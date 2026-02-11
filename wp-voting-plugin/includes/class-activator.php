<?php
/**
 * Handles plugin activation, deactivation, and version upgrades.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Activator {

	/**
	 * Plugin activation callback.
	 */
	public static function activate(): void {
		require_once WPVP_PLUGIN_DIR . 'includes/class-database.php';

		WPVP_Database::create_tables();
		self::set_default_options();
		self::create_default_pages();
		self::add_capabilities();

		update_option( 'wpvp_db_version', WPVP_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'wpvp_daily_cron' );
		flush_rewrite_rules();
	}

	/**
	 * Compare stored version to current; run upgrades if needed.
	 * Hooked to admin_init.
	 */
	public static function check_version(): void {
		$stored = get_option( 'wpvp_db_version', '0' );
		if ( version_compare( $stored, WPVP_VERSION, '<' ) ) {
			WPVP_Database::create_tables(); // dbDelta is safe to re-run.
			self::add_capabilities();

			// Version-specific upgrades.
			if ( version_compare( $stored, '2.2.0', '<' ) ) {
				WPVP_Database::upgrade_to_220();
			}

			if ( version_compare( $stored, '2.3.0', '<' ) ) {
				// Create new open-votes and closed-votes pages (skips existing).
				self::create_default_pages();
			}

			if ( version_compare( $stored, '2.3.2', '<' ) ) {
				// Add classification system and proposal metadata fields.
				WPVP_Database::upgrade_to_231();
			}

			update_option( 'wpvp_db_version', WPVP_VERSION );
		}
	}

	/**
	 * Set default options (only if not already set).
	 */
	private static function set_default_options(): void {
		$defaults = array(
			'wpvp_default_voting_type'        => 'singleton',
			'wpvp_require_login'              => true,
			'wpvp_show_results_before_close'  => false,
			'wpvp_enable_email_notifications' => false,
			'wpvp_remove_data_on_uninstall'   => false,
			'wpvp_timezone'                   => get_option( 'timezone_string', 'UTC' ),
			'wpvp_accessschema_mode'          => 'none',
			'wpvp_accessschema_client_url'    => '',
			'wpvp_accessschema_client_key'    => '',
			'wpvp_capability_map'             => array(),
			'wpvp_wp_capabilities'            => array(
				'create_votes' => 'manage_options',
				'manage_votes' => 'manage_options',
				'cast_votes'   => 'read',
				'view_results' => 'read',
			),
		);

		foreach ( $defaults as $key => $value ) {
			add_option( $key, $value );
		}
	}

	/**
	 * Create default pages used by the plugin shortcodes.
	 */
	private static function create_default_pages(): void {
		$pages = array(
			// Main user-facing pages.
			'voting-dashboard' => array(
				'title'     => __( 'Voting Dashboard', 'wp-voting-plugin' ),
				'shortcode' => '[wpvp_votes]',
				'parent'    => 0,
			),
			'open-votes'       => array(
				'title'     => __( 'Open Votes', 'wp-voting-plugin' ),
				'shortcode' => '[wpvp_votes status="open" limit="50"]',
				'parent'    => 0,
			),
			'closed-votes'     => array(
				'title'     => __( 'Closed Votes', 'wp-voting-plugin' ),
				'shortcode' => '[wpvp_votes status="closed,completed,archived" limit="50"]',
				'parent'    => 0,
			),
			// Hidden utility pages (loaded via lightbox AJAX).
			'cast-vote'        => array(
				'title'     => __( 'Cast Vote', 'wp-voting-plugin' ),
				'shortcode' => '[wpvp_vote]',
				'parent'    => 0,
				'hidden'    => true,
			),
			'vote-results'     => array(
				'title'     => __( 'Vote Results', 'wp-voting-plugin' ),
				'shortcode' => '[wpvp_results]',
				'parent'    => 0,
				'hidden'    => true,
			),
		);

		$page_ids    = get_option( 'wpvp_page_ids', array() );
		$created_ids = array();

		foreach ( $pages as $slug => $page_data ) {
			// Skip if this page was already created.
			if ( ! empty( $page_ids[ $slug ] ) && get_post_status( $page_ids[ $slug ] ) ) {
				$created_ids[ $slug ] = $page_ids[ $slug ];
				continue;
			}

			// Resolve parent.
			$parent_id = 0;
			if ( $page_data['parent'] && isset( $created_ids[ $page_data['parent'] ] ) ) {
				$parent_id = $created_ids[ $page_data['parent'] ];
			}

			$new_page_id = wp_insert_post(
				array(
					'post_title'   => $page_data['title'],
					'post_name'    => $slug,
					'post_content' => $page_data['shortcode'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_parent'  => $parent_id,
				)
			);

			if ( ! is_wp_error( $new_page_id ) ) {
				$created_ids[ $slug ] = $new_page_id;

				// Mark hidden pages to exclude from navigation.
				if ( ! empty( $page_data['hidden'] ) ) {
					update_post_meta( $new_page_id, '_wpvp_hidden_page', true );
				}
			}
		}

		update_option( 'wpvp_page_ids', $created_ids );
	}

	/**
	 * Register custom capabilities with the administrator role.
	 */
	private static function add_capabilities(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		$caps = array(
			'wpvp_create_votes',
			'wpvp_manage_votes',
			'wpvp_cast_votes',
			'wpvp_view_results',
		);

		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}
}
