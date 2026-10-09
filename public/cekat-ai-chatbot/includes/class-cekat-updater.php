<?php
/**
 * GitHub-based auto-update checker.
 *
 * The plugin polls an update-info JSON published in the public GitHub
 * repo (raw URL) and injects the result into the WordPress plugin
 * update transient, so new versions appear - and install - exactly
 * like wordpress.org updates, with no manual ZIP uploads. Any fetch
 * or parse failure leaves WordPress behaviour untouched.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cekat_Updater
{
    private static $instance = null;

    private $info_url = 'https://raw.githubusercontent.com/jharrvis/cekat-saas/integration/cek-remediation-20261007/public/downloads/cekat-ai-chatbot-update.json';
    private $slug = 'cekat-ai-chatbot';
    private $plugin_file;
    private $cache_key = 'cekat_update_info';

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->plugin_file = plugin_basename(CEKAT_PLUGIN_DIR . 'cekat-ai-chatbot.php');

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_action('upgrader_process_complete', array($this, 'clear_cache'));
    }

    public function clear_cache()
    {
        delete_transient($this->cache_key);
    }

    /**
     * Fetch the remote update info, cached for 12 hours so WordPress'
     * twice-daily update checks cause at most two requests a day.
     */
    private function remote_info()
    {
        $cached = get_transient($this->cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $url = apply_filters('cekat_update_info_url', $this->info_url);
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array('Accept' => 'application/json'),
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $info = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($info) || empty($info['version']) || empty($info['download_url'])) {
            return null;
        }

        set_transient($this->cache_key, $info, 12 * HOUR_IN_SECONDS);

        return $info;
    }

    public function check_for_update($transient)
    {
        if (empty($transient->checked) || !isset($transient->checked[$this->plugin_file])) {
            return $transient;
        }

        $info = $this->remote_info();
        if (!$info) {
            return $transient;
        }

        $item = (object) array(
            'id' => $this->plugin_file,
            'slug' => $this->slug,
            'plugin' => $this->plugin_file,
            'new_version' => $info['version'],
            'url' => 'https://cekat.biz.id',
            'package' => $info['download_url'],
            'icons' => array(),
            'banners' => array(),
            'banners_rtl' => array(),
            'tested' => $info['tested'] ?? '',
            'requires_php' => $info['requires_php'] ?? '',
            'compatibility' => new stdClass(),
        );

        if (version_compare($info['version'], CEKAT_VERSION, '>')) {
            $transient->response[$this->plugin_file] = $item;
        } else {
            $transient->no_update[$this->plugin_file] = $item;
        }

        return $transient;
    }

    /**
     * "View details" popup content for this plugin.
     */
    public function plugin_info($result, $action, $args)
    {
        if ($action !== 'plugin_information' || ($args->slug ?? null) !== $this->slug) {
            return $result;
        }

        $info = $this->remote_info();
        if (!$info) {
            return $result;
        }

        $res = new stdClass();
        $res->name = 'Cekat AI Chatbot';
        $res->slug = $this->slug;
        $res->version = $info['version'];
        $res->author = '<a href="https://cekat.biz.id">Cekat.biz.id</a>';
        $res->homepage = 'https://cekat.biz.id';
        $res->requires = $info['requires'] ?? '5.0';
        $res->tested = $info['tested'] ?? '';
        $res->requires_php = $info['requires_php'] ?? '7.4';
        $res->download_link = $info['download_url'];
        $res->last_updated = $info['last_updated'] ?? '';
        $res->sections = array(
            'description' => 'AI-Powered Customer Service Chatbot untuk WordPress. Integrasikan chatbot cerdas ke website Anda dalam hitungan menit.',
            'changelog' => !empty($info['changelog']) ? nl2br(esc_html($info['changelog'])) : '',
        );

        return $res;
    }
}
