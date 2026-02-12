<?php
/**
 * Admin bootstrap: menus, enqueue, screen options.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Admin {

	/** @var string[] Page hook suffixes for our admin pages. */
	private $page_hooks = array();

	/** @var WPVP_Settings Settings instance (always loaded for AJAX handlers). */
	private $settings;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Instantiate settings early so AJAX actions are registered.
		require_once WPVP_PLUGIN_DIR . 'includes/admin/class-settings.php';
		$this->settings = new WPVP_Settings();
	}

	/**
	 * Register the top-level menu and submenus.
	 */
	public function register_menus(): void {
		$this->page_hooks['main'] = add_menu_page(
			__( 'WP Voting', 'wp-voting-plugin' ),
			__( 'WP Voting', 'wp-voting-plugin' ),
			'wpvp_manage_votes',
			'wpvp-votes',
			array( $this, 'render_votes_page' ),
			'dashicons-chart-pie',
			30
		);

		$this->page_hooks['all'] = add_submenu_page(
			'wpvp-votes',
			__( 'All Votes', 'wp-voting-plugin' ),
			__( 'All Votes', 'wp-voting-plugin' ),
			'wpvp_manage_votes',
			'wpvp-votes',
			array( $this, 'render_votes_page' )
		);

		$this->page_hooks['add'] = add_submenu_page(
			'wpvp-votes',
			__( 'Add New Vote', 'wp-voting-plugin' ),
			__( 'Add New', 'wp-voting-plugin' ),
			'wpvp_create_votes',
			'wpvp-vote-edit',
			array( $this, 'render_editor_page' )
		);

		$this->page_hooks['settings'] = add_submenu_page(
			'wpvp-votes',
			__( 'Settings', 'wp-voting-plugin' ),
			__( 'Settings', 'wp-voting-plugin' ),
			'manage_options',
			'wpvp-settings',
			array( $this, 'render_settings_page' )
		);

		$this->page_hooks['templates'] = add_submenu_page(
			'wpvp-votes',
			__( 'Role & Classification Templates', 'wp-voting-plugin' ),
			__( 'Templates', 'wp-voting-plugin' ),
			'manage_options',
			'wpvp-role-templates',
			array( $this, 'render_templates_page' )
		);

		$this->page_hooks['guide'] = add_submenu_page(
			'wpvp-votes',
			__( 'Guide', 'wp-voting-plugin' ),
			__( 'Guide', 'wp-voting-plugin' ),
			'wpvp_manage_votes',
			'wpvp-guide',
			array( $this, 'render_guide_page' )
		);

		// Screen options for the votes list.
		add_action( 'load-' . $this->page_hooks['main'], array( $this, 'add_screen_options' ) );
	}

	/**
	 * Enqueue CSS and JS only on our plugin pages.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		// Vendor: Select2.
		wp_enqueue_style( 'select2', WPVP_PLUGIN_URL . 'assets/vendor/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', WPVP_PLUGIN_URL . 'assets/vendor/select2.min.js', array( 'jquery' ), '4.1.0', true );

		// Plugin admin styles.
		wp_enqueue_style( 'wpvp-admin', WPVP_PLUGIN_URL . 'assets/css/admin.css', array(), WPVP_VERSION );

		// Plugin admin script.
		wp_enqueue_script( 'wpvp-admin', WPVP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'select2' ), WPVP_VERSION, true );

		wp_localize_script(
			'wpvp-admin',
			'wpvp',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wpvp_admin' ),
				'vote_types' => WPVP_Database::get_vote_types(),
				'i18n'       => array(
					'confirm_delete'       => __( 'Are you sure you want to delete this vote? This cannot be undone.', 'wp-voting-plugin' ),
					'select_roles'         => __( 'Select roles...', 'wp-voting-plugin' ),
					'additional_viewers_placeholder' => __( 'Chronicle/*/HST, Chronicle/*/Staff', 'wp-voting-plugin' ),
					'select_users'         => __( 'Search users...', 'wp-voting-plugin' ),
					'option_text'          => __( 'Option text', 'wp-voting-plugin' ),
					'description_optional' => __( 'Description (optional)', 'wp-voting-plugin' ),
					'replace_disciplinary' => __( 'Replace current options with standard disciplinary levels?', 'wp-voting-plugin' ),
					'testing'              => __( 'Testing...', 'wp-voting-plugin' ),
					'connection_failed'    => __( 'Connection failed.', 'wp-voting-plugin' ),
					'request_failed'       => __( 'Request failed.', 'wp-voting-plugin' ),
					'confirm_process'      => __( 'Process all closed votes now?', 'wp-voting-plugin' ),
					'processing'           => __( 'Processing...', 'wp-voting-plugin' ),
					'failed'               => __( 'Failed.', 'wp-voting-plugin' ),
					'checking'             => __( 'Checking...', 'wp-voting-plugin' ),
					'no_data'              => __( 'No data found.', 'wp-voting-plugin' ),
					'confirm_migration'    => __( 'Run the migration now? This will import v1 data into v2 tables.', 'wp-voting-plugin' ),
					'migrating'            => __( 'Migrating...', 'wp-voting-plugin' ),
					'migration_failed'     => __( 'Migration failed.', 'wp-voting-plugin' ),
					'redirecting'          => __( 'Redirecting...', 'wp-voting-plugin' ),
					'create_vote_failed'   => __( 'Failed to create vote.', 'wp-voting-plugin' ),
					'request_failed_retry' => __( 'Request failed. Please try again.', 'wp-voting-plugin' ),
					/* translators: %d: option number */
					'option_n'             => __( 'Option %d', 'wp-voting-plugin' ),
					'permanent_ban'        => __( 'Permanent Ban', 'wp-voting-plugin' ),
					'permanent_ban_desc'   => __( 'Permanent removal from the organization', 'wp-voting-plugin' ),
					'indefinite_ban'       => __( 'Indefinite Ban / 3 Strikes', 'wp-voting-plugin' ),
					'indefinite_ban_desc'  => __( 'Indefinite ban equivalent to 3 strikes', 'wp-voting-plugin' ),
					'temporary_ban'        => __( 'Temporary Ban', 'wp-voting-plugin' ),
					'temporary_ban_desc'   => __( 'Temporary ban for a defined period', 'wp-voting-plugin' ),
					'two_strikes'          => __( '2 Strikes', 'wp-voting-plugin' ),
					'two_strikes_desc'     => __( 'Second formal strike', 'wp-voting-plugin' ),
					'one_strike'           => __( '1 Strike', 'wp-voting-plugin' ),
					'one_strike_desc'      => __( 'First formal strike', 'wp-voting-plugin' ),
					'probation'            => __( 'Probation', 'wp-voting-plugin' ),
					'probation_desc'       => __( 'Placed on probationary status', 'wp-voting-plugin' ),
					'censure'              => __( 'Censure', 'wp-voting-plugin' ),
					'censure_desc'         => __( 'Formal censure on record', 'wp-voting-plugin' ),
					'condemnation'         => __( 'Condemnation', 'wp-voting-plugin' ),
					'condemnation_desc'    => __( 'Formal condemnation statement', 'wp-voting-plugin' ),
				),
			)
		);
	}

	/**
	 * Add per-page screen option on the votes list.
	 */
	public function add_screen_options(): void {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Votes per page', 'wp-voting-plugin' ),
				'default' => 20,
				'option'  => 'wpvp_votes_per_page',
			)
		);
	}

	/*
	------------------------------------------------------------------
	 *  Page render callbacks (delegate to dedicated classes).
	 * ----------------------------------------------------------------*/

	public function render_votes_page(): void {
		$list = new WPVP_Vote_List();
		$list->render();
	}

	public function render_editor_page(): void {
		$editor = new WPVP_Vote_Editor();
		$editor->render();
	}

	public function render_settings_page(): void {
		$this->settings->render();
	}

	public function render_templates_page(): void {
		$templates = new WPVP_Role_Templates();
		$templates->render();
	}

	public function render_guide_page(): void {
		$guide = new WPVP_Guide();
		$guide->render();
	}
}

/**
 * Save per-page screen option.
 */
add_filter(
	'set-screen-option',
	function ( $status, $option, $value ) {
		if ( 'wpvp_votes_per_page' === $option ) {
			return max( 1, intval( $value ) );
		}
		return $status;
	},
	10,
	3
);
