<?php

/** File: includes/core/activation.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Handle plugin activation for single-site installations
 */

defined('ABSPATH') || exit;

/**
 * Plugin activation handler
 * Called when the plugin is activated
 */

require_once dirname(__DIR__) . '/helper/db-setup.php';

function wpvp_activate_plugin()
{
    // Check if this is a single site installation
    if (is_multisite()) {
        wp_die(
            __('This plugin is designed for single-site installations only.', 'wp-voting-plugin'),
            __('Plugin Activation Error', 'wp-voting-plugin'),
            ['back_link' => true]
        );
    }

    // Create database tables
    wpvp_create_tables();

    // Set default options
    wpvp_set_default_options();

    // Create required pages
    wpvp_create_default_pages();

    // Flush rewrite rules
    flush_rewrite_rules();

    // Log activation
    error_log('WP Voting Plugin activated successfully at ' . current_time('mysql'));
}

/**
 * Set default plugin options
 */
function wpvp_set_default_options()
{
    // Plugin version
    add_option('wpvp_version', WPVP_VERSION);

    // Default settings
    add_option('wpvp_settings', [
        'enable_public_votes' => false,
        'default_voting_type' => 'single',
        'require_login' => true,
        'show_results_before_closing' => false,
        'enable_email_notifications' => true,
        'results_display_type' => 'chart',
        'timezone' => wp_timezone_string()
    ]);

    // Capabilities
    add_option('wpvp_capabilities', [
        'create_votes' => 'edit_posts',
        'manage_all_votes' => 'manage_options',
        'view_results' => 'read',
        'cast_votes' => 'read'
    ]);
}

/**
 * Create default pages for the plugin
 */
function wpvp_create_default_pages()
{
    $pages = [
        'voting-dashboard' => [
            'title' => 'Voting Dashboard',
            'content' => '[wpvp_dashboard]',
            'parent' => 0
        ],
        'create-vote' => [
            'title' => 'Create Vote',
            'content' => '[wpvp_create_vote]',
            'parent' => 'voting-dashboard'
        ],
        'cast-vote' => [
            'title' => 'Cast Vote',
            'content' => '[wpvp_cast_vote]',
            'parent' => 'voting-dashboard'
        ],
        'vote-results' => [
            'title' => 'Vote Results',
            'content' => '[wpvp_results]',
            'parent' => 'voting-dashboard'
        ]
    ];

    $created_pages = [];

    foreach ($pages as $slug => $page_data) {
        // Check if page already exists
        $existing = get_page_by_path('wpvp-' . $slug);

        if (!$existing) {
            $parent_id = 0;
            if ($page_data['parent'] && isset($created_pages[$page_data['parent']])) {
                $parent_id = $created_pages[$page_data['parent']];
            }

            $page_id = wp_insert_post([
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $parent_id,
                'post_name' => 'wpvp-' . $slug
            ]);

            if (!is_wp_error($page_id)) {
                $created_pages[$slug] = $page_id;
            }
        } else {
            $created_pages[$slug] = $existing->ID;
        }
    }

    // Store page IDs for later use
    update_option('wpvp_page_ids', $created_pages);
}

/**
 * Check plugin version and run upgrades if needed
 */
function wpvp_check_version()
{
    $current_version = get_option('wpvp_version', '0.0.0');

    if (version_compare($current_version, WPVP_VERSION, '<')) {
        // Run upgrade routines
        wpvp_run_upgrades($current_version, WPVP_VERSION);

        // Update version
        update_option('wpvp_version', WPVP_VERSION);
    }
}

/**
 * Run upgrade routines based on version
 */
function wpvp_run_upgrades($from_version, $to_version)
{
    // Example: Upgrade from 1.x to 2.x
    if (version_compare($from_version, '2.0.0', '<')) {
        // Check for old owbn tables and offer migration
        global $wpdb;
        $old_table = $wpdb->prefix . 'voting';

        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table) {
            // Add admin notice about migration
            add_option('wpvp_show_migration_notice', true);
        }
    }

    // Always check database structure
    wpvp_create_tables();
}

/**
 * Show migration notice if needed
 */
add_action('admin_notices', function () {
    if (get_option('wpvp_show_migration_notice')) {
?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>WP Voting Plugin:</strong>
                We detected an old voting table. Would you like to migrate your data?
                <a href="<?php echo admin_url('tools.php?page=wpvp-migration'); ?>">Run Migration</a>
            </p>
        </div>
<?php
    }
});

// Hook activation
register_activation_hook(WPVP_PLUGIN_FILE, 'wpvp_activate_plugin');

// Check version on admin init
add_action('admin_init', 'wpvp_check_version');
