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
    return array(
        'total_items' => 0,
        'total_pages' => 1,
        'per_page' => 20,
        'current_page' => 1
    );
}

function wpvp_format_vote_date($date) {
    return 'Date stub';
}

function wpvp_get_vote_url($action, $vote_id) {
    return '#';
}

function wpvp_get_vote_type_label($type) {
    return 'Type stub';
}

function wpvp_get_vote_status_badge($status) {
    return '<span>Status stub</span>';
}

function wpvp_can_edit_vote($vote) {
    return true;
}

function wpvp_can_view_results($vote) {
    return true;
}

function wpvp_can_delete_vote($vote) {
    return true;
}

function wpvp_render_vote_actions($vote) {
    return '<div class="vote-actions">Actions stub</div>';
}