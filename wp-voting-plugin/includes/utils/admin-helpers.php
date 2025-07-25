<?php

/** File: includes/utils/admin-helpers.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

function wpvp_votes_add_query_arg($args = array()) {
    $base_url = admin_url('edit.php?post_type=vote');
    return add_query_arg($args, $base_url);
}

function wpvp_votes_remove_query_arg($key) {
    $base_url = admin_url('edit.php?post_type=vote');
    return remove_query_arg($key, $base_url);
}

function wpvp_get_current_filters() {
    $filters = array();
    if (isset($_GET['s'])) {
        $filters['search'] = sanitize_text_field($_GET['s']);
    }
    if (isset($_GET['status'])) {
        $filters['status'] = sanitize_text_field($_GET['status']);
    }
    if (isset($_GET['type'])) {
        $filters['type'] = sanitize_text_field($_GET['type']);
    }
    return $filters;
}

function wpvp_get_pagination_args() {
    // Get per page from screen options
    $user = get_current_user_id();
    $screen = get_current_screen();
    $per_page = 20; // Default
    
    if ($screen) {
        $screen_option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $screen_option, true);
        if (empty($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }
    }
    
    // Get query args with filters
    $args = wpvp_get_votes_query_args();
    $args['per_page'] = $per_page;
    
    // Add filters to count query
    if (isset($_GET['status']) && $_GET['status']) {
        $args['status'] = sanitize_text_field($_GET['status']);
    }
    if (isset($_GET['type']) && $_GET['type']) {
        $args['type'] = sanitize_text_field($_GET['type']);
    }
    
    $total_items = wpvp_get_total_votes_count($args);
    
    return array(
        'total_items' => $total_items,
        'total_pages' => ceil($total_items / $per_page),
        'per_page' => $per_page,
        'current_page' => $args['page']
    );
}

function wpvp_process_search_query($query) {
    if (empty($query)) {
        return '';
    }
    return sanitize_text_field($query);
}
