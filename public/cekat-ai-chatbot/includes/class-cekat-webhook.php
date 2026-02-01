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
