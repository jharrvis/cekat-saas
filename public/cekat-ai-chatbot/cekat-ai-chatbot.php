<?php
/**
 * Plugin Name: Cekat AI Chatbot
 * Plugin URI: https://cekat.biz.id
 * Description: AI-Powered Customer Service Chatbot untuk WordPress. Integrasikan chatbot cerdas ke website Anda dalam hitungan menit.
 * Version: 1.0.0
 * Author: Cekat.biz.id
 * Author URI: https://cekat.biz.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cekat-ai-chatbot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CEKAT_VERSION', '1.0.0');
define('CEKAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CEKAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CEKAT_API_URL', 'https://cekat.biz.id');

/**
 * Main Plugin Class
 */
class Cekat_AI_Chatbot
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
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        // Frontend widget
        add_action('wp_footer', array($this, 'render_widget'));

        // AJAX handlers
        add_action('wp_ajax_cekat_test_connection', array($this, 'ajax_test_connection'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Set default options
        add_option('cekat_widget_id', '');
        add_option('cekat_enabled', '1');
        add_option('cekat_position', 'bottom-right');
        add_option('cekat_primary_color', '#6366f1');
        add_option('cekat_exclude_pages', '');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Cekat AI Chatbot', 'cekat-ai-chatbot'),
            __('Cekat AI', 'cekat-ai-chatbot'),
            'manage_options',
            'cekat-ai-chatbot',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            80
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        // Widget Settings
        register_setting('cekat_settings', 'cekat_widget_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('cekat_settings', 'cekat_enabled', array(
            'sanitize_callback' => 'absint'
        ));
        register_setting('cekat_settings', 'cekat_position', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('cekat_settings', 'cekat_primary_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('cekat_settings', 'cekat_exclude_pages', array(
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook)
    {
        if ('toplevel_page_cekat-ai-chatbot' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'cekat-admin-css',
            CEKAT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CEKAT_VERSION
        );

        wp_enqueue_script(
            'cekat-admin-js',
            CEKAT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CEKAT_VERSION,
            true
        );

        wp_localize_script('cekat-admin-js', 'cekatAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cekat_admin_nonce')
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        include CEKAT_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Render widget in footer
     */
    public function render_widget()
    {
        // Check if enabled
        if (!get_option('cekat_enabled', '1')) {
            return;
        }

        // Get widget ID
        $widget_id = get_option('cekat_widget_id');
        if (empty($widget_id)) {
            return;
        }

        // Check excluded pages
        $exclude_pages = get_option('cekat_exclude_pages', '');
        if (!empty($exclude_pages)) {
            $excludes = array_map('trim', explode("\n", $exclude_pages));
            $current_url = $_SERVER['REQUEST_URI'];

            foreach ($excludes as $exclude) {
                if (!empty($exclude) && strpos($current_url, $exclude) !== false) {
                    return;
                }
            }
        }

        // Get settings
        $position = get_option('cekat_position', 'bottom-right');
        $primary_color = get_option('cekat_primary_color', '#6366f1');

        ?>
        <!-- Cekat AI Chatbot Widget -->
        <script>
            window.CSAIConfig = {
                widgetId: '<?php echo esc_js($widget_id); ?>'
            };
        </script>
        <script src="<?php echo esc_url(CEKAT_API_URL); ?>/widget/widget.min.js" async></script>
        <?php
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection()
    {
        check_ajax_referer('cekat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $widget_id = sanitize_text_field($_POST['widget_id']);

        if (empty($widget_id)) {
            wp_send_json_error('Widget ID required');
        }

        // Test API connection
        $response = wp_remote_get(CEKAT_API_URL . '/api/widget/' . $widget_id . '/config');

        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && isset($body['widgetId'])) {
            wp_send_json_success(array(
                'message' => 'Connected successfully!',
                'widget_name' => $body['title'] ?? 'Unknown'
            ));
        } else {
            wp_send_json_error('Widget not found or invalid ID');
        }
    }
}

// Initialize plugin
Cekat_AI_Chatbot::get_instance();
