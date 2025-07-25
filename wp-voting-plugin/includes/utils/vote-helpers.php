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

function wpvp_get_vote_url($vote_id) {
    // For now, return the admin results page
    // Later this can be changed to a public voting page
    return admin_url('admin.php?page=wpvp-vote-results&id=' . $vote_id);
}

function wpvp_get_vote_status_badge($status) {
    return '<span>Status stub</span>';
}

function wpvp_get_vote_type_label($type) {
    $types = wpvp_get_vote_types();
    return isset($types[$type]) ? $types[$type] : $type;
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

