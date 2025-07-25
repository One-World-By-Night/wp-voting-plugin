<?php

/** File: includes/utils/vote-helpers.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;


function wpvp_format_vote_date($date) {
    return 'Date stub';
}

function wpvp_get_vote_url($action, $vote_id) {
    return '#';
}

function wpvp_get_vote_status_badge($status) {
    return '<span>Status stub</span>';
}

function wpvp_get_vote_type_label($type) {
    return 'Type stub';
}

function wpvp_can_edit_vote($vote) {
    return true;
}

function wpvp_can_delete_vote($vote) {
    return true;
}

function wpvp_can_view_results($vote) {
    return true;
}

