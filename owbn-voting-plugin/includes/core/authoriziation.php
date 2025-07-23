<?php

/** File: includes/core/authorization.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Handle user authorization for voting actions
 */

defined('ABSPATH') || exit;

/**
 * Check if user can create votes
 * @param int $user_id User ID (default current user)
 * @return bool
 */
function wpvp_user_can_create_votes($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $mode = get_option('wpvp_permission_mode', 'wordpress');

    switch ($mode) {
        case 'accessschema':
            return wpvp_check_accessschema_permission($user_id, 'create_votes');

        case 'custom':
            // Custom mode - only admins can create
            return user_can($user_id, 'manage_options');

        case 'wordpress':
        default:
            $capability = get_option('wpvp_capabilities', [])['create_votes'] ?? 'edit_posts';
            return user_can($user_id, $capability);
    }
}

/**
 * Check if user can cast votes
 * @param int $user_id User ID (default current user)
 * @param int $vote_id Specific vote ID to check
 * @return bool
 */
function wpvp_user_can_vote($user_id = null, $vote_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Check if already voted (if vote_id provided)
    if ($vote_id && wpvp_user_has_voted($user_id, $vote_id)) {
        return false;
    }

    $mode = get_option('wpvp_permission_mode', 'wordpress');

    switch ($mode) {
        case 'accessschema':
            return wpvp_check_accessschema_permission($user_id, 'cast_votes', $vote_id);

        case 'custom':
            $allowed_users = get_option('wpvp_can_vote_users', []);
            return in_array($user_id, $allowed_users);

        case 'wordpress':
        default:
            $capability = get_option('wpvp_capabilities', [])['cast_votes'] ?? 'read';
            return user_can($user_id, $capability);
    }
}

/**
 * Check if user can view results
 * @param int $user_id User ID
 * @param int $vote_id Vote ID
 * @return bool
 */
function wpvp_user_can_view_results($user_id = null, $vote_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    // Get vote data
    if ($vote_id) {
        global $wpdb;
        $vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d",
            $vote_id
        ), ARRAY_A);

        if ($vote) {
            // Check visibility settings
            if ($vote['visibility'] === 'public') {
                return true;
            }

            // Check if vote creator
            if ($user_id && $vote['created_by'] == $user_id) {
                return true;
            }

            // Check if voting is completed
            if ($vote['voting_stage'] === 'completed') {
                return wpvp_user_can_vote($user_id, $vote_id) || user_can($user_id, 'manage_options');
            }

            // Check settings for showing results before closing
            $settings = get_option('wpvp_settings', []);
            if (!empty($settings['show_results_before_closing'])) {
                return wpvp_user_can_vote($user_id, $vote_id);
            }
        }
    }

    // Default to capability check
    $capability = get_option('wpvp_capabilities', [])['view_results'] ?? 'read';
    return $user_id ? user_can($user_id, $capability) : false;
}

/**
 * Check if user can manage all votes
 * @param int $user_id User ID
 * @return bool
 */
function wpvp_user_can_manage_votes($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Admins always can
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    $mode = get_option('wpvp_permission_mode', 'wordpress');

    if ($mode === 'accessschema') {
        return wpvp_check_accessschema_permission($user_id, 'manage_votes');
    }

    $capability = get_option('wpvp_capabilities', [])['manage_all_votes'] ?? 'manage_options';
    return user_can($user_id, $capability);
}

/**
 * Check if user has already voted
 * @param int $user_id User ID
 * @param int $vote_id Vote ID
 * @return bool
 */
function wpvp_user_has_voted($user_id, $vote_id)
{
    global $wpdb;

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wpvp_ballots 
        WHERE vote_id = %d AND user_id = %d",
        $vote_id,
        $user_id
    ));

    return $exists > 0;
}

/**
 * Check AccessSchema permission
 * @param int $user_id User ID
 * @param string $action Action to check
 * @param int $vote_id Optional vote ID for context
 * @return bool
 */
