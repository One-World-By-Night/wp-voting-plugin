<?php

/** File: includes/core/deactivation.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 */

defined('ABSPATH') || exit;

/**
 * Remove pages when plugin is deactivated from Wordpress This is called by WP_Deregister_Plugin
 */
function wpvotingplugin_deactivate_plugin()
{
    $pages = ['owbn-personal-dashboard', 'owbn-voting-form', 'owbn-preview-vote', 'owbn-voting-box', 'owbn-elections-dashboard', 'owbn-voting-result'];
    foreach ($pages as $slug) {
        $page = get_page_by_path($slug);
        // Delete the post from the page.
        if ($page) {
            wp_delete_post($page->ID);
        }
    }
}
register_deactivation_hook(__FILE__, 'wpvotingplugin_deactivate_plugin');
