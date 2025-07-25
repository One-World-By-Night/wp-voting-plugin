<?php

/** File: includes/core/enqueue.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

/**
 * Enqueue admin styles
 */
function wpvp_enqueue_admin_styles() {
    wp_enqueue_style(
        'wp-voting-plugin', 
        WPVP_PLUGIN_URL . 'includes/assets/css/wp-voting-plugin.css', 
        array(), 
        WPVP_VERSION, 
        'all'
    );
}
add_action('admin_enqueue_scripts', 'wpvp_enqueue_admin_styles');

/**
 * Enqueue admin scripts
 */
function wpvp_enqueue_admin_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'wpvp') === false && strpos($hook, 'wp-voting-plugin') === false) {
        return;
    }
    
    // Enqueue Select2 from local files
    wp_enqueue_style(
        'wpvp-select2', 
        WPVP_PLUGIN_URL . 'includes/assets/css/select2.min.css',
        array(),
        WPVP_VERSION
    );
    
    wp_enqueue_script(
        'wpvp-select2',
        WPVP_PLUGIN_URL . 'includes/assets/js/select2.min.js',
        array('jquery'),
        WPVP_VERSION,
        true
    );
    
    // Your existing script enqueue
    wp_enqueue_script(
        'wp-voting-plugin',
        WPVP_PLUGIN_URL . 'includes/assets/js/wp-voting-plugin.js',
        array('jquery', 'wpvp-select2'), // Add wpvp-select2 as dependency
        WPVP_VERSION,
        true
    );
    
    // Update localization
    wp_localize_script('wp-voting-plugin', 'wpvp_admin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpvp_admin_nonce'),
        'option_text_placeholder' => __('Option text', 'wp-voting-plugin'),
        'option_desc_placeholder' => __('Optional description', 'wp-voting-plugin'),
        'remove_text' => __('Remove', 'wp-voting-plugin'),
        'replace_options_confirm' => __('This voting type uses predefined options. Replace current options?', 'wp-voting-plugin'),
        'select_voters_placeholder' => __('Select who can vote...', 'wp-voting-plugin'),
        'select_groups_placeholder' => __('Select or add groups...', 'wp-voting-plugin'),
        'select_users_placeholder' => __('Search users...', 'wp-voting-plugin')
    ));
}
add_action('admin_enqueue_scripts', 'wpvp_enqueue_admin_scripts');