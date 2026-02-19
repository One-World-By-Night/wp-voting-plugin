<?php
/**
 * Ballot casting: AJAX handler, validation per voting type,
 * double-vote prevention.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Ballot {

	public function __construct() {
		add_action( 'wp_ajax_wpvp_cast_ballot', array( $this, 'ajax_cast_ballot' ) );
		add_action( 'wp_ajax_nopriv_wpvp_cast_ballot', array( $this, 'ajax_no_auth' ) );
		add_action( 'wp_ajax_wpvp_get_eligible_roles', array( $this, 'ajax_get_eligible_roles' ) );
	}

	/**
	 * AJAX: unauthenticated users get a clear error.
	 */
	public function ajax_no_auth(): void {
		wp_send_json_error(
			array(
				'message' => __( 'You must be logged in to vote.', 'wp-voting-plugin' ),
			)
		);
	}

	/**
	 * AJAX: process a ballot submission from an authenticated user.
	 */
	public function ajax_cast_ballot(): void {
		// 1. Nonce check.
		check_ajax_referer( 'wpvp_public', 'nonce' );

		// 2. User must be logged in.
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to vote.', 'wp-voting-plugin' ) ) );
		}

		// 3. Get and validate vote_id.
		$vote_id = isset( $_POST['vote_id'] ) ? absint( $_POST['vote_id'] ) : 0;
		if ( ! $vote_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid vote.', 'wp-voting-plugin' ) ) );
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			wp_send_json_error( array( 'message' => __( 'Vote not found.', 'wp-voting-plugin' ) ) );
		}

		// 4. Vote must be open.
		if ( 'open' !== $vote->voting_stage ) {
			wp_send_json_error( array( 'message' => __( 'This vote is not currently open.', 'wp-voting-plugin' ) ) );
		}

		// 5. Date window check (consent votes bypass — objections allowed any time while open).
		if ( 'consent' !== $vote->voting_type ) {
			$now = current_time( 'mysql' );
			if ( $vote->opening_date && $now < $vote->opening_date ) {
				wp_send_json_error( array( 'message' => __( 'This vote has not opened yet.', 'wp-voting-plugin' ) ) );
			}
			if ( $vote->closing_date && $now > $vote->closing_date ) {
				wp_send_json_error( array( 'message' => __( 'This vote has closed.', 'wp-voting-plugin' ) ) );
			}
		}

		// 6. Permission check.
		if ( ! WPVP_Permissions::can_cast_vote( $user_id, $vote_id ) ) {
			// Distinguish between "already voted" and "no access".
			if ( WPVP_Database::user_has_voted( $user_id, $vote_id ) ) {
				$decoded_settings = json_decode( $vote->settings, true );
				$settings         = $decoded_settings ? $decoded_settings : array();
				if ( empty( $settings['allow_revote'] ) ) {
					wp_send_json_error( array( 'message' => __( 'You have already voted and revoting is not allowed.', 'wp-voting-plugin' ) ) );
				}
			}
			wp_send_json_error( array( 'message' => __( 'You do not have permission to vote.', 'wp-voting-plugin' ) ) );
		}

		// 6.5. Get eligible roles and validate role selection.
		$eligible_roles = WPVP_Permissions::get_eligible_voting_roles( $user_id, $vote );

		if ( empty( $eligible_roles ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have any eligible roles to vote.', 'wp-voting-plugin' ) ) );
		}

		$selected_role = null;

		// If user has multiple eligible roles, they must select one.
		if ( count( $eligible_roles ) > 1 ) {
			$selected_role = isset( $_POST['voting_role'] ) ? sanitize_text_field( wp_unslash( $_POST['voting_role'] ) ) : '';

			if ( empty( $selected_role ) ) {
				wp_send_json_error(
					array(
						'message'                 => __( 'You have multiple eligible roles. Please select which role you are voting as.', 'wp-voting-plugin' ),
						'requires_role_selection' => true,
						'eligible_roles'          => $eligible_roles,
					)
				);
			}

			// Validate selected role is in eligible roles.
			if ( ! in_array( $selected_role, $eligible_roles, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid role selected.', 'wp-voting-plugin' ) ) );
			}
		} else {
			// Single role - use it automatically.
			$selected_role = $eligible_roles[0];
		}

		// Get user data for preservation.
		$user         = get_userdata( $user_id );
		$display_name = $user ? $user->display_name : '';
		$username     = $user ? $user->user_login : '';

		// 7. Parse ballot data from POST.
		$raw_ballot = isset( $_POST['ballot_data'] ) ? wp_unslash( $_POST['ballot_data'] ) : '';
		if ( ! is_string( $raw_ballot ) || '' === $raw_ballot ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ballot data.', 'wp-voting-plugin' ) ) );
		}
		$ballot_data = json_decode( $raw_ballot, true );
		if ( null === $ballot_data ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ballot format.', 'wp-voting-plugin' ) ) );
		}

		// 8. Validate ballot data against voting type.
		$decoded_options = json_decode( $vote->voting_options, true );
		if ( ! is_array( $decoded_options ) || empty( $decoded_options ) ) {
			wp_send_json_error( array( 'message' => __( 'This vote has no valid options configured. Please contact an administrator.', 'wp-voting-plugin' ) ) );
		}
		$option_texts = array_column( $decoded_options, 'text' );

		$validation = $this->validate_ballot( $vote->voting_type, $ballot_data, $option_texts );
		if ( ! $validation['valid'] ) {
			wp_send_json_error( array( 'message' => $validation['error'] ) );
		}

		// Use the sanitized data from validation.
		$ballot_data = $validation['sanitized'];

		// 8.5. Restructure ballot data to include role and user info.
		$voter_comment = isset( $_POST['voter_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['voter_comment'] ) ) : '';

		$ballot_payload = array(
			'choice'        => $ballot_data,
			'voting_role'   => $selected_role,
			'display_name'  => $display_name,
			'username'      => $username,
			'voter_comment' => $voter_comment,
		);

		// 9. Save or update.
		$already_voted = WPVP_Database::user_has_voted( $user_id, $vote_id );

		if ( $already_voted ) {
			$result  = WPVP_Database::update_ballot( $vote_id, $user_id, $ballot_payload );
			$message = __( 'Your vote has been updated.', 'wp-voting-plugin' );
		} else {
			$result  = WPVP_Database::cast_ballot( $vote_id, $user_id, $ballot_payload );
			$message = __( 'Your vote has been recorded.', 'wp-voting-plugin' );
		}

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save your vote. Please try again.', 'wp-voting-plugin' ) ) );
		}

		// Consent agenda conversion: objection filed → convert to FPTP.
		if ( 'consent' === $vote->voting_type ) {
			global $wpdb;

			// Build converted settings with objector info.
			$fptp_options = array(
				array(
					'text'        => __( 'Approve', 'wp-voting-plugin' ),
					'description' => '',
				),
				array(
					'text'        => __( 'Deny', 'wp-voting-plugin' ),
					'description' => '',
				),
				array(
					'text'        => WPVP_ABSTAIN_LABEL,
					'description' => '',
				),
			);

			$decoded_settings   = json_decode( $vote->settings, true );
			$converted_settings = $decoded_settings ? $decoded_settings : array();
			$converted_settings['consent_objection'] = array(
				'user_id'      => $user_id,
				'display_name' => $user ? $user->display_name : '',
				'username'     => $user ? $user->user_login : '',
				'voting_role'  => $selected_role,
				'objected_at'  => current_time( 'mysql' ),
			);
			$converted_settings['allow_revote'] = true;

			// Atomic conversion: UPDATE only if voting_type is still 'consent'.
			// This prevents a race where two simultaneous objections both convert.
			$votes_table = WPVP_Database::votes_table();
			$rows_updated = $wpdb->update(
				$votes_table,
				array(
					'voting_type'    => 'singleton',
					'voting_options' => wp_json_encode( $fptp_options ),
					'settings'       => wp_json_encode( $converted_settings ),
				),
				array(
					'id'          => $vote_id,
					'voting_type' => 'consent',
				),
				array( '%s', '%s', '%s' ),
				array( '%d', '%s' )
			);

			if ( 0 === $rows_updated || false === $rows_updated ) {
				// Another request already converted this vote.
				$current_vote = WPVP_Database::get_vote( $vote_id );
				wp_send_json_success( array(
					'message'   => __( 'This proposal has already been converted to a vote. The page will reload.', 'wp-voting-plugin' ),
					'converted' => true,
					'new_type'  => $current_vote ? $current_vote->voting_type : 'singleton',
				) );
			}

			// Conversion succeeded — delete existing ballots for a fresh start.
			$wpdb->delete( WPVP_Database::ballots_table(), array( 'vote_id' => $vote_id ), array( '%d' ) );

			wp_send_json_success(
				array(
					'message'   => __( 'Objection filed. This proposal has been converted to a standard vote. The page will reload so you can cast your vote.', 'wp-voting-plugin' ),
					'converted' => true,
					'new_type'  => 'singleton',
				)
			);
		}

		// Save user's notification preference and custom email addresses.
		if ( isset( $_POST['send_confirmation'] ) ) {
			$send_confirmation = (bool) $_POST['send_confirmation'];
			update_user_meta( $user_id, 'wpvp_vote_' . $vote_id . '_notify', $send_confirmation );

			if ( $send_confirmation && ! empty( $_POST['confirmation_emails'] ) ) {
				$raw_emails = sanitize_text_field( wp_unslash( $_POST['confirmation_emails'] ) );
				update_user_meta( $user_id, 'wpvp_vote_' . $vote_id . '_notify_emails', $raw_emails );
			}
		} else {
			update_user_meta( $user_id, 'wpvp_vote_' . $vote_id . '_notify', false );
		}

		// Fire action for email notifications and other plugins.
		do_action( 'wpvp_ballot_submitted', $vote_id, $user_id, $ballot_payload );

		// Check if revoting is allowed.
		$decoded_settings = json_decode( $vote->settings, true );
		$settings         = $decoded_settings ? $decoded_settings : array();
		$allow_revote     = ! empty( $settings['allow_revote'] );

		wp_send_json_success(
			array(
				'message'      => $message,
				'revoted'      => $already_voted,
				'allow_revote' => $allow_revote,
				'ballot_data'  => $ballot_data,
				'voting_role'  => $selected_role,
				'vote_type'    => $vote->voting_type,
			)
		);
	}

	/*
	------------------------------------------------------------------
	 *  Ballot validation per voting type.
	 * ----------------------------------------------------------------*/

	/**
	 * Validate ballot data against the voting type and available options.
	 *
	 * @return array { valid: bool, error: string, sanitized: mixed }
	 */
	private function validate_ballot( string $type, $data, array $options ): array {
		switch ( $type ) {
			case 'singleton':
				return $this->validate_singleton( $data, $options );
			case 'rcv':
			case 'stv':
			case 'sequential_rcv':
				return $this->validate_ranked( $data, $options );
			case 'condorcet':
				return $this->validate_ranked( $data, $options );
			case 'disciplinary':
				return $this->validate_disciplinary( $data, $options );
			case 'consent':
				return $this->validate_consent( $data );
			default:
				return array(
					'valid'     => false,
					'error'     => __( 'Unknown voting type.', 'wp-voting-plugin' ),
					'sanitized' => null,
				);
		}
	}

	/**
	 * Singleton: single string matching a valid option.
	 */
	private function validate_singleton( $data, array $options ): array {
		if ( is_array( $data ) ) {
			$data = $data[0] ?? '';
		}

		$data = sanitize_text_field( (string) $data );

		if ( empty( $data ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Please select an option.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		if ( ! in_array( $data, $options, true ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Invalid option selected.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		return array(
			'valid'     => true,
			'error'     => '',
			'sanitized' => $data,
		);
	}

	/**
	 * Ranked ballot (RCV, STV, Condorcet): array of option strings, all valid, no duplicates.
	 */
	private function validate_ranked( $data, array $options ): array {
		if ( ! is_array( $data ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Please rank at least one option.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		$sanitized = array();
		foreach ( $data as $item ) {
			$item = sanitize_text_field( (string) $item );
			if ( ! empty( $item ) ) {
				$sanitized[] = $item;
			}
		}

		if ( empty( $sanitized ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Please rank at least one option.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		// Check for duplicates.
		if ( count( $sanitized ) !== count( array_unique( $sanitized ) ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Duplicate rankings are not allowed.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		// Check all are valid options.
		foreach ( $sanitized as $item ) {
			if ( ! in_array( $item, $options, true ) ) {
				return array(
					'valid'     => false,
					'error'     => sprintf( __( 'Invalid option: %s', 'wp-voting-plugin' ), $item ),
					'sanitized' => null,
				);
			}
		}

		return array(
			'valid'     => true,
			'error'     => '',
			'sanitized' => $sanitized,
		);
	}

	/**
	 * Disciplinary: single string matching a punishment level.
	 */
	private function validate_disciplinary( $data, array $options ): array {
		// Same as singleton — user picks one punishment level.
		return $this->validate_singleton( $data, $options );
	}

	/**
	 * Consent: the ballot is simply an objection. Any non-empty value is valid.
	 */
	private function validate_consent( $data ): array {
		if ( is_array( $data ) ) {
			$data = $data[0] ?? '';
		}

		$data = sanitize_text_field( (string) $data );

		if ( empty( $data ) ) {
			return array(
				'valid'     => false,
				'error'     => __( 'Invalid objection.', 'wp-voting-plugin' ),
				'sanitized' => null,
			);
		}

		return array(
			'valid'     => true,
			'error'     => '',
			'sanitized' => $data,
		);
	}

	/*
	------------------------------------------------------------------
	 *  Render helper (called from templates).
	 * ----------------------------------------------------------------*/

	/**
	 * Render the ballot form for a vote.
	 * This is called from the vote-detail template.
	 */
	public static function render_form( object $vote, int $user_id ): void {
		$decoded_options  = json_decode( $vote->voting_options, true );
		$options          = $decoded_options ? $decoded_options : array();
		$decoded_settings = json_decode( $vote->settings, true );
		$settings         = $decoded_settings ? $decoded_settings : array();

		$can_vote     = WPVP_Permissions::can_cast_vote( $user_id, $vote->id );
		$has_voted    = $user_id ? WPVP_Database::user_has_voted( $user_id, $vote->id ) : false;
		$allow_revote = ! empty( $settings['allow_revote'] );
		$show_form    = $can_vote || ( $has_voted && $allow_revote );

		// Get user's previous ballot if they've voted.
		$previous_ballot = null;
		if ( $has_voted ) {
			global $wpdb;
			$table = $wpdb->prefix . 'wpvp_ballots';
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT ballot_data FROM {$table} WHERE vote_id = %d AND user_id = %d",
					$vote->id,
					$user_id
				)
			);
			if ( $row ) {
				$previous_ballot = json_decode( $row->ballot_data, true );
				// Unwrap new ballot format: {choice: ..., voting_role: ...} → just the choice.
				if ( is_array( $previous_ballot ) && isset( $previous_ballot['choice'] ) ) {
					$previous_comment = $previous_ballot['voter_comment'] ?? '';
					$previous_ballot  = $previous_ballot['choice'];
				} else {
					$previous_comment = '';
				}
			}
		}

		include WPVP_PLUGIN_DIR . 'templates/public/ballot-form.php';
	}

	/**
	 * AJAX endpoint to fetch eligible roles for role selection UI.
	 */
	public function ajax_get_eligible_roles(): void {
		check_ajax_referer( 'wpvp_public', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'wp-voting-plugin' ) ) );
		}

		$vote_id = isset( $_POST['vote_id'] ) ? absint( $_POST['vote_id'] ) : 0;
		if ( ! $vote_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid vote.', 'wp-voting-plugin' ) ) );
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			wp_send_json_error( array( 'message' => __( 'Vote not found.', 'wp-voting-plugin' ) ) );
		}

		$eligible_roles = WPVP_Permissions::get_eligible_voting_roles( $user_id, $vote );

		wp_send_json_success(
			array(
				'eligible_roles'     => $eligible_roles,
				'requires_selection' => count( $eligible_roles ) > 1,
			)
		);
	}
}

// Boot the ballot AJAX handler.
new WPVP_Ballot();
