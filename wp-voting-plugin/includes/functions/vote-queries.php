<?php

/** File: includes/functions//vote-queries.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

function wpvp_get_votes_query_args() {
    $args = array(
        'per_page' => 4,
        'page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
        'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
        'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
        'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
    );

    return $args;
}

function wpvp_get_votes($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpvp_votes';
    
    $args = wp_parse_args($args, wpvp_get_votes_query_args());
    
    $offset = ($args['page'] - 1) * $args['per_page'];
    
    $query = "SELECT v.*, u.display_name as creator_name 
              FROM $table_name v
              LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID";
    
    $where = array();
    if (!empty($args['search'])) {
        $where[] = $wpdb->prepare("(v.proposal_name LIKE %s OR v.proposal_description LIKE %s)", 
            '%' . $args['search'] . '%', 
            '%' . $args['search'] . '%'
        );
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    $query .= " ORDER BY v.{$args['orderby']} {$args['order']}";
    $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);
    
    return $wpdb->get_results($query);
}

function wpvp_get_vote_count_by_status($status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpvp_votes';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE voting_stage = %s",
        $status
    ));
}

function wpvp_get_vote_types() {
    return array(
        'single' => __('Single Choice', 'wp-voting-plugin'),
        'multiple' => __('Multiple Choice', 'wp-voting-plugin'),
        'ranked' => __('Ranked Choice', 'wp-voting-plugin'),
        'condorcet' => __('Condorcet', 'wp-voting-plugin')
    );
}

function wpvp_get_vote_statuses() {
    return array(
        'draft' => __('Draft', 'wp-voting-plugin'),
        'open' => __('Open', 'wp-voting-plugin'),
        'closed' => __('Closed', 'wp-voting-plugin'),
        'archived' => __('Archived', 'wp-voting-plugin')
    );
}

function wpvp_get_total_votes_count($args = array()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpvp_votes';
    
    $query = "SELECT COUNT(*) FROM $table_name v";
    
    if (!empty($args['search'])) {
        $query .= $wpdb->prepare(" WHERE v.proposal_name LIKE %s OR v.proposal_description LIKE %s", 
            '%' . $args['search'] . '%', 
            '%' . $args['search'] . '%'
        );
    }
    
    return $wpdb->get_var($query);
}