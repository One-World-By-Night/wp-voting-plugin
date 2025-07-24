<?php

/** File: includes/admin/all-votes.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Display and manage all votes in admin
 */

defined('ABSPATH') || exit;

/**
 * Render the main All Votes page
 * Core function that orchestrates the entire page rendering
 */
function wpvp_render_all_votes_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wp-voting-plugin'));
    }
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('All Votes', 'wp-voting-plugin'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=wp-voting-plugin-new'); ?>" class="page-title-action"><?php _e('Add New', 'wp-voting-plugin'); ?></a>
        <hr class="wp-header-end">
        
        <?php wpvp_render_search_box(); ?>
        <?php wpvp_render_votes_filters(); ?>
        <?php wpvp_render_votes_table(); ?>
    </div>
    <?php
}

/**
 * Render vote filters
 * Status, type, and date filters
 */
function wpvp_render_votes_filters() {
    $current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $current_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    
    ?>
    <div class="wpvp-filters">
        <div class="alignleft actions">
            <select name="status" id="wpvp-status-filter">
                <option value=""><?php _e('All Statuses', 'wp-voting-plugin'); ?></option>
                <option value="active" <?php selected($current_status, 'active'); ?>><?php _e('Active', 'wp-voting-plugin'); ?></option>
                <option value="inactive" <?php selected($current_status, 'inactive'); ?>><?php _e('Inactive', 'wp-voting-plugin'); ?></option>
                <option value="draft" <?php selected($current_status, 'draft'); ?>><?php _e('Draft', 'wp-voting-plugin'); ?></option>
            </select>
            
            <select name="type" id="wpvp-type-filter">
                <option value=""><?php _e('All Types', 'wp-voting-plugin'); ?></option>
                <?php foreach (wpvp_get_vote_types() as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_type, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'wp-voting-plugin'); ?>">
        </div>
        
        <?php wpvp_render_bulk_actions(); ?>
    </div>
    <br class="clear">
    <?php
}

/**
 * Render the votes table
 * Main table with all votes
 */
function wpvp_render_votes_table() {
    $votes = wpvp_get_votes(wpvp_get_votes_query_args());
    
    ?>
    <table class="wp-list-table widefat fixed striped wpvp-votes-table">
        <?php wpvp_render_votes_table_header(); ?>
        <tbody>
            <?php 
            if (!empty($votes)) {
                foreach ($votes as $vote) {
                    wpvp_render_votes_table_row($vote);
                }
            } else {
                wpvp_render_votes_table_empty();
            }
            ?>
        </tbody>
    </table>
    
    <?php wpvp_render_pagination(); ?>
    <?php
}

/**
 * Render table header
 * Column headers with sorting
 */
function wpvp_render_votes_table_header() {
    ?>
    <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <input type="checkbox" id="cb-select-all">
            </td>
            <th scope="col" class="manage-column column-title column-primary">
                <?php _e('Title', 'wp-voting-plugin'); ?>
            </th>
            <th scope="col" class="manage-column column-type">
                <?php _e('Type', 'wp-voting-plugin'); ?>
            </th>
            <th scope="col" class="manage-column column-status">
                <?php _e('Status', 'wp-voting-plugin'); ?>
            </th>
            <th scope="col" class="manage-column column-responses">
                <?php _e('Responses', 'wp-voting-plugin'); ?>
            </th>
            <th scope="col" class="manage-column column-date">
                <?php _e('Date', 'wp-voting-plugin'); ?>
            </th>
        </tr>
    </thead>
    <?php
}

/**
 * Render a single table row
 * Individual vote row with actions
 */
function wpvp_render_votes_table_row($vote) {
    ?>
    <tr data-vote-id="<?php echo esc_attr($vote->id); ?>">
        <th scope="row" class="check-column">
            <input type="checkbox" name="votes[]" value="<?php echo esc_attr($vote->id); ?>">
        </th>
        <td class="title column-title has-row-actions column-primary">
            <strong>
                <a href="<?php echo wpvp_get_vote_url('edit', $vote->id); ?>" class="row-title">
                    <?php echo esc_html($vote->title); ?>
                </a>
            </strong>
            <?php wpvp_render_row_actions($vote); ?>
        </td>
        <td class="type column-type">
            <?php echo wpvp_get_vote_type_label($vote->type); ?>
        </td>
        <td class="status column-status">
            <?php echo wpvp_get_vote_status_badge($vote->status); ?>
        </td>
        <td class="responses column-responses">
            <a href="<?php echo wpvp_get_vote_url('results', $vote->id); ?>">
                <?php echo number_format_i18n($vote->response_count); ?>
            </a>
        </td>
        <td class="date column-date">
            <?php echo wpvp_format_vote_date($vote->created_at); ?>
        </td>
    </tr>
    <?php
}

