<?php
/**
 * Uninstall WP Voting Plugin
 *
 * Removes all plugin data when deleted through WordPress admin
 * This file is called automatically when plugin is deleted
 *
 * @package WP_Voting_Plugin
 * @version 2.0.0
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the db-setup file to get access to wpvp_drop_tables()
require_once plugin_dir_path(__FILE__) . 'includes/helper/db-setup.php';

// Check if user wants to keep data
$keep_data = get_option('wpvp_keep_data_on_uninstall', false);

if (!$keep_data) {
    // Remove all plugin tables
    wpvp_drop_tables();
}

// Remove all plugin options
$options_to_delete = [
    'wpvp_version',
    'wpvp_settings',
    'wpvp_capabilities',
    'wpvp_page_ids',
    'wpvp_show_migration_notice',
    'wpvp_db_version',
    'wpvp_permission_mode',
    'wpvp_can_vote_users'
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Remove plugin pages
$page_ids = get_option('wpvp_page_ids', []);
foreach ($page_ids as $page_id) {
    wp_delete_post($page_id, true); // true = force delete (skip trash)
}

// Remove any custom capabilities from roles
$roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
$caps = ['wpvp_create_votes', 'wpvp_manage_votes', 'wpvp_cast_votes', 'wpvp_view_results'];

foreach ($roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($caps as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Clear any cached data
wp_cache_flush();