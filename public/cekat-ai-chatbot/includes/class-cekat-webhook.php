<?php
/**
 * Cekat Webhook Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cekat_Webhook
{
    private static $instance = null;
    private $namespace = 'cekat/v1';

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Validation done via Signature
        ));
    }

    public function handle_webhook($request)
    {
        // 1. Verify Signature
        $signature = $request->get_header('x_cekat_signature');
        $timestamp = $request->get_header('x_cekat_timestamp');
        $secret = get_option('cekat_webhook_secret', '');

        if (!$this->verify_signature($request->get_body(), $secret, $signature, $timestamp)) {
            return new WP_Error('invalid_signature', 'Invalid Signature', array('status' => 401));
        }

        // 2. Parse Payload
        $params = $request->get_json_params();
        $action = $params['action'] ?? '';

        // 3. Route Action
        switch ($action) {
            case 'save_lead':
                return $this->handle_save_lead($params);
            case 'check_status':
                return $this->handle_check_status($params);
            case 'create_order':
                return $this->handle_create_order($params);
            default:
                return new WP_Error('invalid_action', 'Unknown Action', array('status' => 400));
        }
    }

    /**
     * Handle save_lead action
     * Default: Email the admin
     */
    private function handle_save_lead($data)
    {
        $name = sanitize_text_field($data['name'] ?? '-');
        $email = sanitize_email($data['email'] ?? '-');
        $phone = sanitize_text_field($data['phone'] ?? '-');

        // Logic Custom: Save to DB or Email
        // For MVP: Send Email to Admin
        $to = get_option('admin_email');
        $subject = '[Cekat AI] New Lead: ' . $name;
        $message = "New Lead received from Chatbot:\n\n";
        $message .= "Name: $name\n";
        $message .= "Email: $email\n";
        $message .= "Phone: $phone\n";

        wp_mail($to, $subject, $message);

        // Bonus: Fire a standard WP Action for other plugins (CRM) to hook into
        do_action('cekat_new_lead', $data);

        return rest_ensure_response(array('success' => true, 'message' => 'Lead saved'));
    }

    /**
     * Handle check_status action: real-time WooCommerce order lookup.
     *
     * Privacy rules:
     * - A configured webhook secret is mandatory here (unlike the legacy
     *   actions, an order lookup must never run in the no-secret mode).
     * - The caller must prove ownership with the email or phone used at
     *   checkout. "Order not found" and "contact mismatch" deliberately
     *   return the same error so order numbers cannot be enumerated.
     */
    private function handle_check_status($data)
    {
        if (empty(get_option('cekat_webhook_secret', ''))) {
            return new WP_Error('secret_required', 'Webhook secret is not configured', array('status' => 401));
        }

        if (!function_exists('wc_get_order')) {
            return new WP_Error('woocommerce_inactive', 'WooCommerce is not active', array('status' => 400));
        }

        $reference = sanitize_text_field($data['reference_id'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));

        if ($reference === '') {
            return rest_ensure_response(array('success' => false, 'error' => 'verification_required'));
        }

        if ($email === '' && $phone === '') {
            return rest_ensure_response(array('success' => false, 'error' => 'verification_required'));
        }

        $order = $this->find_order($reference);

        if (!$order || !$this->verify_order_contact($order, $email, $phone)) {
            return rest_ensure_response(array('success' => false, 'error' => 'verification_failed'));
        }

        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = array(
                'name' => $item->get_name(),
                'qty' => $item->get_quantity(),
                'total' => $this->format_money($item->get_total(), $order),
            );
        }

        $date_created = $order->get_date_created();

        return rest_ensure_response(array(
            'success' => true,
            'order' => array(
                'id' => $order->get_id(),
                'number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'date' => $date_created ? wc_format_datetime($date_created) : null,
                'items' => $items,
                'total' => $this->format_money($order->get_total(), $order),
                'payment_method' => $order->get_payment_method_title(),
                'paid' => $order->is_paid(),
                'tracking' => $this->order_tracking_number($order),
            ),
        ));
    }

    /**
     * Find an order by id, falling back to the _order_number meta used
     * by sequential-order-number setups.
     */
    private function find_order($reference)
    {
        if (is_numeric($reference)) {
            $order = wc_get_order((int) $reference);
            if ($order) {
                return $order;
            }
        }

        $found = wc_get_orders(array(
            'limit' => 1,
            'meta_key' => '_order_number',
            'meta_value' => $reference,
        ));

        return !empty($found) ? $found[0] : null;
    }

    /**
     * Ownership check: billing email (exact, case-insensitive) or
     * billing phone (last-9-digits suffix match, formatting-proof).
     */
    private function verify_order_contact($order, $email, $phone)
    {
        if ($email !== '' && strtolower($email) === strtolower($order->get_billing_email())) {
            return true;
        }

        if ($phone !== '') {
            $billing = preg_replace('/\D+/', '', (string) $order->get_billing_phone());
            if ($billing !== '' && substr($billing, -9) === substr($phone, -9)) {
                return true;
            }
        }

        return false;
    }

    private function order_tracking_number($order)
    {
        $tracking = $order->get_meta('_tracking_number');
        if (!empty($tracking)) {
            return $tracking;
        }

        $items = $order->get_meta('_wc_shipment_tracking_items');
        if (is_array($items) && !empty($items[0]['tracking_number'])) {
            return $items[0]['tracking_number'];
        }

        return null;
    }

    private function format_money($amount, $order)
    {
        return wp_strip_all_tags(wc_price($amount, array('currency' => $order->get_currency())));
    }

    /**
     * Handle create_order action (Placeholder)
     */
    private function handle_create_order($data)
    {
        // Logic: integration with WooCommerce could go here
        do_action('cekat_create_order', $data);
        return rest_ensure_response(array('success' => true, 'message' => 'Order processed'));
    }

    /**
     * Verify HMAC SHA256 Signature
     */
    private function verify_signature($payload, $secret, $signature, $timestamp)
    {
        if (empty($secret))
            return true; // Debug mode: allow if no secret set (not recommended prod)

        // Tolerance 5 mins
        if (abs(time() - $timestamp) > 300)
            return false;

        $dataToSign = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $dataToSign, $secret);

        return hash_equals($expected, $signature);
    }
}