/**
 * Render empty table state
 * Message when no votes found
 */
function wpvp_render_votes_table_empty() {
    ?>
    <tr>
        <td colspan="6" class="no-items">
            <?php _e('No votes found.', 'wp-voting-plugin'); ?>
            <a href="<?php echo admin_url('admin.php?page=wp-voting-plugin-new'); ?>">
                <?php _e('Create your first vote', 'wp-voting-plugin'); ?>
            </a>
        </td>
    </tr>
    <?php
}

/**
 * Render search box
 * Search functionality for votes
 */
function wpvp_render_search_box() {
    $search = wpvp_process_search_query();
    ?>
    <form method="get" class="search-form">
        <input type="hidden" name="page" value="wp-voting-plugin">
        <p class="search-box">
            <label class="screen-reader-text" for="wpvp-search-input"><?php _e('Search Votes:', 'wp-voting-plugin'); ?></label>
            <input type="search" id="wpvp-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Search Votes', 'wp-voting-plugin'); ?>">
        </p>
    </form>
    <?php
}

/**
 * Render pagination
 * Page navigation controls
 */
function wpvp_render_pagination() {
    $pagination_args = wpvp_get_pagination_args();
    
    if ($pagination_args['total_pages'] <= 1) {
        return;
    }
    
    ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(
                    _n('%s item', '%s items', $pagination_args['total_items'], 'wp-voting-plugin'),
                    number_format_i18n($pagination_args['total_items'])
                ); ?>
            </span>
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;', 'wp-voting-plugin'),
                'next_text' => __('&raquo;', 'wp-voting-plugin'),
                'total' => $pagination_args['total_pages'],
                'current' => $pagination_args['current_page']
            ));
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render bulk actions
 * Dropdown for bulk operations
 */
function wpvp_render_bulk_actions() {
    ?>
    <div class="alignleft actions bulkactions">
        <select name="action" id="wpvp-bulk-action-selector">
            <option value=""><?php _e('Bulk Actions', 'wp-voting-plugin'); ?></option>
            <option value="activate"><?php _e('Activate', 'wp-voting-plugin'); ?></option>
            <option value="deactivate"><?php _e('Deactivate', 'wp-voting-plugin'); ?></option>
            <option value="delete"><?php _e('Delete', 'wp-voting-plugin'); ?></option>
        </select>
        <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'wp-voting-plugin'); ?>">
    </div>
    <?php
}

/**
 * Render row actions
 * Individual vote actions (edit, clone, delete, etc.)
 */
function wpvp_render_row_actions($vote) {
    $actions = array();
    
    if (wpvp_can_edit_vote($vote)) {
        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
            wpvp_get_vote_url('edit', $vote->id),
            __('Edit', 'wp-voting-plugin')
        );
    }
    
    if (wpvp_can_view_results($vote)) {
        $actions['results'] = sprintf(
            '<a href="%s">%s</a>',
            wpvp_get_vote_url('results', $vote->id),
            __('Results', 'wp-voting-plugin')
        );
    }
    
    $actions['clone'] = sprintf(
        '<a href="#" class="wpvp-clone-vote" data-vote-id="%d">%s</a>',
        $vote->id,
        __('Clone', 'wp-voting-plugin')
    );
    
    if (wpvp_can_delete_vote($vote)) {
        $actions['delete'] = sprintf(
            '<a href="#" class="wpvp-delete-vote" data-vote-id="%d">%s</a>',
            $vote->id,
            __('Delete', 'wp-voting-plugin')
        );
    }
    
    ?>
    <div class="row-actions">
        <?php echo implode(' | ', $actions); ?>
    </div>
    <?php
}