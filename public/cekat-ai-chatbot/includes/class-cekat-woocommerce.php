<?php
/**
 * WooCommerce Integration for Cekat AI Chatbot
 *
 * Syncs the product catalog into the Cekat knowledge base of the
 * configured channel, so the chatbot can answer product and stock
 * questions. Every published product becomes one knowledge document
 * (upserted through the Cekat API with the site's API key), kept fresh
 * by product lifecycle hooks: save, stock change, trash, delete.
 *
 * @package Cekat_AI_Chatbot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Cekat_WooCommerce
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Product lifecycle -> knowledge base sync
        add_action('save_post_product', array($this, 'handle_product_save'), 20, 1);
        add_action('woocommerce_product_set_stock', array($this, 'handle_stock_change'));
        add_action('woocommerce_variation_set_stock', array($this, 'handle_variation_stock_change'));
        add_action('wp_trash_post', array($this, 'handle_product_removed'), 10, 1);
        add_action('before_delete_post', array($this, 'handle_product_removed'), 10, 1);

        // Manual full-catalog sync from the settings page
        add_action('wp_ajax_cekat_wc_sync_all', array($this, 'ajax_sync_all'));
    }

    /**
     * Sync runs only when the admin enabled it and the connection
     * settings (widget + API key) are complete.
     */
    public static function is_configured()
    {
        return (bool) get_option('cekat_wc_enabled', 1)
            && (bool) get_option('cekat_wc_sync_enabled', 0)
            && get_option('cekat_widget_id', '') !== ''
            && get_option('cekat_api_key', '') !== '';
    }

    // ------------------------------------------------------------------
    // Hook handlers
    // ------------------------------------------------------------------

    public function handle_product_save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!self::is_configured() || get_post_type($post_id) !== 'product') {
            return;
        }

        // Only published products belong in the knowledge base; anything
        // else (draft, private, pending) is removed from it.
        if (get_post_status($post_id) !== 'publish') {
            $this->delete_product($post_id);
            return;
        }

        $product = wc_get_product($post_id);
        if ($product) {
            $this->push_product($product);
        }
    }

    public function handle_stock_change($product)
    {
        if (!self::is_configured() || !$product instanceof WC_Product) {
            return;
        }

        if ($product->get_status() === 'publish') {
            $this->push_product($product, false);
        }
    }

    public function handle_variation_stock_change($variation)
    {
        if (!self::is_configured() || !$variation instanceof WC_Product) {
            return;
        }

        $parent = wc_get_product($variation->get_parent_id());
        if ($parent && $parent->get_status() === 'publish') {
            $this->push_product($parent, false);
        }
    }

    public function handle_product_removed($post_id)
    {
        if (!self::is_configured() || get_post_type($post_id) !== 'product') {
            return;
        }

        $this->delete_product($post_id);
    }

    // ------------------------------------------------------------------
    // Sync operations
    // ------------------------------------------------------------------

    /**
     * Upsert one product as a knowledge document.
     */
    public function push_product(WC_Product $product, $blocking = true)
    {
        $response = $this->request('POST', $this->build_payload($product), $blocking);

        return $blocking ? (!is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201), true)) : true;
    }

    /**
     * Remove one product's knowledge document (idempotent server-side).
     */
    public function delete_product($product_id, $blocking = true)
    {
        $response = $this->request('DELETE', array(
            'widget_slug' => get_option('cekat_widget_id', ''),
            'source' => 'woocommerce',
            'external_ref' => 'wc-product-' . $product_id,
        ), $blocking);

        return $blocking ? (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) : true;
    }

    /**
     * Manual full sync (settings page button). Synchronous on purpose:
     * the admin gets a truthful synced/failed count. Suitable for small
     * to medium catalogs; per-product hooks keep it fresh afterwards.
     */
    public function ajax_sync_all()
    {
        check_ajax_referer('cekat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!self::is_configured()) {
            wp_send_json_error('Aktifkan sinkronisasi WooCommerce dan lengkapi Widget ID + API Key terlebih dahulu.');
        }

        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            if ($this->push_product($product)) {
                $synced++;
            } else {
                $failed++;
            }
        }

        update_option('cekat_wc_last_sync', array(
            'at' => current_time('mysql'),
            'synced' => $synced,
            'failed' => $failed,
            'total' => count($products),
        ));

        if ($failed > 0 && $synced === 0) {
            wp_send_json_error("Sinkronisasi gagal untuk semua produk ({$failed}). Periksa API Key dan batas dokumen paket Anda.");
        }

        wp_send_json_success(array(
            'message' => "Sinkronisasi selesai: {$synced} produk terkirim" . ($failed ? ", {$failed} gagal" : '') . '.',
            'synced' => $synced,
            'failed' => $failed,
        ));
    }

    // ------------------------------------------------------------------
    // Payload + transport
    // ------------------------------------------------------------------

    /**
     * Compose the knowledge document for one product. The chatbot reads
     * this text, so it is written the way a shop assistant would answer:
     * name, price (with promo), stock state, categories, description,
     * and the product link.
     */
    public function build_payload(WC_Product $product)
    {
        $lines = array();
        $lines[] = 'Produk: ' . $product->get_name();

        if ($product->is_type('variable')) {
            $min = (float) $product->get_variation_price('min');
            $max = (float) $product->get_variation_price('max');
            $lines[] = 'Harga: ' . $this->format_price($min) . ($max > $min ? ' - ' . $this->format_price($max) : '');
        } elseif ($product->is_on_sale() && $product->get_sale_price() !== '') {
            $lines[] = 'Harga: ' . $this->format_price((float) $product->get_sale_price())
                . ' (harga normal ' . $this->format_price((float) $product->get_regular_price()) . ', sedang promo)';
        } elseif ($product->get_price() !== '') {
            $lines[] = 'Harga: ' . $this->format_price((float) $product->get_price());
        }

        if ($product->get_sku()) {
            $lines[] = 'SKU: ' . $product->get_sku();
        }

        $lines[] = 'Stok: ' . $this->stock_text($product);

        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        if (!is_wp_error($categories) && !empty($categories)) {
            $lines[] = 'Kategori: ' . implode(', ', $categories);
        }

        $description = $product->get_short_description() ?: $product->get_description();
        $description = trim(wp_strip_all_tags($description));
        if ($description !== '') {
            if (strlen($description) > 1500) {
                $description = substr($description, 0, 1500);
            }
            $lines[] = 'Deskripsi: ' . $description;
        }

        $lines[] = 'Tautan: ' . get_permalink($product->get_id());

        return array(
            'widget_slug' => get_option('cekat_widget_id', ''),
            'source' => 'woocommerce',
            'external_ref' => 'wc-product-' . $product->get_id(),
            'name' => $product->get_name(),
            'content' => implode("\n", $lines),
            'url' => get_permalink($product->get_id()),
        );
    }

    private function stock_text(WC_Product $product)
    {
        if (!$product->is_in_stock()) {
            return 'Habis';
        }

        if ($product->is_on_backorder()) {
            return 'Bisa dipesan (backorder)';
        }

        if ($product->managing_stock()) {
            $qty = $product->get_stock_quantity();
            if ($qty !== null) {
                return 'Tersedia (sisa ' . $qty . ')';
            }
        }

        return 'Tersedia';
    }

    private function format_price($amount)
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    /**
     * One call to the Cekat knowledge sync API with the site's API key.
     */
    private function request($method, array $body, $blocking = true)
    {
        return wp_remote_request(CEKAT_API_URL . '/api/v1/knowledge/documents', array(
            'method' => $method,
            'timeout' => 15,
            'blocking' => $blocking,
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option('cekat_api_key', ''),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
        ));
    }
}
