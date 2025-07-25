<?php

/** File: includes/admin/all-votes.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Display and manage all votes in admin
 */

defined('ABSPATH') || exit;

/**
 * Add screen options
 */
add_action('load-toplevel_page_wpvp-all-votes', 'wpvp_add_votes_screen_options');
function wpvp_add_votes_screen_options() {
    $option = 'per_page';
    $args = array(
        'label' => __('Votes per page', 'wp-voting-plugin'),
        'default' => 20,
        'option' => 'wpvp_votes_per_page'
    );
    add_screen_option($option, $args);
}

/**
 * Save screen options
 */
add_filter('set-screen-option', 'wpvp_set_votes_screen_options', 10, 3);
function wpvp_set_votes_screen_options($status, $option, $value) {
    if ('wpvp_votes_per_page' === $option) {
        return $value;
    }
    return $status;
}

/**
 * Render the main All Votes page
 * Core function that orchestrates the entire page rendering
 */
function wpvp_render_all_votes_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wp-voting-plugin'));
    }
    
    // Process bulk actions if any
    wpvp_process_vote_bulk_actions();
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('All Votes', 'wp-voting-plugin'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=wp-voting-plugin-new'); ?>" class="page-title-action"><?php _e('Add New', 'wp-voting-plugin'); ?></a>
        <hr class="wp-header-end">
        
        <form method="get">
            <input type="hidden" name="page" value="wpvp-all-votes">
            <?php wpvp_render_search_box(); ?>
            <?php wpvp_render_votes_filters(); ?>
            <?php wpvp_render_votes_table(); ?>
        </form>
    </div>
    <?php
}

/**
 * Process bulk actions
 */
function wpvp_process_vote_bulk_actions() {
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] === '-1') {
        return;
    }
    
    if (!isset($_REQUEST['vote']) || !is_array($_REQUEST['vote'])) {
        return;
    }
    
    $action = $_REQUEST['action'];
    $vote_ids = array_map('intval', $_REQUEST['vote']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpvp_votes';
    
    switch ($action) {
        case 'delete':
            foreach ($vote_ids as $vote_id) {
                $wpdb->delete($table_name, array('id' => $vote_id));
                // Also delete related ballots and results
                $wpdb->delete($wpdb->prefix . 'wpvp_ballots', array('vote_id' => $vote_id));
                $wpdb->delete($wpdb->prefix . 'wpvp_results', array('vote_id' => $vote_id));
            }
            break;
        case 'activate':
            foreach ($vote_ids as $vote_id) {
                $wpdb->update($table_name, array('voting_stage' => 'open'), array('id' => $vote_id));
            }
            break;
        case 'deactivate':
            foreach ($vote_ids as $vote_id) {
                $wpdb->update($table_name, array('voting_stage' => 'closed'), array('id' => $vote_id));
            }
            break;
    }
}

/**
 * Get sorting parameters
 */
