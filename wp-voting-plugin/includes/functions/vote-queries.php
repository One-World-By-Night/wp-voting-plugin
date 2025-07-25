<?php

/** File: includes/functions//vote-queries.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

function wpvp_get_votes_query_args() {
    $args = array(
        'per_page' => 20,
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
        'singleton' => __('Single Choice', 'wp-voting-plugin'),
        'rcv' => __('Ranked Choice Voting (RCV)', 'wp-voting-plugin'),
        'stv' => __('Single Transferable Vote (STV)', 'wp-voting-plugin'),
        'condorcet' => __('Condorcet Method', 'wp-voting-plugin'),
        'disciplinary' => __('Disciplinary Vote', 'wp-voting-plugin')
    );
}

/**
 * Get pre-defined options for specific vote types
 */
function wpvp_get_predefined_options($voting_type) {
    $predefined = array(
        'disciplinary' => array(
            array('text' => __('Condemnation', 'wp-voting-plugin'), 'description' => __('Least severe', 'wp-voting-plugin')),
            array('text' => __('Censure', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('Probation', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('1 Strike', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('2 Strikes', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('Temporary Ban', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('Indefinite Ban/3 Strikes', 'wp-voting-plugin'), 'description' => ''),
            array('text' => __('Permanent Ban', 'wp-voting-plugin'), 'description' => __('Most severe', 'wp-voting-plugin'))
        )
    );
    
    return isset($predefined[$voting_type]) ? $predefined[$voting_type] : false;
}

/**
 * Get vote type descriptions
 */
function wpvp_get_vote_type_descriptions() {
    return array(
        'singleton' => __('Voters select one option. Most votes wins.', 'wp-voting-plugin'),
        'rcv' => __('Voters rank options in order of preference. Uses instant runoff voting.', 'wp-voting-plugin'),
        'stv' => __('Multi-winner ranked choice voting. Select number of winners.', 'wp-voting-plugin'),
        'condorcet' => __('Finds candidate who would win all head-to-head matchups.', 'wp-voting-plugin'),
        'disciplinary' => __('Disciplinary action vote with fixed punishment levels from least to most severe.', 'wp-voting-plugin')
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
    $where = array();
    
    // Search condition
    if (!empty($args['search'])) {
        $query .= " LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID";
        $where[] = $wpdb->prepare("(v.proposal_name LIKE %s OR v.proposal_description LIKE %s)", 
            '%' . $args['search'] . '%', 
            '%' . $args['search'] . '%'
        );
    }
    
    // Status filter
    if (!empty($args['status'])) {
        $where[] = $wpdb->prepare("v.voting_stage = %s", $args['status']);
    }
    
    // Type filter
    if (!empty($args['type'])) {
        $where[] = $wpdb->prepare("v.voting_type = %s", $args['type']);
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }
    
    return $wpdb->get_var($query);
}