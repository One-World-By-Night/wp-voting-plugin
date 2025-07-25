<?php

/** File: includes/ajax/vote-ajax.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Display and manage all votes in admin
 */

defined('ABSPATH') || exit;

/**
 * Search users for Select2
 */
add_action('wp_ajax_wpvp_search_users', 'wpvp_ajax_search_users');
function wpvp_ajax_search_users() {
    check_ajax_referer('wpvp_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    
    $args = array(
        'search' => '*' . $search . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => 20,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );
    
    $users = get_users($args);
    $results = array();
    
    foreach ($users as $user) {
        $results[] = array(
            'id' => $user->ID,
            'text' => $user->display_name . ' (' . $user->user_email . ')'
        );
    }
    
    wp_send_json_success($results);
}