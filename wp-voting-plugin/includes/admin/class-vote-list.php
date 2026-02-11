<?php
/**
 * All Votes admin page using WP_List_Table.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPVP_Vote_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'vote',
				'plural'   => 'votes',
				'ajax'     => false,
			)
		);
	}

	protected function get_views(): array {
		$stages   = WPVP_Database::get_vote_stages();
		$current  = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '';
		$base_url = admin_url( 'admin.php?page=wpvp-votes' );
		$total    = WPVP_Database::get_vote_count( array() );

		$views        = array();
		$views['all'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( $base_url ),
			'' === $current ? ' class="current"' : '',
			esc_html__( 'All', 'wp-voting-plugin' ),
			$total
		);

		foreach ( $stages as $key => $label ) {
			$count = WPVP_Database::get_vote_count( array( 'status' => $key ) );
			if ( $count > 0 ) {
				$views[ $key ] = sprintf(
					'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'status', $key, $base_url ) ),
					$key === $current ? ' class="current"' : '',
					esc_html( $label ),
					$count
				);
			}
		}

		return $views;
	}

	public function get_columns(): array {
		return array(
			'cb'            => '<input type="checkbox" />',
			'proposal_name' => __( 'Title', 'wp-voting-plugin' ),
			'voting_type'   => __( 'Type', 'wp-voting-plugin' ),
			'voting_stage'  => __( 'Status', 'wp-voting-plugin' ),
			'ballots'       => __( 'Responses', 'wp-voting-plugin' ),
			'creator_name'  => __( 'Created By', 'wp-voting-plugin' ),
			'created_at'    => __( 'Date', 'wp-voting-plugin' ),
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'proposal_name' => array( 'proposal_name', false ),
			'voting_type'   => array( 'voting_type', false ),
			'voting_stage'  => array( 'voting_stage', false ),
			'created_at'    => array( 'created_at', true ), // Default sort desc.
		);
	}

	protected function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'wp-voting-plugin' ),
			'open'   => __( 'Set Open', 'wp-voting-plugin' ),
			'close'  => __( 'Set Closed', 'wp-voting-plugin' ),
		);
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'wpvp_votes_per_page', 20 );

		$args = array(
			'per_page' => $per_page,
			'page'     => $this->get_pagenum(),
			'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
			'status'   => isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '',
			'type'     => isset( $_REQUEST['type'] ) ? sanitize_key( $_REQUEST['type'] ) : '',
			'orderby'  => isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'created_at',
			'order'    => isset( $_REQUEST['order'] ) ? sanitize_key( $_REQUEST['order'] ) : 'DESC',
		);

		$this->items = WPVP_Database::get_votes( $args );
		$total_items = WPVP_Database::get_vote_count( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		// Explicitly set column headers.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="vote[]" value="%d" />', intval( $item->id ) );
	}

	protected function column_proposal_name( $item ): string {
		$edit_url = admin_url( 'admin.php?page=wpvp-vote-edit&id=' . intval( $item->id ) );
		$title    = '<strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $item->proposal_name ) . '</a></strong>';

		$actions         = array();
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_url ),
			esc_html__( 'Edit', 'wp-voting-plugin' )
		);

		// Show Results link for completed/archived votes.
		if ( in_array( $item->voting_stage, array( 'closed', 'completed', 'archived' ), true ) ) {
			$page_ids    = get_option( 'wpvp_page_ids', array() );
			$results_url = ! empty( $page_ids['vote-results'] )
				? add_query_arg( 'wpvp_vote', intval( $item->id ), get_permalink( $page_ids['vote-results'] ) )
				: '';
			if ( $results_url ) {
				$actions['results'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $results_url ),
					esc_html__( 'Results', 'wp-voting-plugin' )
				);
			}
		}

		$actions['delete'] = sprintf(
			'<a href="%s" class="wpvp-delete-link" onclick="return confirm(wpvp.i18n.confirm_delete);">%s</a>',
			esc_url(
				wp_nonce_url(
					admin_url( 'admin.php?page=wpvp-votes&action=delete&vote[]=' . intval( $item->id ) ),
					'bulk-votes'
				)
			),
			esc_html__( 'Delete', 'wp-voting-plugin' )
		);

		return $title . $this->row_actions( $actions );
	}

	protected function column_voting_type( $item ): string {
		$types = WPVP_Database::get_vote_types();
		return esc_html( $types[ $item->voting_type ]['label'] ?? $item->voting_type );
	}

	protected function column_voting_stage( $item ): string {
		$stages = WPVP_Database::get_vote_stages();
		$label  = $stages[ $item->voting_stage ] ?? $item->voting_stage;
		$class  = 'wpvp-badge wpvp-badge--' . sanitize_html_class( $item->voting_stage );
		return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	protected function column_ballots( $item ): string {
		return esc_html( (string) WPVP_Database::get_ballot_count( intval( $item->id ) ) );
	}

	protected function column_creator_name( $item ): string {
		return esc_html( $item->creator_name ?? __( 'Unknown', 'wp-voting-plugin' ) );
	}

	protected function column_created_at( $item ): string {
		return esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) ) );
	}

	protected function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '' );
	}

	public function single_row( $item ) {
		echo sprintf( '<tr id="vote-%d">', $item->id );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	public function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}

	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$stages = WPVP_Database::get_vote_stages();
		$types  = WPVP_Database::get_vote_types();

		$current_status = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '';
		$current_type   = isset( $_REQUEST['type'] ) ? sanitize_key( $_REQUEST['type'] ) : '';

		echo '<div class="alignleft actions">';

		echo '<select name="status">';
		echo '<option value="">' . esc_html__( 'All Statuses', 'wp-voting-plugin' ) . '</option>';
		foreach ( $stages as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $current_status, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		echo '<select name="type">';
		echo '<option value="">' . esc_html__( 'All Types', 'wp-voting-plugin' ) . '</option>';
		foreach ( $types as $key => $info ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $current_type, $key, false ),
				esc_html( $info['label'] )
			);
		}
		echo '</select>';

		submit_button( __( 'Filter', 'wp-voting-plugin' ), '', 'filter_action', false );

		if ( ! empty( $current_status ) || ! empty( $current_type ) ) {
			printf(
				' <a href="%s" class="button">%s</a>',
				esc_url( admin_url( 'admin.php?page=wpvp-votes' ) ),
				esc_html__( 'Clear Filters', 'wp-voting-plugin' )
			);
		}
		echo '</div>';
	}
}

/**
 * Wrapper that handles rendering and bulk action processing.
 */
