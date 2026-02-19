<?php
/**
 * Main plugin singleton — loads all includes and wires hooks.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Plugin {

	/** @var self|null */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Require all class files.
	 */
	private function load_dependencies(): void {
		// Core.
		require_once WPVP_PLUGIN_DIR . 'includes/class-activator.php';
		require_once WPVP_PLUGIN_DIR . 'includes/class-database.php';
		require_once WPVP_PLUGIN_DIR . 'includes/class-permissions.php';

		// Voting algorithms.
		require_once WPVP_PLUGIN_DIR . 'includes/voting/interface-algorithm.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-processor.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-singleton.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-rcv.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-stv.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-sequential-rcv.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-condorcet.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-disciplinary.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-consent.php';
		require_once WPVP_PLUGIN_DIR . 'includes/voting/class-validator.php';

		// Admin (only in admin context).
		if ( is_admin() ) {
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-admin.php';
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-settings.php';
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-vote-list.php';
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-vote-editor.php';
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-guide.php';
			require_once WPVP_PLUGIN_DIR . 'includes/admin/class-role-templates.php';
		}

		// Notifications and cron.
		require_once WPVP_PLUGIN_DIR . 'includes/class-notifications.php';

		// Migration utility (admin only).
		if ( is_admin() ) {
			require_once WPVP_PLUGIN_DIR . 'includes/class-migration.php';
		}

		// Public / frontend.
		require_once WPVP_PLUGIN_DIR . 'includes/public/class-public.php';
		require_once WPVP_PLUGIN_DIR . 'includes/public/class-ballot.php';
		require_once WPVP_PLUGIN_DIR . 'includes/public/class-results-display.php';

		// AccessSchema client (self-contained module).
		$asc_client = WPVP_PLUGIN_DIR . 'includes/accessschema-client/accessSchema-client.php';
		if ( file_exists( $asc_client ) ) {
			require_once $asc_client;
		}
	}

	/**
	 * Wire up WordPress hooks.
	 */
	private function init_hooks(): void {
		// Version check / upgrade on admin_init.
		add_action( 'admin_init', array( 'WPVP_Activator', 'check_version' ) );

		// i18n.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Settings link on Plugins page.
		add_filter( 'plugin_action_links_' . WPVP_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		// Initialise admin or public subsystems.
		if ( is_admin() ) {
			new WPVP_Admin();
		}

		// Public always loads (shortcodes need to register early).
		new WPVP_Public();

		// Notifications and cron.
		new WPVP_Notifications();

		// Migration (admin only).
		if ( is_admin() ) {
			new WPVP_Migration();
		}

		/**
		 * Fires after all WP Voting Plugin components are loaded.
		 */
		do_action( 'wpvp_plugin_loaded' );
	}

	/**
	 * Load translation files.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'wp-voting-plugin', false, dirname( WPVP_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Add "Settings" link on the Plugins listing page.
	 */
	public function add_settings_link( array $links ): array {
		$settings_url  = esc_url( admin_url( 'admin.php?page=wpvp-settings' ) );
		$settings_link = '<a href="' . $settings_url . '">' . esc_html__( 'Settings', 'wp-voting-plugin' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
