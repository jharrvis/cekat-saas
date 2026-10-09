<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$widget_id = get_option('cekat_widget_id', '');
$enabled = get_option('cekat_enabled', '1');
$position = get_option('cekat_position', 'bottom-right');
$primary_color = get_option('cekat_primary_color', '#6366f1');
$primary_color = get_option('cekat_primary_color', '#6366f1');
$exclude_pages = get_option('cekat_exclude_pages', '');
$api_key = get_option('cekat_api_key', '');
$wc_enabled = get_option('cekat_wc_enabled', 1);
$wc_sync_enabled = get_option('cekat_wc_sync_enabled', 0);
$wc_last_sync = get_option('cekat_wc_last_sync', null);
$webhook_secret = get_option('cekat_webhook_secret', '');
?>

<div class="wrap cekat-admin-wrap">
    <div class="cekat-header">
        <h1>
            <span class="dashicons dashicons-format-chat"></span>
            <?php _e('Cekat AI Chatbot', 'cekat-ai-chatbot'); ?>
        </h1>
        <p class="cekat-version">v<?php echo CEKAT_VERSION; ?></p>
    </div>

    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully!', 'cekat-ai-chatbot'); ?></p>
        </div>
    <?php endif; ?>

    <div class="cekat-content">
        <div class="cekat-main">
            <form method="post" action="options.php">
                <?php settings_fields('cekat_settings'); ?>

                <!-- Connection Card -->
                <div class="cekat-card">
                    <h2><?php _e('Widget Connection', 'cekat-ai-chatbot'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cekat_widget_id"><?php _e('Widget ID', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="cekat_widget_id" 
                                       name="cekat_widget_id" 
                                       value="<?php echo esc_attr($widget_id); ?>" 
                                       class="regular-text"
                                       placeholder="widget-xxx-xxxxxxxx">
                                <button type="button" id="cekat-test-connection" class="button">
                                    <?php _e('Test Connection', 'cekat-ai-chatbot'); ?>
                                </button>
                                <span id="cekat-connection-status"></span>
                                <p class="description">
                                    <?php _e('Find your Widget ID in the', 'cekat-ai-chatbot'); ?>
                                    <a href="https://cekat.biz.id/integration" target="_blank"><?php _e('Cekat Dashboard → Integration', 'cekat-ai-chatbot'); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Settings Card -->
                <div class="cekat-card">
                    <h2><?php _e('Widget Settings', 'cekat-ai-chatbot'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cekat_enabled"><?php _e('Enable Widget', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <label class="cekat-toggle">
                                    <input type="checkbox" 
                                           id="cekat_enabled" 
                                           name="cekat_enabled" 
                                           value="1" 
                                           <?php checked($enabled, '1'); ?>>
                                    <span class="cekat-toggle-slider"></span>
                                </label>
                                <p class="description"><?php _e('Show chatbot widget on your website', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="cekat_position"><?php _e('Position', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <select id="cekat_position" name="cekat_position">
                                    <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>><?php _e('Bottom Right', 'cekat-ai-chatbot'); ?></option>
                                    <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>><?php _e('Bottom Left', 'cekat-ai-chatbot'); ?></option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="cekat_exclude_pages"><?php _e('Exclude Pages', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <textarea id="cekat_exclude_pages" 
                                          name="cekat_exclude_pages" 
                                          rows="4" 
                                          class="large-text"
                                          placeholder="/checkout&#10;/cart&#10;/my-account"><?php echo esc_textarea($exclude_pages); ?></textarea>
                                <p class="description"><?php _e('Enter one URL path per line. Widget will not appear on these pages.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Webhook Card -->
                <div class="cekat-card">
                    <h2><?php _e('Webhook Integration', 'cekat-ai-chatbot'); ?></h2>
                    <p class="description">
                        <?php _e('Allow Cekat Chatbot to perform actions on your WordPress site (e.g., Save Leads).', 'cekat-ai-chatbot'); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label><?php _e('Webhook URL', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       value="<?php echo esc_url(rest_url('cekat/v1/webhook')); ?>" 
                                       class="regular-text" 
                                       readonly
                                       onclick="this.select()">
                                <p class="description"><?php _e('Copy this URL and paste it into your Cekat Widget Settings.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cekat_webhook_secret"><?php _e('Secret Key', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="cekat_webhook_secret" 
                                       name="cekat_webhook_secret" 
                                       value="<?php echo esc_attr($webhook_secret); ?>" 
                                       class="regular-text">
                                <button type="button" class="button button-secondary" onclick="document.getElementById('cekat_webhook_secret').type = 'text'">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <p class="description"><?php _e('Create a random secret key and save it here AND in Cekat Widget Settings.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cekat_api_key"><?php _e('API Key', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <input type="password"
                                       id="cekat_api_key"
                                       name="cekat_api_key"
                                       value="<?php echo esc_attr($api_key); ?>"
                                       class="regular-text"
                                       placeholder="ck_live_...">
                                <button type="button" class="button button-secondary" onclick="document.getElementById('cekat_api_key').type = 'text'">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <p class="description"><?php _e('Create an API key in your Cekat dashboard (Settings &gt; API Keys). Used by integrations such as the WooCommerce product sync below.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php if (class_exists('WooCommerce')) : ?>
                <!-- WooCommerce Card -->
                <div class="cekat-card">
                    <h2><?php _e('WooCommerce Integration', 'cekat-ai-chatbot'); ?></h2>
                    <p class="description" style="margin-bottom: 12px;">
                        <?php _e('Connect WooCommerce to the chatbot: sync your product catalog (name, price, stock, description, link) into the knowledge base, let visitors check order status in the chat, and let them place orders from the chat. Turn the master switch off to disable the whole integration.', 'cekat-ai-chatbot'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cekat_wc_enabled"><?php _e('WooCommerce Integration', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           id="cekat_wc_enabled"
                                           name="cekat_wc_enabled"
                                           value="1"
                                           <?php checked($wc_enabled, 1); ?>>
                                    <?php _e('Enable the WooCommerce integration (master switch)', 'cekat-ai-chatbot'); ?>
                                </label>
                                <p class="description"><?php _e('When off, product sync stops and the chat order features (order status checks and ordering) are refused. Products already synced stay in the knowledge base until removed there.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cekat_wc_sync_enabled"><?php _e('Enable Sync', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           id="cekat_wc_sync_enabled"
                                           name="cekat_wc_sync_enabled"
                                           value="1"
                                           <?php checked($wc_sync_enabled, 1); ?>>
                                    <?php _e('Keep products synced to the chatbot knowledge base', 'cekat-ai-chatbot'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Full Sync', 'cekat-ai-chatbot'); ?></label>
                            </th>
                            <td>
                                <button type="button" id="cekat-wc-sync-all" class="button">
                                    <?php _e('Sync All Products Now', 'cekat-ai-chatbot'); ?>
                                </button>
                                <span id="cekat-wc-sync-status"></span>
                                <?php if (is_array($wc_last_sync)) : ?>
                                    <p class="description">
                                        <?php printf(__('Last full sync: %s (%d sent, %d failed of %d products).', 'cekat-ai-chatbot'), esc_html($wc_last_sync['at']), (int) $wc_last_sync['synced'], (int) $wc_last_sync['failed'], (int) $wc_last_sync['total']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="description"><?php _e('Save the settings first, then run a full sync once. After that, product changes sync automatically.', 'cekat-ai-chatbot'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php submit_button(__('Save Settings', 'cekat-ai-chatbot')); ?>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="cekat-sidebar">
            <div class="cekat-card cekat-info-card">
                <h3><?php _e('Quick Start', 'cekat-ai-chatbot'); ?></h3>
                <ol>
                    <li><?php _e('Login to your Cekat.biz.id dashboard', 'cekat-ai-chatbot'); ?></li>
                    <li><?php _e('Create or select a chatbot widget', 'cekat-ai-chatbot'); ?></li>
                    <li><?php _e('Copy the Widget ID from Integration page', 'cekat-ai-chatbot'); ?></li>
                    <li><?php _e('Paste it here and save', 'cekat-ai-chatbot'); ?></li>
                </ol>
            </div>

            <div class="cekat-card cekat-support-card">
                <h3><?php _e('Need Help?', 'cekat-ai-chatbot'); ?></h3>
                <p><?php _e('Visit our documentation or contact support.', 'cekat-ai-chatbot'); ?></p>
                <a href="https://cekat.biz.id" target="_blank" class="button button-secondary">
                    <?php _e('Visit Cekat.biz.id', 'cekat-ai-chatbot'); ?>
                </a>
            </div>

            <div class="cekat-card cekat-status-card">
                <h3><?php _e('Widget Status', 'cekat-ai-chatbot'); ?></h3>
                <div class="cekat-status-item">
                    <span class="cekat-status-label"><?php _e('Plugin', 'cekat-ai-chatbot'); ?></span>
                    <span class="cekat-status-badge cekat-status-active"><?php _e('Active', 'cekat-ai-chatbot'); ?></span>
                </div>
                <div class="cekat-status-item">
                    <span class="cekat-status-label"><?php _e('Widget', 'cekat-ai-chatbot'); ?></span>
                    <span class="cekat-status-badge <?php echo $enabled && !empty($widget_id) ? 'cekat-status-active' : 'cekat-status-inactive'; ?>">
                        <?php echo $enabled && !empty($widget_id) ? __('Enabled', 'cekat-ai-chatbot') : __('Disabled', 'cekat-ai-chatbot'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
