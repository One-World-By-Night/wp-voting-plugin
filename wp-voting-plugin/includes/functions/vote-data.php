<?php

/** File: includes/functions//vote-data.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

// Helper function stubs
function wpvp_process_search_query() {
    return isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
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

function wpvp_render_vote_actions($vote) {
    $actions = array();
    
    // Edit
    $actions['edit'] = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=wpvp-edit-vote&id=' . $vote->id),
        __('Edit', 'wp-voting-plugin')
    );
    
    // View Results
    $actions['results'] = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=wpvp-vote-results&id=' . $vote->id),
        __('Results', 'wp-voting-plugin')
    );
    
    // Delete
    $actions['delete'] = sprintf(
        '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
        wp_nonce_url(admin_url('admin.php?page=wpvp-all-votes&action=delete&id=' . $vote->id), 'delete-vote'),
        __('Are you sure?', 'wp-voting-plugin'),
        __('Delete', 'wp-voting-plugin')
    );
    
    return implode(' | ', $actions);
}