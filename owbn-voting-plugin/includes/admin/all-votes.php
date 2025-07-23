<?php

/** File: includes/admin/all-votes.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Display and manage all votes in admin
 */

defined('ABSPATH') || exit;

/**
 * Render all votes page
 */
function wpvp_render_all_votes_page()
{
    // Check permissions
    if (!wpvp_user_can_manage_votes()) {
        wp_die(wpvp_get_permission_error('manage'));
    }

    // Handle actions
    if (isset($_GET['action']) && isset($_GET['vote_id'])) {
        wpvp_handle_vote_action($_GET['action'], intval($_GET['vote_id']));
    }

?>
    <div class="wrap">
        <h1>
            <?php _e('All Votes', 'wp-voting-plugin'); ?>
            <?php if (wpvp_user_can_create_votes()): ?>
                <a href="<?php echo admin_url('admin.php?page=wpvp-create-vote'); ?>" class="page-title-action">
                    <?php _e('Add New', 'wp-voting-plugin'); ?>
                </a>
            <?php endif; ?>
        </h1>

        <?php wpvp_display_admin_notices(); ?>

        <form method="get">
            <input type="hidden" name="page" value="wpvp-all-votes">
            <?php
            $list_table = new WPVP_Votes_List_Table();
            $list_table->prepare_items();
            $list_table->search_box(__('Search Votes', 'wp-voting-plugin'), 'vote');
            $list_table->display();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Handle vote actions
 */
function wpvp_handle_vote_action($action, $vote_id)
{
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wpvp_vote_action')) {
        wp_die(__('Security check failed', 'wp-voting-plugin'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wpvp_votes';

    switch ($action) {
        case 'delete':
            // Delete vote and related data
            $wpdb->delete($table, ['id' => $vote_id], ['%d']);
            $wpdb->delete($wpdb->prefix . 'wpvp_ballots', ['vote_id' => $vote_id], ['%d']);
            $wpdb->delete($wpdb->prefix . 'wpvp_results', ['vote_id' => $vote_id], ['%d']);

            wp_redirect(add_query_arg([
                'page' => 'wpvp-all-votes',
                'deleted' => 1
            ], admin_url('admin.php')));
            exit;

        case 'close':
            $wpdb->update(
                $table,
                ['voting_stage' => 'completed', 'closing_date' => current_time('mysql')],
                ['id' => $vote_id],
                ['%s', '%s'],
                ['%d']
            );

            // Process results
            wpvp_process_vote_results($vote_id);

            wp_redirect(add_query_arg([
                'page' => 'wpvp-all-votes',
                'closed' => 1
            ], admin_url('admin.php')));
            exit;

        case 'open':
            $wpdb->update(
                $table,
                ['voting_stage' => 'open', 'opening_date' => current_time('mysql')],
                ['id' => $vote_id],
                ['%s', '%s'],
                ['%d']
            );

            wp_redirect(add_query_arg([
                'page' => 'wpvp-all-votes',
                'opened' => 1
            ], admin_url('admin.php')));
            exit;
    }
}

/**
 * Display admin notices
 */
function wpvp_display_admin_notices()
{
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
            __('Vote deleted successfully.', 'wp-voting-plugin') . '</p></div>';
    }

    if (isset($_GET['closed'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
            __('Vote closed and results calculated.', 'wp-voting-plugin') . '</p></div>';
    }

    if (isset($_GET['opened'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
            __('Vote opened successfully.', 'wp-voting-plugin') . '</p></div>';
    }
}

/**
 * Process vote results (wrapper for vote processing)
 */
function wpvp_process_vote_results($vote_id)
{
    global $wpdb;

    // Get vote data
    $vote = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d",
        $vote_id
    ), ARRAY_A);

    if (!$vote) {
        return false;
    }

    // Get ballots
    $ballots = wpvp_get_ballots_for_processing($vote_id);

    // Get voting options
    $voting_options = json_decode($vote['voting_options'], true);

    // Process votes
    $results = wpvp_process_votes([
        'voting_choice' => $vote['voting_type'],
        'number_of_winner' => $vote['number_of_winners']
    ], $ballots, $voting_options);

    // Save results
    return wpvp_save_vote_results($vote_id, $results);
}

/**
 * Votes List Table Class
 */
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WPVP_Votes_List_Table extends WP_List_Table
{

    public function __construct()
    {
        parent::__construct([
            'singular' => __('Vote', 'wp-voting-plugin'),
            'plural' => __('Votes', 'wp-voting-plugin'),
            'ajax' => false
        ]);
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'proposal_name' => __('Vote Name', 'wp-voting-plugin'),
            'voting_type' => __('Type', 'wp-voting-plugin'),
            'voting_stage' => __('Status', 'wp-voting-plugin'),
            'votes_cast' => __('Votes Cast', 'wp-voting-plugin'),
            'dates' => __('Dates', 'wp-voting-plugin'),
            'created_by' => __('Created By', 'wp-voting-plugin')
        ];
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = 20;
        $current_page = $this->get_pagenum();

        // Build query
        $query = "SELECT v.*, 
                  (SELECT COUNT(*) FROM {$wpdb->prefix}wpvp_ballots WHERE vote_id = v.id) as vote_count
                  FROM {$wpdb->prefix}wpvp_votes v";

        $where = [];

        // Search
        if (!empty($_REQUEST['s'])) {
            $search = '%' . $wpdb->esc_like($_REQUEST['s']) . '%';
            $where[] = $wpdb->prepare("proposal_name LIKE %s", $search);
        }

        // Filter by status
        if (!empty($_REQUEST['status'])) {
            $where[] = $wpdb->prepare("voting_stage = %s", $_REQUEST['status']);
        }

        if ($where) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        // Ordering
        $orderby = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'created_at';
        $order = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC';
        $query .= " ORDER BY $orderby $order";

        // Pagination
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_query");

        $query .= " LIMIT $per_page";
        $query .= ' OFFSET ' . ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'voting_type':
                $types = [
                    'single' => __('Single Choice', 'wp-voting-plugin'),
                    'irv' => __('Instant Runoff', 'wp-voting-plugin'),
                    'stv' => __('STV', 'wp-voting-plugin')
                ];
                return $types[$item[$column_name]] ?? $item[$column_name];

            case 'voting_stage':
                return '<span class="status-' . $item[$column_name] . '">' .
                    ucfirst($item[$column_name]) . '</span>';

            case 'votes_cast':
                return $item['vote_count'];

            case 'dates':
                $output = __('Created:', 'wp-voting-plugin') . ' ' .
                    date_i18n(get_option('date_format'), strtotime($item['created_at']));

                if ($item['opening_date']) {
                    $output .= '<br>' . __('Opens:', 'wp-voting-plugin') . ' ' .
                        date_i18n(get_option('date_format'), strtotime($item['opening_date']));
                }

                if ($item['closing_date']) {
                    $output .= '<br>' . __('Closes:', 'wp-voting-plugin') . ' ' .
                        date_i18n(get_option('date_format'), strtotime($item['closing_date']));
                }

                return $output;

            case 'created_by':
                $user = get_userdata($item[$column_name]);
                return $user ? $user->display_name : __('Unknown', 'wp-voting-plugin');

            default:
                return $item[$column_name];
        }
    }

    public function column_proposal_name($item)
    {
        $actions = [];

        // View results
        if (wpvp_user_can_view_results(null, $item['id'])) {
            $actions['view'] = sprintf(
                '<a href="%s">%s</a>',
                get_permalink(get_option('wpvp_page_ids')['vote-results']) . '?vote_id=' . $item['id'],
                __('View Results', 'wp-voting-plugin')
            );
        }

        // Edit
        if (wpvp_user_can_manage_votes() || $item['created_by'] == get_current_user_id()) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=wpvp-edit-vote&vote_id=' . $item['id']),
                __('Edit', 'wp-voting-plugin')
            );
        }

        // Close/Open
        if (wpvp_user_can_manage_votes()) {
            if ($item['voting_stage'] === 'open') {
                $actions['close'] = sprintf(
                    '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                    wp_nonce_url(
                        admin_url('admin.php?page=wpvp-all-votes&action=close&vote_id=' . $item['id']),
                        'wpvp_vote_action'
                    ),
                    __('Are you sure you want to close this vote?', 'wp-voting-plugin'),
                    __('Close Vote', 'wp-voting-plugin')
                );
            } elseif ($item['voting_stage'] === 'draft') {
                $actions['open'] = sprintf(
                    '<a href="%s">%s</a>',
                    wp_nonce_url(
                        admin_url('admin.php?page=wpvp-all-votes&action=open&vote_id=' . $item['id']),
                        'wpvp_vote_action'
                    ),
                    __('Open Vote', 'wp-voting-plugin')
                );
            }

            // Delete
            $actions['delete'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')" class="delete-link">%s</a>',
                wp_nonce_url(
                    admin_url('admin.php?page=wpvp-all-votes&action=delete&vote_id=' . $item['id']),
                    'wpvp_vote_action'
                ),
                __('Are you sure you want to delete this vote?', 'wp-voting-plugin'),
                __('Delete', 'wp-voting-plugin')
            );
        }

        return sprintf(
            '%1$s %2$s',
            '<strong>' . esc_html($item['proposal_name']) . '</strong>',
            $this->row_actions($actions)
        );
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="vote[]" value="%s" />',
            $item['id']
        );
    }

    protected function get_bulk_actions()
    {
        if (wpvp_user_can_manage_votes()) {
            return [
                'bulk-delete' => __('Delete', 'wp-voting-plugin')
            ];
        }
        return [];
    }

    protected function extra_tablenav($which)
    {
        if ($which === 'top') {
    ?>
            <div class="alignleft actions">
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'wp-voting-plugin'); ?></option>
                    <option value="draft" <?php selected($_REQUEST['status'] ?? '', 'draft'); ?>>
                        <?php _e('Draft', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="open" <?php selected($_REQUEST['status'] ?? '', 'open'); ?>>
                        <?php _e('Open', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="completed" <?php selected($_REQUEST['status'] ?? '', 'completed'); ?>>
                        <?php _e('Completed', 'wp-voting-plugin'); ?>
                    </option>
                </select>
                <?php submit_button(__('Filter', 'wp-voting-plugin'), '', 'filter_action', false); ?>
            </div>
<?php
        }
    }
}

// Add menu item
add_action('admin_menu', function () {
    // This is added in settings.php, but included here for reference
    // add_submenu_page(...)
});