function wpvp_get_sort_params() {
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
    
    // Validate orderby
    $allowed_orderby = array('proposal_name', 'voting_type', 'voting_stage', 'created_at');
    if (!in_array($orderby, $allowed_orderby)) {
        $orderby = 'created_at';
    }
    
    // Validate order
    $order = strtoupper($order);
    if (!in_array($order, array('ASC', 'DESC'))) {
        $order = 'DESC';
    }
    
    return array('orderby' => $orderby, 'order' => $order);
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
                <option value="draft" <?php selected($current_status, 'draft'); ?>><?php _e('Draft', 'wp-voting-plugin'); ?></option>
                <option value="open" <?php selected($current_status, 'open'); ?>><?php _e('Open', 'wp-voting-plugin'); ?></option>
                <option value="closed" <?php selected($current_status, 'closed'); ?>><?php _e('Closed', 'wp-voting-plugin'); ?></option>
                <option value="archived" <?php selected($current_status, 'archived'); ?>><?php _e('Archived', 'wp-voting-plugin'); ?></option>
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
    // Get per page option
    $user = get_current_user_id();
    $screen = get_current_screen();
    $screen_option = $screen->get_option('per_page', 'option');
    $per_page = get_user_meta($user, $screen_option, true);
    if (empty($per_page) || $per_page < 1) {
        $per_page = $screen->get_option('per_page', 'default');
    }
    
    // Update query args with per page
    $args = wpvp_get_votes_query_args();
    $args['per_page'] = $per_page;
    
    // Get sort params
    $sort_params = wpvp_get_sort_params();
    $args['orderby'] = $sort_params['orderby'];
    $args['order'] = $sort_params['order'];
    
    // Add filters
    if (isset($_GET['status']) && $_GET['status']) {
        $args['status'] = sanitize_text_field($_GET['status']);
    }
    if (isset($_GET['type']) && $_GET['type']) {
        $args['type'] = sanitize_text_field($_GET['type']);
    }
    
    $votes = wpvp_get_votes($args);
    
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
    $sort_params = wpvp_get_sort_params();
    $current_orderby = $sort_params['orderby'];
    $current_order = $sort_params['order'];
    
    // Function to create sortable column header
    $sortable_header = function($column, $label) use ($current_orderby, $current_order) {
        $is_current = ($current_orderby === $column);
        $order = ($is_current && $current_order === 'ASC') ? 'DESC' : 'ASC';
        $class = $is_current ? "sorted " . strtolower($current_order) : 'sortable';
        
        $url = add_query_arg(array(
            'orderby' => $column,
            'order' => $order
        ));
        
        return sprintf(
            '<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
            esc_url($url),
            esc_html($label)
        );
    };
    
    ?>
    <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <input type="checkbox" id="cb-select-all">
            </td>
            <th scope="col" class="manage-column column-title column-primary <?php echo $current_orderby === 'proposal_name' ? 'sorted ' . strtolower($current_order) : 'sortable'; ?>">
                <?php echo $sortable_header('proposal_name', __('Title', 'wp-voting-plugin')); ?>
            </th>
            <th scope="col" class="manage-column column-type <?php echo $current_orderby === 'voting_type' ? 'sorted ' . strtolower($current_order) : 'sortable'; ?>">
                <?php echo $sortable_header('voting_type', __('Type', 'wp-voting-plugin')); ?>
            </th>
            <th scope="col" class="manage-column column-status <?php echo $current_orderby === 'voting_stage' ? 'sorted ' . strtolower($current_order) : 'sortable'; ?>">
                <?php echo $sortable_header('voting_stage', __('Status', 'wp-voting-plugin')); ?>
            </th>
            <th scope="col" class="manage-column column-responses">
                <?php _e('Responses', 'wp-voting-plugin'); ?>
            </th>
            <th scope="col" class="manage-column column-date <?php echo $current_orderby === 'created_at' ? 'sorted ' . strtolower($current_order) : 'sortable'; ?>">
                <?php echo $sortable_header('created_at', __('Date', 'wp-voting-plugin')); ?>
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
    // Get ballot count for this vote
    global $wpdb;
    $ballot_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wpvp_ballots WHERE vote_id = %d",
        $vote->id
    ));
    
    ?>
    <tr>
        <th scope="row" class="check-column">
            <input type="checkbox" name="vote[]" value="<?php echo esc_attr($vote->id); ?>">
        </th>
        <td class="title column-title has-row-actions column-primary">
            <strong>
                <a href="<?php echo esc_url(wpvp_get_vote_url($vote->id)); ?>">
                    <?php echo esc_html($vote->proposal_name); ?>
                </a>
            </strong>
            <div class="row-actions">
                <?php echo wpvp_render_vote_actions($vote); ?>
            </div>
            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details', 'wp-voting-plugin'); ?></span></button>
        </td>
        <td data-colname="<?php _e('Type', 'wp-voting-plugin'); ?>">
            <?php echo esc_html(wpvp_get_vote_type_label($vote->voting_type)); ?>
        </td>
        <td data-colname="<?php _e('Status', 'wp-voting-plugin'); ?>">
            <?php echo wpvp_get_vote_status_badge($vote->voting_stage); ?>
        </td>
        <td data-colname="<?php _e('Responses', 'wp-voting-plugin'); ?>">
            <?php echo esc_html($ballot_count); ?>
        </td>
        <td data-colname="<?php _e('Date', 'wp-voting-plugin'); ?>">
            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($vote->created_at))); ?>
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
    $search = wpvp_process_search_query(isset($_GET['s']) ? $_GET['s'] : '');
    ?>
    <p class="search-box">
        <label class="screen-reader-text" for="wpvp-search-input"><?php _e('Search Votes:', 'wp-voting-plugin'); ?></label>
        <input type="search" id="wpvp-search-input" name="s" value="<?php echo esc_attr($search); ?>">
        <input type="submit" class="button" value="<?php esc_attr_e('Search Votes', 'wp-voting-plugin'); ?>">
    </p>
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
            <option value="-1"><?php _e('Bulk Actions', 'wp-voting-plugin'); ?></option>
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