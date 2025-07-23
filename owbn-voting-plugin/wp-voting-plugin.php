<?php

/**
 * Plugin Name: WP Voting Plugin
 * Plugin URI: https://yoursite.com/
 * Description: A comprehensive voting system with support for multiple voting methods
 * Version: 2.0.0
 * Author: YourName
 * Text Domain: wp-voting-plugin
 * Domain Path: /languages
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('WPVP_VERSION', '2.0.0');
define('WPVP_PLUGIN_FILE', __FILE__);
define('WPVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPVP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check WordPress version
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function () {
?>
        <div class="notice notice-error">
            <p><?php _e('WP Voting Plugin requires WordPress 5.0 or higher.', 'wp-voting-plugin'); ?></p>
        </div>
    <?php
    });
    return;
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
    ?>
        <div class="notice notice-error">
            <p><?php _e('WP Voting Plugin requires PHP 7.4 or higher.', 'wp-voting-plugin'); ?></p>
        </div>
    <?php
    });
    return;
}

// Check for multisite (we don't support it)
if (is_multisite()) {
    add_action('admin_notices', function () {
    ?>
        <div class="notice notice-error">
            <p><?php _e('WP Voting Plugin does not support multisite installations.', 'wp-voting-plugin'); ?></p>
        </div>
<?php
    });
    return;
}

// Load the plugin
require_once WPVP_PLUGIN_DIR . 'includes/init.php';

// Load text domain
add_action('plugins_loaded', function () {
    load_plugin_textdomain('wp-voting-plugin', false, dirname(WPVP_PLUGIN_BASENAME) . '/languages');
});

// Activation/Deactivation hooks are registered in includes/core/activation.php

// Add settings link to plugins page
add_filter('plugin_action_links_' . WPVP_PLUGIN_BASENAME, function ($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wpvp-settings') . '">' . __('Settings', 'wp-voting-plugin') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
