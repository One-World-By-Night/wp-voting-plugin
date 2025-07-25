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
    $args = wpvp_get_votes_query_args();
    $total_items = wpvp_get_total_votes_count($args);
    $per_page = $args['per_page'];
    
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