class WPVP_Vote_List {

	public function render(): void {
		if ( ! WPVP_Permissions::can_manage_votes() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-voting-plugin' ) );
		}

		$this->process_bulk_actions();

		$table = new WPVP_Vote_List_Table();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Votes', 'wp-voting-plugin' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-vote-edit' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-voting-plugin' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_notices(); ?>

			<?php $table->views(); ?>

			<form method="post">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? 'wpvp-votes' ); ?>">
				<?php
				// Preserve GET parameters for filters.
				if ( ! empty( $_GET['status'] ) ) {
					echo '<input type="hidden" name="status" value="' . esc_attr( sanitize_key( $_GET['status'] ) ) . '">';
				}
				if ( ! empty( $_GET['type'] ) ) {
					echo '<input type="hidden" name="type" value="' . esc_attr( sanitize_key( $_GET['type'] ) ) . '">';
				}
				if ( ! empty( $_GET['s'] ) ) {
					echo '<input type="hidden" name="s" value="' . esc_attr( sanitize_text_field( $_GET['s'] ) ) . '">';
				}
				$table->search_box( __( 'Search Votes', 'wp-voting-plugin' ), 'wpvp-search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Process bulk actions with nonce and capability checks.
	 */
	private function process_bulk_actions(): void {
		$action = '';
		if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
			$action = sanitize_key( $_REQUEST['action'] );
		} elseif ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) {
			$action = sanitize_key( $_REQUEST['action2'] );
		}

		if ( empty( $action ) || empty( $_REQUEST['vote'] ) ) {
			return;
		}

		// Verify nonce (WP_List_Table generates bulk-{plural} nonce).
		check_admin_referer( 'bulk-votes' );

		if ( ! WPVP_Permissions::can_manage_votes() ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$vote_ids = array_map( 'intval', (array) $_REQUEST['vote'] );
		$count    = 0;

		foreach ( $vote_ids as $vote_id ) {
			if ( $vote_id <= 0 ) {
				continue;
			}

			switch ( $action ) {
				case 'delete':
					if ( WPVP_Database::delete_vote( $vote_id ) ) {
						++$count;
					}
					break;
				case 'open':
					if ( WPVP_Database::update_vote( $vote_id, array( 'voting_stage' => 'open' ) ) ) {
						++$count;
						do_action( 'wpvp_vote_stage_changed', $vote_id, 'open', 'draft' );
					}
					break;
				case 'close':
					if ( WPVP_Database::update_vote( $vote_id, array( 'voting_stage' => 'closed' ) ) ) {
						++$count;
						do_action( 'wpvp_vote_stage_changed', $vote_id, 'closed', 'open' );
					}
					break;
			}
		}

		// Redirect to remove the action from the URL (PRG pattern).
		$redirect = remove_query_arg( array( 'action', 'action2', 'vote', '_wpnonce' ) );
		$redirect = add_query_arg(
			array(
				'wpvp_action' => $action,
				'wpvp_count'  => $count,
			),
			$redirect
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Show success notices after bulk actions.
	 */
	private function render_notices(): void {
		if ( empty( $_GET['wpvp_action'] ) ) {
			return;
		}

		$action = sanitize_key( $_GET['wpvp_action'] );
		$count  = intval( $_GET['wpvp_count'] ?? 0 );

		$messages = array(
			'delete' => sprintf( _n( '%d vote deleted.', '%d votes deleted.', $count, 'wp-voting-plugin' ), $count ),
			'open'   => sprintf( _n( '%d vote opened.', '%d votes opened.', $count, 'wp-voting-plugin' ), $count ),
			'close'  => sprintf( _n( '%d vote closed.', '%d votes closed.', $count, 'wp-voting-plugin' ), $count ),
		);

		if ( isset( $messages[ $action ] ) && $count > 0 ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $action ] ) );
		}
	}
}
