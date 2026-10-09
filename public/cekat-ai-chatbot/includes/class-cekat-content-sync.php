<?php
/**
 * Website content sync (Fase D): pushes published Pages, Posts and a
 * composed site-info document into the chatbot knowledge base through
 * the same knowledge sync API the WooCommerce module uses, so the
 * chatbot can answer general website questions (about, FAQ, policies,
 * store address) - not just product questions. Independent of
 * WooCommerce: works on any WordPress site.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cekat_Content_Sync
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Page/post lifecycle -> knowledge base sync
        add_action('save_post_page', array($this, 'handle_content_save'), 20, 1);
        add_action('save_post_post', array($this, 'handle_content_save'), 20, 1);
        add_action('wp_trash_post', array($this, 'handle_content_removed'), 10, 1);
        add_action('before_delete_post', array($this, 'handle_content_removed'), 10, 1);

        // Site identity changes refresh the site-info document
        foreach (array('blogname', 'blogdescription', 'home', 'woocommerce_store_address', 'woocommerce_store_city', 'woocommerce_store_postcode', 'woocommerce_default_country') as $option) {
            add_action('update_option_' . $option, array($this, 'handle_site_info_change'));
        }

        // Manual full-content sync from the settings page
        add_action('wp_ajax_cekat_content_sync_all', array($this, 'ajax_sync_all'));
    }

    /**
     * Sync runs only when the admin enabled it and the connection
     * settings (widget + API key) are complete.
     */
    public static function is_configured()
    {
        return (bool) get_option('cekat_content_sync_enabled', 0)
            && get_option('cekat_widget_id', '') !== ''
            && get_option('cekat_api_key', '') !== '';
    }

    // ------------------------------------------------------------------
    // Hook handlers
    // ------------------------------------------------------------------

    public function handle_content_save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('page', 'post'), true)) {
            return;
        }

        if (!self::is_configured()) {
            return;
        }

        // Only published content belongs in the knowledge base;
        // anything else (draft, pending, private) is removed.
        if ($post->post_status !== 'publish') {
            $this->delete_content($post, false);
            return;
        }

        $this->push_content($post, false);
    }

    public function handle_content_removed($post_id)
    {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('page', 'post'), true)) {
            return;
        }

        if (!self::is_configured()) {
            return;
        }

        $this->delete_content($post, false);
    }

    public function handle_site_info_change()
    {
        if (!self::is_configured()) {
            return;
        }

        $this->push_site_info(false);
    }

    /**
     * Manual full sync (settings page button). Synchronous on purpose:
     * the admin gets a truthful synced/failed count. Suitable for
     * small to medium sites; per-item hooks keep it fresh afterwards.
     */
    public function ajax_sync_all()
    {
        check_ajax_referer('cekat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!self::is_configured()) {
            wp_send_json_error('Aktifkan sinkronisasi konten situs dan lengkapi Widget ID + API Key terlebih dahulu.');
        }

        $posts = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => -1,
        ));

        $synced = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($this->push_content($post)) {
                $synced++;
            } else {
                $failed++;
            }
        }

        if ($this->push_site_info()) {
            $synced++;
        } else {
            $failed++;
        }

        $total = count($posts) + 1;

        update_option('cekat_content_last_sync', array(
            'at' => current_time('mysql'),
            'synced' => $synced,
            'failed' => $failed,
            'total' => $total,
        ));

        if ($failed > 0 && $synced === 0) {
            wp_send_json_error("Sinkronisasi gagal untuk semua konten ({$failed}). Periksa API Key dan batas dokumen paket Anda.");
        }

        wp_send_json_success(array(
            'message' => "Sinkronisasi selesai: {$synced} konten terkirim" . ($failed ? ", {$failed} gagal" : '') . '.',
            'synced' => $synced,
            'failed' => $failed,
        ));
    }

    // ------------------------------------------------------------------
    // Payload + transport
    // ------------------------------------------------------------------

    public function push_content(WP_Post $post, $blocking = true)
    {
        $response = $this->request('POST', $this->build_payload($post), $blocking);

        return $blocking ? (!is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201), true)) : true;
    }

    public function delete_content(WP_Post $post, $blocking = true)
    {
        $response = $this->request('DELETE', array(
            'widget_slug' => get_option('cekat_widget_id', ''),
            'source' => 'wordpress',
            'external_ref' => $this->external_ref($post),
        ), $blocking);

        return $blocking ? (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) : true;
    }

    public function push_site_info($blocking = true)
    {
        $response = $this->request('POST', $this->build_site_info_payload(), $blocking);

        return $blocking ? (!is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201), true)) : true;
    }

    private function external_ref(WP_Post $post)
    {
        return $post->post_type === 'page' ? 'wp-page-' . $post->ID : 'wp-post-' . $post->ID;
    }

    /**
     * Compose the knowledge document for one page/post: title, kind,
     * cleaned body text and the public link.
     */
    public function build_payload(WP_Post $post)
    {
        $lines = array();
        $lines[] = 'Judul: ' . get_the_title($post);
        $lines[] = 'Jenis: ' . ($post->post_type === 'page' ? 'Halaman' : 'Artikel');

        $content = $this->clean_content($post->post_content);
        if ($content !== '') {
            $lines[] = 'Konten: ' . $content;
        }

        $lines[] = 'Tautan: ' . get_permalink($post);

        return array(
            'widget_slug' => get_option('cekat_widget_id', ''),
            'source' => 'wordpress',
            'external_ref' => $this->external_ref($post),
            'name' => get_the_title($post),
            'content' => implode("\n", $lines),
            'url' => get_permalink($post),
        );
    }

    /**
     * One composed document with the site's identity: name, tagline,
     * URL and - when WooCommerce is present - the store address and
     * currency, so "where is your store / do you ship to X" style
     * questions have something to ground on.
     */
    public function build_site_info_payload()
    {
        $lines = array();
        $lines[] = 'Nama Situs: ' . get_bloginfo('name');

        $tagline = trim((string) get_bloginfo('description'));
        if ($tagline !== '') {
            $lines[] = 'Deskripsi: ' . $tagline;
        }

        $lines[] = 'Alamat Web: ' . home_url('/');

        if (class_exists('WooCommerce')) {
            $address = array_filter(array(
                trim((string) get_option('woocommerce_store_address', '')),
                trim((string) get_option('woocommerce_store_address_2', '')),
                trim((string) get_option('woocommerce_store_city', '')),
                trim((string) get_option('woocommerce_store_postcode', '')),
                trim(str_replace(':', ', ', (string) get_option('woocommerce_default_country', ''))),
            ));
            if (!empty($address)) {
                $lines[] = 'Alamat Toko: ' . implode(', ', $address);
            }

            if (function_exists('get_woocommerce_currency')) {
                $lines[] = 'Mata Uang: ' . get_woocommerce_currency();
            }
        }

        return array(
            'widget_slug' => get_option('cekat_widget_id', ''),
            'source' => 'wordpress',
            'external_ref' => 'wp-site-info',
            'name' => get_bloginfo('name') . ' - Informasi Situs',
            'content' => implode("\n", $lines),
            'url' => home_url('/'),
        );
    }

    private function clean_content($html)
    {
        $text = strip_shortcodes((string) $html);
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if (strlen($text) > 5000) {
            $text = substr($text, 0, 5000);
        }

        return $text;
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
