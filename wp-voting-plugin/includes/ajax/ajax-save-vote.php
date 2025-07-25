<?php

/** File: includes/ajax/ajax-save-vote.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Display and manage all votes in admin
 */

defined('ABSPATH') || exit;

/**
 * Get predefined options for vote type
 */
add_action('wp_ajax_wpvp_get_predefined_options', 'wpvp_ajax_get_predefined_options');
function wpvp_ajax_get_predefined_options() {
    check_ajax_referer('wpvp_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $voting_type = sanitize_text_field($_POST['voting_type']);
    $options = wpvp_get_predefined_options($voting_type);
    
    if ($options) {
        wp_send_json_success($options);
    } else {
        wp_send_json_error('No predefined options for this type');
    }
}

/**
 * Get vote type description
 */
add_action('wp_ajax_wpvp_get_vote_type_description', 'wpvp_ajax_get_vote_type_description');
function wpvp_ajax_get_vote_type_description() {
    check_ajax_referer('wpvp_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $voting_type = sanitize_text_field($_POST['voting_type']);
    $descriptions = wpvp_get_vote_type_descriptions();
    
    if (isset($descriptions[$voting_type])) {
        wp_send_json_success($descriptions[$voting_type]);
    } else {
        wp_send_json_error('No description available');
    }
}