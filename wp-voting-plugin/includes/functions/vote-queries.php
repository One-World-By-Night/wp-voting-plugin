<?php

/** File: includes/functions//vote-queries.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

function wpvp_get_votes_query_args() {
    $args = array(
        'post_type' => 'vote',
        'posts_per_page' => 20,
        'paged' => get_query_var('paged', 1),
    );

    if (isset($_GET['s'])) {
        $args['s'] = sanitize_text_field($_GET['s']);
    }

    return $args;
}

function wpvp_get_votes($args = array()) {
    $query_args = wp_parse_args($args, wpvp_get_votes_query_args());
    $query = new WP_Query($query_args);

    if (!$query->have_posts()) {
        return array();
    }

    return $query->posts;
}

function wpvp_get_vote_count_by_status($status) {
    $args = array(
        'post_type' => 'vote',
        'post_status' => $status,
        'fields' => 'ids',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    return count($query->posts);
}

function wpvp_get_vote_types() {
    return array(
        'single' => 'Single Choice',
        'multiple' => 'Multiple Choice',
        'rating' => 'Rating'
    );
}

function wpvp_get_vote_statuses() {
    return array(
        'publish' => __('Published', 'wp-voting-plugin'),
        'draft' => __('Draft', 'wp-voting-plugin'),
        'pending' => __('Pending Review', 'wp-voting-plugin'),
        'open' => __('Open', 'wp-voting-plugin'),
        'closed' => __('Closed', 'wp-voting-plugin'),
        'trash' => __('Trash', 'wp-voting-plugin')
    );
}