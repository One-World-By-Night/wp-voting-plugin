<?php

/** File: includes/core/activation.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

/**
 * Create pages on plugin activation and activate them on first page load ( for admin only ) This function is hooked into wpvotingplugin_init
 */
function wpvotingplugin_activate_plugin()
{
    create_voting_table();
    create_personal_dashboard_page();
    create_elections_dashboard_page();
    create_vote_page();
    create_preview_vote_page();
    create_voting_box_page();
    create_voting_result_page();
}
register_activation_hook(__FILE__, 'wpvotingplugin_activate_plugin');
