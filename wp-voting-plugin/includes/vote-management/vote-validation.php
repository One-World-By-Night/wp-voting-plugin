<?php

/**
 * File: includes/vote-management/vote-validation.php
 * Vote Validation Management Page
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

function wpvp_validate_vote_data($data) {
    $errors = array();

    if (empty($data['proposal_name'])) {
        $errors[] = __('Proposal name is required.', 'wp-voting-plugin');
    }

    if (empty($data['proposal_description'])) {
        $errors[] = __('Proposal description is required.', 'wp-voting-plugin');
    }

    if (!isset($data['voting_stage']) || !in_array($data['voting_stage'], array('draft', 'active', 'closed'))) {
        $errors[] = __('Invalid voting stage.', 'wp-voting-plugin');
    }

    if (!empty($data['created_by']) && !is_numeric($data['created_by'])) {
        $errors[] = __('Invalid creator ID.', 'wp-voting-plugin');
    }

    return $errors;
}