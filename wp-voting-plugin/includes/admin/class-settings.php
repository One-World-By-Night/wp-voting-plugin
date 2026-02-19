<?php
/**
 * Admin settings page with 3 tabs: General, Permissions, Advanced.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Settings {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_wpvp_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_wpvp_fetch_roles', array( $this, 'ajax_fetch_roles' ) );
		add_action( 'wp_ajax_wpvp_process_closed_votes', array( $this, 'ajax_process_closed_votes' ) );
		add_action( 'wp_ajax_wpvp_guide_create_vote', array( $this, 'ajax_guide_create_vote' ) );
		add_action( 'wp_ajax_wpvp_get_template_roles', array( $this, 'ajax_get_template_roles' ) );
		add_action( 'wp_ajax_wpvp_get_all_templates', array( $this, 'ajax_get_all_templates' ) );

		// Whitelist option groups for multisite (must be in constructor, not in register_settings).
		add_filter( 'allowed_options', array( $this, 'whitelist_options' ), 1 );
		add_filter( 'whitelist_options', array( $this, 'whitelist_options' ), 1 ); // Pre-WP 5.5 compatibility.
	}

	/*
	------------------------------------------------------------------
	 *  Settings registration.
	 * ----------------------------------------------------------------*/

	public function register_settings(): void {
		// Register option groups (required for multisite).
		add_settings_section( 'wpvp_general_section', '', '__return_empty_string', 'wpvp_general' );
		add_settings_section( 'wpvp_permissions_section', '', '__return_empty_string', 'wpvp_permissions' );
		add_settings_section( 'wpvp_advanced_section', '', '__return_empty_string', 'wpvp_advanced' );

		// General tab.
		register_setting(
			'wpvp_general',
			'wpvp_default_voting_type',
			array(
				'sanitize_callback' => 'sanitize_key',
				'default'           => 'singleton',
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_require_login',
			array(
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => true,
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_show_results_before_close',
			array(
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_enable_email_notifications',
			array(
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_admin_notification_email',
			array(
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_default_notify_open_to',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_default_notify_reminder_to',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'wpvp_general',
			'wpvp_default_notify_close_to',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		// Permissions tab.
		register_setting(
			'wpvp_permissions',
			'wpvp_accessschema_mode',
			array(
				'sanitize_callback' => function ( $v ) {
					return in_array( $v, array( 'none', 'remote', 'local' ), true ) ? $v : 'none';
				},
				'default'           => 'none',
			)
		);
		register_setting(
			'wpvp_permissions',
			'wpvp_accessschema_client_url',
			array(
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		register_setting(
			'wpvp_permissions',
			'wpvp_accessschema_client_key',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'wpvp_permissions',
			'wpvp_capability_map',
			array(
				'sanitize_callback' => array( $this, 'sanitize_capability_map' ),
				'default'           => array(),
			)
		);
		register_setting(
			'wpvp_permissions',
			'wpvp_wp_capabilities',
			array(
				'sanitize_callback' => array( $this, 'sanitize_wp_capabilities' ),
				'default'           => array(
					'create_votes' => 'manage_options',
					'manage_votes' => 'manage_options',
					'cast_votes'   => 'read',
					'view_results' => 'read',
				),
			)
		);

		// Advanced tab.
		register_setting(
			'wpvp_advanced',
			'wpvp_remove_data_on_uninstall',
			array(
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			)
		);
		register_setting(
			'wpvp_advanced',
			'wpvp_timezone',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => get_option( 'timezone_string', 'UTC' ),
			)
		);
	}

	/**
	 * Whitelist our option groups for WordPress multisite.
	 *
	 * This filter ensures the Settings API allows saves for our custom option groups.
	 * Must be registered early (priority 1) in the constructor, not in admin_init.
	 *
	 * @param array $allowed_options Array of allowed option groups and their options.
	 * @return array Modified allowed options.
	 */
	public function whitelist_options( array $allowed_options ): array {
		$allowed_options['wpvp_general']     = array(
			'wpvp_default_voting_type',
			'wpvp_require_login',
			'wpvp_show_results_before_close',
			'wpvp_enable_email_notifications',
			'wpvp_admin_notification_email',
			'wpvp_default_notify_open_to',
			'wpvp_default_notify_reminder_to',
			'wpvp_default_notify_close_to',
		);
		$allowed_options['wpvp_permissions'] = array(
			'wpvp_accessschema_mode',
			'wpvp_accessschema_client_url',
			'wpvp_accessschema_client_key',
			'wpvp_capability_map',
			'wpvp_wp_capabilities',
		);
		$allowed_options['wpvp_advanced']    = array(
			'wpvp_remove_data_on_uninstall',
			'wpvp_timezone',
		);

		return $allowed_options;
	}

	/**
	 * Handle manual settings save (bypasses options.php for multisite compatibility).
	 *
	 * @param string $tab The active tab being saved.
	 */
	private function handle_save( string $tab ): void {
		switch ( $tab ) {
			case 'general':
				update_option( 'wpvp_default_voting_type', isset( $_POST['wpvp_default_voting_type'] ) ? sanitize_key( $_POST['wpvp_default_voting_type'] ) : 'singleton' );
				update_option( 'wpvp_require_login', isset( $_POST['wpvp_require_login'] ) );
				update_option( 'wpvp_show_results_before_close', isset( $_POST['wpvp_show_results_before_close'] ) );
				update_option( 'wpvp_enable_email_notifications', isset( $_POST['wpvp_enable_email_notifications'] ) );
				update_option( 'wpvp_admin_notification_email', isset( $_POST['wpvp_admin_notification_email'] ) ? sanitize_email( wp_unslash( $_POST['wpvp_admin_notification_email'] ) ) : '' );
				update_option( 'wpvp_default_notify_open_to', isset( $_POST['wpvp_default_notify_open_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wpvp_default_notify_open_to'] ) ) : '' );
				update_option( 'wpvp_default_notify_reminder_to', isset( $_POST['wpvp_default_notify_reminder_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wpvp_default_notify_reminder_to'] ) ) : '' );
				update_option( 'wpvp_default_notify_close_to', isset( $_POST['wpvp_default_notify_close_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wpvp_default_notify_close_to'] ) ) : '' );
				break;

			case 'permissions':
				update_option( 'wpvp_accessschema_mode', isset( $_POST['wpvp_accessschema_mode'] ) ? sanitize_key( $_POST['wpvp_accessschema_mode'] ) : 'none' );
				update_option( 'wpvp_accessschema_client_url', isset( $_POST['wpvp_accessschema_client_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpvp_accessschema_client_url'] ) ) : '' );
				update_option( 'wpvp_accessschema_client_key', isset( $_POST['wpvp_accessschema_client_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wpvp_accessschema_client_key'] ) ) : '' );
				update_option( 'wpvp_capability_map', isset( $_POST['wpvp_capability_map'] ) ? $this->sanitize_capability_map( wp_unslash( $_POST['wpvp_capability_map'] ) ) : array() );
				update_option( 'wpvp_wp_capabilities', isset( $_POST['wpvp_wp_capabilities'] ) ? $this->sanitize_wp_capabilities( wp_unslash( $_POST['wpvp_wp_capabilities'] ) ) : array() );
				break;

			case 'advanced':
				update_option( 'wpvp_remove_data_on_uninstall', isset( $_POST['wpvp_remove_data_on_uninstall'] ) );
				update_option( 'wpvp_timezone', isset( $_POST['wpvp_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wpvp_timezone'] ) ) : 'UTC' );
				break;
		}
	}

	/*
	------------------------------------------------------------------
	 *  Render.
	 * ----------------------------------------------------------------*/

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-voting-plugin' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$valid_tabs = array( 'general', 'permissions', 'advanced' );
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'general';
		}

		// Handle form submission manually (bypass options.php for multisite compatibility).
		if ( isset( $_POST['wpvp_settings_submit'] ) && check_admin_referer( 'wpvp_settings_' . $active_tab ) ) {
			$this->handle_save( $active_tab );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'wp-voting-plugin' ) . '</p></div>';
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Voting Settings', 'wp-voting-plugin' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $valid_tabs as $tab ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=' . $tab ) ); ?>"
						class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( ucfirst( $tab ) ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="">
				<?php
				wp_nonce_field( 'wpvp_settings_' . $active_tab );

				switch ( $active_tab ) {
					case 'permissions':
						$this->render_permissions_tab();
						break;
					case 'advanced':
						$this->render_advanced_tab();
						break;
					default:
						$this->render_general_tab();
						break;
				}

				submit_button( null, 'primary', 'wpvp_settings_submit' );
				?>
			</form>
		</div>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Tab renderers.
	 * ----------------------------------------------------------------*/

	private function render_general_tab(): void {
		$vote_types = WPVP_Database::get_vote_types();
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="wpvp_default_voting_type"><?php esc_html_e( 'Default Voting Type', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<select name="wpvp_default_voting_type" id="wpvp_default_voting_type">
						<?php foreach ( $vote_types as $key => $type ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"
								<?php selected( get_option( 'wpvp_default_voting_type', 'singleton' ), $key ); ?>>
								<?php echo esc_html( $type['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Require Login to Vote', 'wp-voting-plugin' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpvp_require_login" value="1"
							<?php checked( get_option( 'wpvp_require_login', true ) ); ?>>
						<?php esc_html_e( 'Users must be logged in to cast a vote.', 'wp-voting-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show Results Before Close', 'wp-voting-plugin' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpvp_show_results_before_close" value="1"
							<?php checked( get_option( 'wpvp_show_results_before_close', false ) ); ?>>
						<?php esc_html_e( 'Allow eligible voters to view partial results while voting is still open.', 'wp-voting-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email Notifications', 'wp-voting-plugin' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpvp_enable_email_notifications" value="1"
							<?php checked( get_option( 'wpvp_enable_email_notifications', false ) ); ?>>
						<?php esc_html_e( 'Send email notifications when votes open or close.', 'wp-voting-plugin' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Voters can opt-in to receive confirmation emails when they cast their ballot.', 'wp-voting-plugin' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpvp_admin_notification_email"><?php esc_html_e( 'Admin Notification Email', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="email" id="wpvp_admin_notification_email"
							name="wpvp_admin_notification_email"
							value="<?php echo esc_attr( get_option( 'wpvp_admin_notification_email', '' ) ); ?>"
							class="regular-text"
							placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description">
						<?php
						printf(
							/* translators: %s: default admin email */
							esc_html__( 'Email address for vote opening, closing, and reminder notifications. Defaults to %s if not set.', 'wp-voting-plugin' ),
							'<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpvp_default_notify_open_to"><?php esc_html_e( 'Vote Opened Recipients', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="text" id="wpvp_default_notify_open_to"
							name="wpvp_default_notify_open_to"
							value="<?php echo esc_attr( get_option( 'wpvp_default_notify_open_to', '' ) ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'eligible voters + admin', 'wp-voting-plugin' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Default recipients when a vote opens. Comma-separated emails. Leave blank to use eligible voters + admin.', 'wp-voting-plugin' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpvp_default_notify_reminder_to"><?php esc_html_e( 'Closing Reminder Recipients', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="text" id="wpvp_default_notify_reminder_to"
							name="wpvp_default_notify_reminder_to"
							value="<?php echo esc_attr( get_option( 'wpvp_default_notify_reminder_to', '' ) ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'admin email', 'wp-voting-plugin' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Default recipients for the closing-day reminder. Comma-separated emails. Leave blank to use admin email only.', 'wp-voting-plugin' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpvp_default_notify_close_to"><?php esc_html_e( 'Vote Closed Recipients', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="text" id="wpvp_default_notify_close_to"
							name="wpvp_default_notify_close_to"
							value="<?php echo esc_attr( get_option( 'wpvp_default_notify_close_to', '' ) ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'voters + admin', 'wp-voting-plugin' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Default recipients when a vote closes. Comma-separated emails. Leave blank to use voters who participated + admin.', 'wp-voting-plugin' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_permissions_tab(): void {
		$mode = get_option( 'wpvp_accessschema_mode', 'none' );
		$url  = get_option( 'wpvp_accessschema_client_url', '' );
		$key  = get_option( 'wpvp_accessschema_client_key', '' );
		$caps = get_option( 'wpvp_wp_capabilities', array() );
		?>
		<h2><?php esc_html_e( 'AccessSchema Connection', 'wp-voting-plugin' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'When AccessSchema is configured, it takes priority for voter eligibility checks. If unavailable, WordPress capabilities are used as a fallback.', 'wp-voting-plugin' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Connection Mode', 'wp-voting-plugin' ); ?></th>
				<td>
					<fieldset>
						<?php
						foreach ( array(
							'none'   => 'Disabled',
							'remote' => 'Remote Server',
							'local'  => 'Local (same site)',
						) as $val => $label ) :
							?>
							<label style="display:block; margin-bottom:4px;">
								<input type="radio" name="wpvp_accessschema_mode" value="<?php echo esc_attr( $val ); ?>"
									<?php checked( $mode, $val ); ?>>
									<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr class="wpvp-asc-remote-fields" <?php echo 'remote' !== $mode ? 'style="display:none;"' : ''; ?>>
				<th scope="row">
					<label for="wpvp_accessschema_client_url"><?php esc_html_e( 'Remote URL', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="url" id="wpvp_accessschema_client_url"
							name="wpvp_accessschema_client_url"
							value="<?php echo esc_attr( $url ); ?>"
							class="regular-text"
							placeholder="https://example.com">
				</td>
			</tr>
			<tr class="wpvp-asc-remote-fields" <?php echo 'remote' !== $mode ? 'style="display:none;"' : ''; ?>>
				<th scope="row">
					<label for="wpvp_accessschema_client_key"><?php esc_html_e( 'API Key', 'wp-voting-plugin' ); ?></label>
				</th>
				<td>
					<input type="password" id="wpvp_accessschema_client_key"
							name="wpvp_accessschema_client_key"
							value="<?php echo esc_attr( $key ); ?>"
							class="regular-text">
					<button type="button" class="button" id="wpvp-test-connection">
						<?php esc_html_e( 'Test Connection', 'wp-voting-plugin' ); ?>
					</button>
					<span id="wpvp-connection-result"></span>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'WordPress Capability Fallback', 'wp-voting-plugin' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'These WordPress capabilities are used when AccessSchema is disabled or unreachable.', 'wp-voting-plugin' ); ?>
		</p>
		<table class="form-table">
			<?php
			$cap_labels = array(
				'create_votes' => __( 'Create Votes', 'wp-voting-plugin' ),
				'manage_votes' => __( 'Manage Votes', 'wp-voting-plugin' ),
				'cast_votes'   => __( 'Cast Votes', 'wp-voting-plugin' ),
				'view_results' => __( 'View Results', 'wp-voting-plugin' ),
			);
			$wp_caps    = array( 'manage_options', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read' );
			foreach ( $cap_labels as $cap_key => $cap_label ) :
				$current = $caps[ $cap_key ] ?? 'read';
				?>
				<tr>
					<th scope="row"><label for="wpvp_cap_<?php echo esc_attr( $cap_key ); ?>"><?php echo esc_html( $cap_label ); ?></label></th>
					<td>
						<select name="wpvp_wp_capabilities[<?php echo esc_attr( $cap_key ); ?>]" id="wpvp_cap_<?php echo esc_attr( $cap_key ); ?>">
							<?php foreach ( $wp_caps as $wp_cap ) : ?>
								<option value="<?php echo esc_attr( $wp_cap ); ?>" <?php selected( $current, $wp_cap ); ?>>
									<?php echo esc_html( $wp_cap ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	private function render_advanced_tab(): void {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Remove Data on Uninstall', 'wp-voting-plugin' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wpvp_remove_data_on_uninstall" value="1"
							<?php checked( get_option( 'wpvp_remove_data_on_uninstall', false ) ); ?>>
						<?php esc_html_e( 'Delete all votes, ballots, results, and settings when the plugin is uninstalled.', 'wp-voting-plugin' ); ?>
					</label>
					<p class="description" style="color:#d63638;">
						<?php esc_html_e( 'Warning: This action is irreversible.', 'wp-voting-plugin' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Vote Processing', 'wp-voting-plugin' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Manually process closed votes that have not yet been calculated.', 'wp-voting-plugin' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Process Votes', 'wp-voting-plugin' ); ?></th>
				<td>
					<button type="button" class="button" id="wpvp-process-closed">
						<?php esc_html_e( 'Process All Closed Votes', 'wp-voting-plugin' ); ?>
					</button>
					<span id="wpvp-process-result" style="margin-left:8px;"></span>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Migration from v1 Plugin', 'wp-voting-plugin' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'If you previously used the original WP Voting Plugin, you can migrate your data here.', 'wp-voting-plugin' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Data Migration', 'wp-voting-plugin' ); ?></th>
				<td>
					<button type="button" class="button" id="wpvp-check-migration">
						<?php esc_html_e( 'Check for v1 Data', 'wp-voting-plugin' ); ?>
					</button>
					<button type="button" class="button button-primary" id="wpvp-run-migration" style="display:none;">
						<?php esc_html_e( 'Run Migration', 'wp-voting-plugin' ); ?>
					</button>
					<span id="wpvp-migration-result" style="margin-left:8px;"></span>
					<div id="wpvp-migration-log" style="display:none; margin-top:12px; max-height:200px; overflow-y:auto; background:#f6f7f7; padding:8px; border:1px solid #dcdcde; font-size:12px; font-family:monospace;"></div>
				</td>
			</tr>
		</table>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  AJAX handlers.
	 * ----------------------------------------------------------------*/

	public function ajax_test_connection(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$url = get_option( 'wpvp_accessschema_client_url', '' );
		$key = get_option( 'wpvp_accessschema_client_key', '' );

		if ( empty( $url ) || empty( $key ) ) {
			wp_send_json_error( __( 'URL and API key are required.', 'wp-voting-plugin' ) );
		}

		// Normalize URL: if it already contains the API path, use it as-is; otherwise append the path.
		$api_url = $url;
		if ( false === strpos( $url, '/wp-json/access-schema/v1' ) ) {
			$api_url = trailingslashit( $url ) . 'wp-json/access-schema/v1';
		}
		$api_url = trailingslashit( $api_url ) . 'roles/all';

		$response = wp_remote_get(
			$api_url,
			array(
				'headers'   => array( 'x-api-key' => $key ),
				'timeout'   => 15,
				'sslverify' => apply_filters( 'wpvp_accessschema_sslverify', true ),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			wp_send_json_error( sprintf( __( 'Server returned HTTP %d.', 'wp-voting-plugin' ), $code ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			wp_send_json_error( __( 'Invalid response from server.', 'wp-voting-plugin' ) );
		}

		// Cache roles for 24 hours.
		set_transient( 'wpvp_accessschema_roles', $body, DAY_IN_SECONDS );

		wp_send_json_success(
			array(
				'message'    => __( 'Connection successful.', 'wp-voting-plugin' ),
				'role_count' => count( $body ),
			)
		);
	}

	public function ajax_fetch_roles(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$roles = get_transient( 'wpvp_accessschema_roles' );
		if ( false === $roles ) {
			wp_send_json_error( __( 'No cached roles. Test the connection first.', 'wp-voting-plugin' ) );
		}

		wp_send_json_success( $roles );
	}

	/**
	 * AJAX: Process all closed (unprocessed) votes.
	 */
	public function ajax_process_closed_votes(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		global $wpdb;

		// Find closed votes that don't have results yet.
		$table   = WPVP_Database::votes_table();
		$results = WPVP_Database::results_table();

		$vote_ids = $wpdb->get_col(
			"SELECT v.id FROM {$table} v
             LEFT JOIN {$results} r ON v.id = r.vote_id
             WHERE v.voting_stage = 'closed' AND r.id IS NULL"
		);

		if ( empty( $vote_ids ) ) {
			wp_send_json_success(
				array(
					'processed' => 0,
					'message'   => __( 'No unprocessed closed votes found.', 'wp-voting-plugin' ),
				)
			);
		}

		$processed = 0;
		$errors    = array();

		foreach ( $vote_ids as $vote_id ) {
			$result = WPVP_Processor::process( (int) $vote_id );
			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( '#%d: %s', $vote_id, $result->get_error_message() );
			} else {
				++$processed;
			}
		}

		wp_send_json_success(
			array(
				'processed' => $processed,
				'errors'    => $errors,
				'message'   => sprintf(
					__( 'Processed %d votes.', 'wp-voting-plugin' ),
					$processed
				),
			)
		);
	}

	/**
	 * AJAX: Create vote from Guide builder form.
	 */
	public function ajax_guide_create_vote(): void {
		check_ajax_referer( 'wpvp_guide_create_vote', 'nonce' );

		if ( ! WPVP_Permissions::can_create_vote() ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		// Sanitize and collect form data.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below per-field.
		$raw_options = isset( $_POST['voting_options'] ) ? (array) wp_unslash( $_POST['voting_options'] ) : array();
		$options     = array();
		foreach ( $raw_options as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			$text = sanitize_text_field( $opt['text'] ?? '' );
			if ( '' === $text ) {
				continue;
			}
			$options[] = array(
				'text'        => $text,
				'description' => sanitize_text_field( $opt['description'] ?? '' ),
			);
		}

		// Parse allowed roles (Select2 sends array).
		$allowed_roles = array();
		if ( ! empty( $_POST['allowed_roles'] ) ) {
			$raw = (array) wp_unslash( $_POST['allowed_roles'] );
			$allowed_roles = array_values( array_filter( array_map( 'sanitize_text_field', $raw ) ) );
		}

		// Parse voting roles (Select2 sends array).
		$voting_roles = array();
		if ( ! empty( $_POST['voting_roles'] ) ) {
			$raw = (array) wp_unslash( $_POST['voting_roles'] );
			$voting_roles = array_values( array_filter( array_map( 'sanitize_text_field', $raw ) ) );
		}

		// Parse additional viewers (Select2 sends array).
		$additional_viewers = array();
		if ( ! empty( $_POST['additional_viewers'] ) ) {
			$raw = (array) wp_unslash( $_POST['additional_viewers'] );
			$additional_viewers = array_values( array_filter( array_map( 'sanitize_text_field', $raw ) ) );
		}

		// Build settings array.
		$raw_settings = isset( $_POST['settings'] ) ? (array) wp_unslash( $_POST['settings'] ) : array();
		$settings = array(
			'allow_revote'                 => ! empty( $raw_settings['allow_revote'] ),
			'show_results_before_closing'  => ! empty( $raw_settings['show_results_before_closing'] ),
			'anonymous_voting'             => ! empty( $raw_settings['anonymous_voting'] ),
			'allow_voter_comments'         => ! empty( $raw_settings['allow_voter_comments'] ),
			'notify_on_open'               => ! empty( $raw_settings['notify_on_open'] ),
			'notify_before_close'          => ! empty( $raw_settings['notify_before_close'] ),
			'notify_on_close'              => ! empty( $raw_settings['notify_on_close'] ),
			'notify_voter_confirmation'    => ! empty( $raw_settings['notify_voter_confirmation'] ),
			'notify_open_to'               => isset( $raw_settings['notify_open_to'] ) ? sanitize_text_field( $raw_settings['notify_open_to'] ) : '',
			'notify_reminder_to'           => isset( $raw_settings['notify_reminder_to'] ) ? sanitize_text_field( $raw_settings['notify_reminder_to'] ) : '',
			'notify_close_to'              => isset( $raw_settings['notify_close_to'] ) ? sanitize_text_field( $raw_settings['notify_close_to'] ) : '',
		);

		$data = array(
			'proposal_name'        => sanitize_text_field( wp_unslash( $_POST['proposal_name'] ?? '' ) ),
			'proposal_description' => wp_kses_post( wp_unslash( $_POST['proposal_description'] ?? '' ) ),
			'voting_type'          => sanitize_key( wp_unslash( $_POST['voting_type'] ?? 'singleton' ) ),
			'voting_options'       => $options,
			'number_of_winners'    => max( 1, absint( $_POST['number_of_winners'] ?? 1 ) ),
			'allowed_roles'        => $allowed_roles,
			'visibility'           => sanitize_key( wp_unslash( $_POST['visibility'] ?? 'private' ) ),
			'voting_roles'         => $voting_roles,
			'additional_viewers'   => $additional_viewers,
			'voting_eligibility'   => sanitize_key( wp_unslash( $_POST['voting_eligibility'] ?? 'private' ) ),
			'voting_stage'         => sanitize_key( wp_unslash( $_POST['voting_stage'] ?? 'draft' ) ),
			'opening_date'         => sanitize_text_field( wp_unslash( $_POST['opening_date'] ?? '' ) ),
			'closing_date'         => sanitize_text_field( wp_unslash( $_POST['closing_date'] ?? '' ) ),
			'settings'             => $settings,
			'classification'       => sanitize_text_field( wp_unslash( $_POST['classification'] ?? '' ) ),
			'proposed_by'          => sanitize_text_field( wp_unslash( $_POST['proposed_by'] ?? '' ) ),
			'seconded_by'          => sanitize_text_field( wp_unslash( $_POST['seconded_by'] ?? '' ) ),
			'objection_by'         => sanitize_text_field( wp_unslash( $_POST['objection_by'] ?? '' ) ),
		);

		// Basic validation.
		if ( empty( $data['proposal_name'] ) ) {
			wp_send_json_error( __( 'Title is required.', 'wp-voting-plugin' ) );
		}

		$valid_types = array_keys( WPVP_Database::get_vote_types() );
		if ( ! in_array( $data['voting_type'], $valid_types, true ) ) {
			wp_send_json_error( __( 'Invalid voting type.', 'wp-voting-plugin' ) );
		}

		if ( count( $data['voting_options'] ) < 2 && ! in_array( $data['voting_type'], array( 'disciplinary', 'consent' ), true ) ) {
			wp_send_json_error( __( 'At least two options are required.', 'wp-voting-plugin' ) );
		}

		// Save vote.
		$new_id = WPVP_Database::save_vote( $data );
		if ( ! $new_id ) {
			wp_send_json_error( __( 'Failed to create vote. Please try again.', 'wp-voting-plugin' ) );
		}

		wp_send_json_success(
			array(
				'vote_id'      => $new_id,
				'edit_url'     => admin_url( 'admin.php?page=wpvp-vote-edit&id=' . $new_id ),
				'message'      => __( 'Vote created successfully!', 'wp-voting-plugin' ),
			)
		);
	}

	/*
	------------------------------------------------------------------
	 *  Sanitisation helpers.
	 * ----------------------------------------------------------------*/

	public function sanitize_bool( $value ): bool {
		return (bool) $value;
	}

	public function sanitize_capability_map( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$clean = array();
		foreach ( $input as $cap => $paths ) {
			$cap = sanitize_key( $cap );
			if ( is_string( $paths ) ) {
				$paths = array_filter( array_map( 'sanitize_text_field', explode( "\n", $paths ) ) );
			} elseif ( is_array( $paths ) ) {
				$paths = array_filter( array_map( 'sanitize_text_field', $paths ) );
			} else {
				$paths = array();
			}
			if ( ! empty( $cap ) ) {
				$clean[ $cap ] = array_values( $paths );
			}
		}
		return $clean;
	}

	public function sanitize_wp_capabilities( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$clean   = array();
		$allowed = array( 'create_votes', 'manage_votes', 'cast_votes', 'view_results' );
		foreach ( $allowed as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : 'read';
		}
		return $clean;
	}

	/*
	------------------------------------------------------------------
	 *  Role Template AJAX handlers.
	 * ----------------------------------------------------------------*/

	/**
	 * AJAX: Get roles for a specific template (for populating Select2).
	 */
	public function ajax_get_template_roles(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wpvp_create_votes' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$template_id = absint( $_POST['template_id'] ?? 0 );
		if ( ! $template_id ) {
			wp_send_json_error( __( 'Invalid template ID.', 'wp-voting-plugin' ) );
		}

		$template = WPVP_Database::get_role_template( $template_id );
		if ( ! $template ) {
			wp_send_json_error( __( 'Template not found.', 'wp-voting-plugin' ) );
		}

		$roles = json_decode( $template->roles, true );
		wp_send_json_success( array(
			'template_name' => $template->template_name,
			'roles'         => is_array( $roles ) ? $roles : array(),
		) );
	}

	/**
	 * AJAX: Get all templates (for dropdown population).
	 */
	public function ajax_get_all_templates(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'wpvp_create_votes' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$templates = WPVP_Database::get_role_templates();
		$result    = array();

		foreach ( $templates as $t ) {
			$roles    = json_decode( $t->roles, true );
			$result[] = array(
				'id'    => (int) $t->id,
				'name'  => $t->template_name,
				'roles' => is_array( $roles ) ? $roles : array(),
			);
		}

		wp_send_json_success( $result );
	}
}