function wpvp_check_accessschema_permission($user_id, $action, $vote_id = null)
{
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $prefix = 'wpvp';
    $mode = get_option("{$prefix}_accessschema_mode", 'none');

    if ($mode === 'none') {
        // Fall back to WordPress permissions
        $capability = get_option('wpvp_capabilities', [])[$action] ?? 'read';
        return user_can($user_id, $capability);
    }

    // Get role path for this action
    $capability_map = get_option("{$prefix}_capability_map", []);
    $role_path = $capability_map[$action] ?? '';

    if (empty($role_path)) {
        return false;
    }

    // Expand dynamic placeholders
    if ($vote_id && strpos($role_path, '$vote_id') !== false) {
        $role_path = str_replace('$vote_id', $vote_id, $role_path);
    }

    // Use AccessSchema check
    if (function_exists('accessSchema_client_remote_check_access')) {
        $granted = accessSchema_client_remote_check_access(
            $user->user_email,
            $role_path,
            $prefix
        );

        return $granted === true;
    }

    // If AccessSchema functions not available, check for asc_has_access_to_group capability
    if (strpos($role_path, '*') !== false) {
        // Wildcard check
        $group_path = str_replace('/*', '', $role_path);
        return user_can($user_id, 'asc_has_access_to_group', $group_path);
    }

    return false;
}

/**
 * Add custom capabilities to WordPress
 */
function wpvp_add_capabilities()
{
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('wpvp_create_votes');
        $role->add_cap('wpvp_manage_votes');
        $role->add_cap('wpvp_cast_votes');
        $role->add_cap('wpvp_view_results');
    }

    // Add to other roles based on settings
    $capabilities = get_option('wpvp_capabilities', []);

    // Create votes capability
    if (!empty($capabilities['create_votes'])) {
        $min_cap = $capabilities['create_votes'];

        if ($min_cap === 'edit_posts') {
            foreach (['editor', 'author'] as $role_name) {
                $role = get_role($role_name);
                if ($role) $role->add_cap('wpvp_create_votes');
            }
        } elseif ($min_cap === 'edit_others_posts') {
            $role = get_role('editor');
            if ($role) $role->add_cap('wpvp_create_votes');
        }
    }

    // Cast votes capability
    if (!empty($capabilities['cast_votes'])) {
        $min_cap = $capabilities['cast_votes'];

        if ($min_cap === 'read') {
            foreach (['editor', 'author', 'contributor', 'subscriber'] as $role_name) {
                $role = get_role($role_name);
                if ($role) $role->add_cap('wpvp_cast_votes');
            }
        }
    }
}

// Add capabilities on activation
register_activation_hook(WPVP_PLUGIN_FILE, 'wpvp_add_capabilities');

/**
 * Helper function to check any voting permission
 * @param string $action Action to check
 * @param int $user_id User ID (optional)
 * @param int $vote_id Vote ID (optional)
 * @return bool
 */
function wpvp_check_permission($action, $user_id = null, $vote_id = null)
{
    switch ($action) {
        case 'create':
            return wpvp_user_can_create_votes($user_id);

        case 'vote':
        case 'cast':
            return wpvp_user_can_vote($user_id, $vote_id);

        case 'view':
        case 'results':
            return wpvp_user_can_view_results($user_id, $vote_id);

        case 'manage':
            return wpvp_user_can_manage_votes($user_id);

        default:
            return false;
    }
}

/**
 * Get permission error message
 * @param string $action Action that was denied
 * @return string
 */
function wpvp_get_permission_error($action)
{
    $messages = [
        'create' => __('You do not have permission to create votes.', 'wp-voting-plugin'),
        'vote' => __('You do not have permission to cast votes.', 'wp-voting-plugin'),
        'view' => __('You do not have permission to view these results.', 'wp-voting-plugin'),
        'manage' => __('You do not have permission to manage votes.', 'wp-voting-plugin'),
        'already_voted' => __('You have already voted in this election.', 'wp-voting-plugin')
    ];

    return $messages[$action] ?? __('You do not have permission to perform this action.', 'wp-voting-plugin');
}
