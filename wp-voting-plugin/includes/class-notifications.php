<?php
/**
 * Email notifications and cron-based automation.
 *
 * Handles:
 *  - Sending notifications when votes open or close.
 *  - Auto-opening votes when opening_date is reached.
 *  - Auto-closing votes when closing_date is reached.
 *  - Auto-processing votes on close (if enabled).
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Notifications {

	public function __construct() {
		// Schedule cron on plugin load if not already scheduled.
		add_action( 'wpvp_plugin_loaded', array( $this, 'schedule_cron' ) );

		// Cron callback.
		add_action( 'wpvp_daily_cron', array( $this, 'run_cron' ) );

		// Hook into stage transitions for notifications.
		add_action( 'wpvp_vote_stage_changed', array( $this, 'on_stage_change' ), 10, 3 );

		// Hook into ballot submission for voter confirmation emails.
		add_action( 'wpvp_ballot_submitted', array( $this, 'send_voter_confirmation' ), 10, 3 );

		// Handle scheduled closing reminders.
		add_action( 'wpvp_closing_reminder', array( $this, 'send_closing_reminder' ) );

		// Dedicated midnight cron for vote transitions.
		add_action( 'wpvp_midnight_cron', array( $this, 'run_midnight_cron' ) );
	}


	/**
	 * Ensure the daily cron event is scheduled.
	 */
	public function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'wpvp_daily_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'wpvp_daily_cron' );
		}

		if ( ! wp_next_scheduled( 'wpvp_midnight_cron' ) ) {
			$et       = new DateTimeZone( 'America/New_York' );
			$midnight = new DateTime( 'tomorrow midnight', $et );
			wp_schedule_event( $midnight->getTimestamp(), 'daily', 'wpvp_midnight_cron' );
		}
	}

	/**
	 * Cron callback: check for votes that should auto-open or auto-close.
	 */
	public function run_cron(): void {
		$this->auto_open_votes();
		$this->auto_close_votes();
		$this->catch_up_open_notifications();
	}

	/**
	 * Midnight cron: only vote open/close transitions.
	 * Runs daily at midnight ET so votes close on time.
	 */
	public function run_midnight_cron(): void {
		update_option( '_wpvp_midnight_cron_last_run', current_time( 'mysql' ), false );
		$this->auto_open_votes();
		$this->auto_close_votes();
	}


	private function auto_open_votes(): void {
		global $wpdb;

		$now   = current_time( 'mysql' );
		$table = WPVP_Database::votes_table();

		// Find scheduled (or legacy draft) votes whose opening_date has passed.
		$vote_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
             WHERE voting_stage IN ('scheduled', 'draft')
               AND opening_date IS NOT NULL
               AND opening_date <= %s",
				$now
			)
		);

		foreach ( $vote_ids as $vote_id ) {
			$old_vote  = WPVP_Database::get_vote( (int) $vote_id );
			$old_stage = $old_vote ? $old_vote->voting_stage : 'scheduled';

			WPVP_Database::update_vote( (int) $vote_id, array( 'voting_stage' => 'open' ) );

			/**
			 * Fires when a vote stage changes.
			 *
			 * @param int    $vote_id   The vote ID.
			 * @param string $new_stage The new stage.
			 * @param string $old_stage The previous stage.
			 */
			do_action( 'wpvp_vote_stage_changed', (int) $vote_id, 'open', $old_stage );
		}
	}


	private function auto_close_votes(): void {
		global $wpdb;

		$now           = current_time( 'mysql' );
		$table         = WPVP_Database::votes_table();
		$results_table = WPVP_Database::results_table();

		// Two-branch query:
		//   (a) 'open' votes whose closing_date has passed — normal close.
		//   (b) 'closed' votes with no results row — recovery for previous
		//       runs where stage was flipped but process() failed/crashed.
		$vote_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT v.id FROM {$table} v
                 LEFT JOIN {$results_table} r ON r.vote_id = v.id
                 WHERE r.id IS NULL
                   AND (
                        ( v.voting_stage = 'open'
                          AND v.closing_date IS NOT NULL
                          AND v.closing_date <= %s )
                     OR ( v.voting_stage = 'closed' )
                   )",
				$now
			)
		);

		foreach ( $vote_ids as $vote_id ) {
			$vote_id = (int) $vote_id;
			$before  = WPVP_Database::get_vote( $vote_id );
			if ( ! $before ) {
				continue;
			}
			$old_stage = $before->voting_stage;

			if ( 'open' === $old_stage ) {
				WPVP_Database::update_vote( $vote_id, array( 'voting_stage' => 'closed' ) );
			}

			$result = WPVP_Processor::process( $vote_id );
			if ( is_wp_error( $result ) ) {
				// Leave stage 'closed' so the next cron run picks it up via the
				// recovery branch above. Skip stage_changed so the close email
				// doesn't go out for a vote whose results aren't computed yet.
				error_log( sprintf(
					'[wp-voting-plugin] auto_close_votes: process() failed for vote %d: %s — %s',
					$vote_id,
					$result->get_error_code(),
					$result->get_error_message()
				) );
				continue;
			}

			$final_vote  = WPVP_Database::get_vote( $vote_id );
			$final_stage = $final_vote ? $final_vote->voting_stage : 'completed';
			do_action( 'wpvp_vote_stage_changed', $vote_id, $final_stage, $old_stage );
		}
	}


	/**
	 * Find 'open' votes whose opening_date has passed but no open notification was sent.
	 * Sends the notification and sets the flag to prevent duplicates.
	 */
	private function catch_up_open_notifications(): void {
		if ( ! get_option( 'wpvp_enable_email_notifications', false ) ) {
			return;
		}

		global $wpdb;
		$now   = current_time( 'mysql' );
		$table = WPVP_Database::votes_table();

		// Find open votes whose opening_date has passed.
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				 WHERE voting_stage = 'open'
				   AND opening_date IS NOT NULL
				   AND opening_date <= %s",
				$now
			)
		);

		foreach ( $votes as $row ) {
			$vote_id = (int) $row->id;
			// Skip if notification was already sent.
			if ( get_option( '_wpvp_open_notification_sent_' . $vote_id ) ) {
				continue;
			}

			$vote = WPVP_Database::get_vote( $vote_id );
			if ( ! $vote ) {
				continue;
			}

			$vote_settings = json_decode( $vote->settings, true );
			$vote_settings = $vote_settings ? $vote_settings : array();

			$this->send_vote_opened_notification( $vote, $vote_settings );
		}
	}


	/**
	 * Send notifications when a vote changes stage.
	 *
	 * @param int    $vote_id   The vote ID.
	 * @param string $new_stage The new stage.
	 * @param string $old_stage The previous stage.
	 */
	public function on_stage_change( int $vote_id, string $new_stage, string $old_stage ): void {
		if ( ! get_option( 'wpvp_enable_email_notifications', false ) ) {
			return;
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return;
		}

		// If opening_date hasn't arrived yet, set to scheduled so the hourly cron
		// (auto_open_votes) handles the transition and sends the email at the right time.
		if ( 'open' === $new_stage && ! empty( $vote->opening_date ) ) {
			if ( $vote->opening_date > current_time( 'mysql' ) ) {
				WPVP_Database::update_vote( $vote_id, array( 'voting_stage' => 'scheduled' ) );
				return;
			}
		}

		$vote_settings = json_decode( $vote->settings, true );
		$vote_settings = $vote_settings ? $vote_settings : array();

		switch ( $new_stage ) {
			case 'open':
				$this->send_vote_opened_notification( $vote, $vote_settings );
				break;
			case 'closed':
			case 'completed':
				$this->send_vote_closed_notification( $vote, $vote_settings );
				break;
		}
	}

	private function get_vote_settings( object $vote ): array {
		$settings = json_decode( $vote->settings, true );
		return $settings ? $settings : array();
	}

	/**
	 * Parse a comma-separated email string into a list of valid emails.
	 *
	 * @param string $email_string Comma-separated emails.
	 * @return string[] Valid email addresses.
	 */
	private function parse_email_list( string $email_string ): array {
		$emails = array_map( 'trim', explode( ',', $email_string ) );
		return array_filter( $emails, 'is_email' );
	}

	/**
	 * Send "vote is now open" email to eligible voters.
	 */
	private function send_vote_opened_notification( object $vote, array $vote_settings ): void {
		// Check per-vote setting. If setting exists and is explicitly off, skip.
		if ( isset( $vote_settings['notify_on_open'] ) && ! $vote_settings['notify_on_open'] ) {
			// Still schedule the closing reminder if enabled.
			if ( ! empty( $vote_settings['notify_before_close'] ) ) {
				$this->schedule_closing_reminder( $vote, $vote_settings );
			}
			return;
		}

		// Use per-vote custom recipients if set, then global default, then computed fallback.
		if ( ! empty( $vote_settings['notify_open_to'] ) ) {
			$recipients = $this->parse_email_list( $vote_settings['notify_open_to'] );
		} elseif ( '' !== get_option( 'wpvp_default_notify_open_to', '' ) ) {
			$recipients = $this->parse_email_list( get_option( 'wpvp_default_notify_open_to', '' ) );
		} else {
			$recipients = $this->get_notification_recipients( $vote );
			// Also send to admin notification email.
			$admin_email = $this->get_admin_notification_email();
			if ( $admin_email ) {
				$recipients[] = $admin_email;
			}
			$recipients = array_unique( $recipients );
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: vote title */
			__( 'Vote Now Open: %s', 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		$page_ids = get_option( 'wpvp_page_ids', array() );
		$vote_url = ! empty( $page_ids['cast-vote'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['cast-vote'] ) )
			: home_url();

		$message = sprintf(
			/* translators: %s: vote title */
			__( "A new vote is now open for your participation:\n\n%s", 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		// Description.
		if ( ! empty( $vote->proposal_description ) ) {
			$message .= "\n\n" . __( 'Description:', 'wp-voting-plugin' ) . "\n"
				. wp_strip_all_tags( $vote->proposal_description );
		}

		// Voting options.
		$raw_options = json_decode( $vote->voting_options, true );
		if ( is_array( $raw_options ) && ! empty( $raw_options ) ) {
			$message .= "\n\n" . __( 'Options:', 'wp-voting-plugin' );
			$i = 1;
			foreach ( $raw_options as $opt ) {
				$label = is_array( $opt ) ? ( $opt['text'] ?? '' ) : (string) $opt;
				if ( '' !== $label ) {
					$message .= "\n  " . $i . '. ' . $label;
					$i++;
				}
			}
		}

		// Proposal metadata.
		$meta_lines = array();
		$classifications = json_decode( $vote->classification, true );
		if ( is_array( $classifications ) && ! empty( $classifications ) ) {
			$meta_lines[] = __( 'Proposal Type:', 'wp-voting-plugin' ) . ' ' . implode( ', ', $classifications );
		}
		if ( ! empty( $vote->proposed_by ) ) {
			$meta_lines[] = __( 'Proposed By:', 'wp-voting-plugin' ) . ' ' . $vote->proposed_by;
		}
		if ( ! empty( $vote->seconded_by ) ) {
			$meta_lines[] = __( 'Seconded By:', 'wp-voting-plugin' ) . ' ' . $vote->seconded_by;
		}
		if ( ! empty( $meta_lines ) ) {
			$message .= "\n\n" . implode( "\n", $meta_lines );
		}

		$message .= "\n\n" . sprintf(
			/* translators: %s: vote URL */
			__( 'Cast your vote: %s', 'wp-voting-plugin' ),
			$vote_url
		);

		if ( $vote->closing_date ) {
			$message .= "\n\n" . sprintf(
				/* translators: %s: closing date */
				__( 'Voting closes: %s', 'wp-voting-plugin' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), WPVP_Database::local_timestamp( $vote->closing_date ) )
			);
		}

		$this->send_bulk_email( $recipients, $subject, $message );

		// Mark that open notification was sent (prevents duplicate sends from catch-up).
		update_option( '_wpvp_open_notification_sent_' . $vote->id, time(), false );

		// Schedule closing reminder if enabled.
		if ( ! isset( $vote_settings['notify_before_close'] ) || ! empty( $vote_settings['notify_before_close'] ) ) {
			$this->schedule_closing_reminder( $vote, $vote_settings );
		}
	}

	/**
	 * Send "vote has closed" email to voters who participated.
	 */
	private function send_vote_closed_notification( object $vote, array $vote_settings ): void {
		// Prevent duplicate close notifications.
		if ( get_option( '_wpvp_close_notification_sent_' . $vote->id ) ) {
			return;
		}

		// Check per-vote setting.
		if ( isset( $vote_settings['notify_on_close'] ) && ! $vote_settings['notify_on_close'] ) {
			return;
		}

		// Use per-vote custom recipients if set, then global default, then computed fallback.
		if ( ! empty( $vote_settings['notify_close_to'] ) ) {
			$recipients = $this->parse_email_list( $vote_settings['notify_close_to'] );
		} elseif ( '' !== get_option( 'wpvp_default_notify_close_to', '' ) ) {
			$recipients = $this->parse_email_list( get_option( 'wpvp_default_notify_close_to', '' ) );
		} else {
			// Default: voters who participated + admin.
			$recipients = $this->get_voters( $vote->id );
			$admin_email = $this->get_admin_notification_email();
			if ( $admin_email ) {
				$recipients[] = $admin_email;
			}
			$recipients = array_unique( $recipients );
		}

		$subject = sprintf(
			/* translators: %s: vote title */
			__( 'Vote Completed: %s', 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		// Build full HTML email with results, voted/not-voted entities.
		$message = $this->build_closed_vote_html( $vote );

		if ( ! empty( $recipients ) ) {
			$this->send_bulk_email( $recipients, $subject, $message, true );
		}

		update_option( '_wpvp_close_notification_sent_' . $vote->id, time(), false );
	}


	/**
	 * Get email addresses of users eligible to vote on a proposal.
	 * Uses a capped query to avoid memory issues on large sites.
	 *
	 * @return string[] Array of email addresses.
	 */
	private function get_notification_recipients( object $vote ): array {
		$visibility = $vote->visibility ?? 'private';

		// Public votes: don't spam everyone on the site.
		// Only notify admins for public votes.
		if ( 'public' === $visibility ) {
			return $this->get_admin_emails();
		}

		// Private: all logged-in users (capped at 500).
		if ( 'private' === $visibility ) {
			$users = get_users(
				array(
					'fields' => 'user_email',
					'number' => 500,
				)
			);
			return array_filter( $users );
		}

		// Restricted: users matching allowed_roles.
		$allowed_roles = json_decode( $vote->allowed_roles, true );
		if ( empty( $allowed_roles ) ) {
			return $this->get_admin_emails();
		}

		// Get users by WP roles (AccessSchema users can't be enumerated).
		$emails = array();
		foreach ( $allowed_roles as $role ) {
			$role   = sanitize_text_field( $role );
			$users  = get_users(
				array(
					'role'   => $role,
					'fields' => 'user_email',
					'number' => 200,
				)
			);
			$emails = array_merge( $emails, $users );
		}

		return array_unique( array_filter( $emails ) );
	}

	private function get_voters( int $vote_id ): array {
		global $wpdb;

		$emails = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT u.user_email
             FROM ' . WPVP_Database::ballots_table() . " b
             INNER JOIN {$wpdb->users} u ON b.user_id = u.ID
             WHERE b.vote_id = %d",
				$vote_id
			)
		);

		return array_filter( $emails ? $emails : array() );
	}

	private function get_admin_emails(): array {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'fields' => 'user_email',
			)
		);
		return array_filter( $admins );
	}

	/**
	 * Send an email to multiple recipients using BCC to protect privacy.
	 */
	private function send_bulk_email( array $recipients, string $subject, string $message, bool $html = false ): void {
		if ( empty( $recipients ) ) {
			return;
		}

		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$headers = array(
			'From: ' . $site_name . ' <' . $admin_email . '>',
		);

		if ( $html ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		// Send individually to avoid exposing email addresses.
		// Batched in groups of 50 to avoid timeouts.
		$batches = array_chunk( $recipients, 50 );
		foreach ( $batches as $batch ) {
			foreach ( $batch as $email ) {
				wp_mail( $email, $subject, $message, $headers );
			}
		}
	}

	/**
	 * Get the admin notification email address.
	 * Falls back to site admin email if not set.
	 *
	 * @return string
	 */
	private function get_admin_notification_email(): string {
		$email = get_option( 'wpvp_admin_notification_email', '' );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}
		return $email;
	}


	/**
	 * Send confirmation email to voter after they cast their ballot.
	 *
	 * @param int   $vote_id     Vote ID.
	 * @param int   $user_id     User ID.
	 * @param mixed $ballot_data Ballot data.
	 */
	public function send_voter_confirmation( int $vote_id, int $user_id, $ballot_data ): void {
		if ( ! get_option( 'wpvp_enable_email_notifications', false ) ) {
			return;
		}

		// Check if user opted in to vote notifications.
		$opted_in = get_user_meta( $user_id, 'wpvp_vote_' . $vote_id . '_notify', true );
		if ( ! $opted_in ) {
			return;
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		$user = get_user_by( 'id', $user_id );

		// Check per-vote setting: if voter confirmations are disabled for this vote, skip.
		if ( $vote ) {
			$vote_settings = $this->get_vote_settings( $vote );
			if ( isset( $vote_settings['notify_voter_confirmation'] ) && ! $vote_settings['notify_voter_confirmation'] ) {
				return;
			}
		}

		if ( ! $vote || ! $user ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: vote title */
			__( 'Vote Confirmation: %s', 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		$page_ids = get_option( 'wpvp_page_ids', array() );
		$vote_url = ! empty( $page_ids['cast-vote'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['cast-vote'] ) )
			: home_url();

		// Format ballot for display.
		$ballot_display = $this->format_ballot_for_email( $ballot_data, $vote->voting_type );

		$message = sprintf(
			/* translators: 1: user display name, 2: vote title */
			__( "Hello %1\$s,\n\nYour vote has been recorded for:\n%2\$s\n\n", 'wp-voting-plugin' ),
			$user->display_name,
			$vote->proposal_name
		);

		$message .= __( "Your selection:\n", 'wp-voting-plugin' ) . $ballot_display . "\n\n";

		// Check if revoting is allowed.
		$decoded_settings = json_decode( $vote->settings, true );
		$settings         = $decoded_settings ? $decoded_settings : array();
		$allow_revote     = ! empty( $settings['allow_revote'] );

		if ( $allow_revote ) {
			$message .= sprintf(
				/* translators: %s: vote URL */
				__( "You can change your vote at any time before voting closes:\n%s\n\n", 'wp-voting-plugin' ),
				$vote_url
			);
		}

		if ( $vote->closing_date ) {
			$message .= sprintf(
				/* translators: %s: closing date */
				__( 'Voting closes: %s', 'wp-voting-plugin' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), WPVP_Database::local_timestamp( $vote->closing_date ) )
			);
		}

		$headers = array(
			'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
		);

		// Get custom email addresses (user may have specified additional recipients).
		$custom_emails = get_user_meta( $user_id, 'wpvp_vote_' . $vote_id . '_notify_emails', true );

		if ( ! empty( $custom_emails ) ) {
			$email_list = array_map( 'trim', explode( ',', $custom_emails ) );
			$email_list = array_filter( $email_list, 'is_email' );
		} else {
			$email_list = array( $user->user_email );
		}

		foreach ( $email_list as $recipient ) {
			wp_mail( $recipient, $subject, $message, $headers );
		}
	}

	/**
	 * Format ballot data for email display.
	 *
	 * @param mixed  $ballot_data Ballot data (may be the full payload with choice/voting_role/etc.).
	 * @param string $voting_type Voting type.
	 * @return string
	 */
	private function format_ballot_for_email( $ballot_data, string $voting_type ): string {
		$decoded = is_string( $ballot_data ) ? json_decode( $ballot_data, true ) : $ballot_data;

		// Unwrap the enriched ballot payload to extract the actual choice and metadata.
		$choice        = $decoded;
		$voting_role   = '';
		$voter_comment = '';

		if ( is_array( $decoded ) && isset( $decoded['choice'] ) ) {
			$choice        = $decoded['choice'];
			$voting_role   = isset( $decoded['voting_role'] ) ? $decoded['voting_role'] : '';
			$voter_comment = isset( $decoded['voter_comment'] ) ? $decoded['voter_comment'] : '';
		}

		$output = '';

		// Format the choice based on voting type.
		if ( in_array( $voting_type, array( 'rcv', 'stv', 'condorcet', 'sequential_rcv' ), true ) && is_array( $choice ) ) {
			// Ranked voting types.
			foreach ( $choice as $rank => $option ) {
				$output .= '  ' . ( $rank + 1 ) . '. ' . $option . "\n";
			}
		} elseif ( 'consent' === $voting_type ) {
			$output .= "  Objection filed\n";
		} elseif ( is_string( $choice ) ) {
			$output .= '  ' . $choice . "\n";
		} elseif ( is_array( $choice ) ) {
			// Fallback for array choices that aren't ranked.
			foreach ( $choice as $item ) {
				$output .= '  - ' . $item . "\n";
			}
		}

		// Append voting role if present.
		if ( ! empty( $voting_role ) ) {
			$output .= "\n" . sprintf(
				__( 'Voting as: %s', 'wp-voting-plugin' ),
				$voting_role
			) . "\n";
		}

		// Append comment if present.
		if ( ! empty( $voter_comment ) ) {
			$output .= "\n" . sprintf(
				__( 'Your comment: %s', 'wp-voting-plugin' ),
				$voter_comment
			) . "\n";
		}

		return rtrim( $output );
	}


	/**
	 * Schedule a reminder email for 9am on the day the vote closes.
	 *
	 * @param object $vote Vote object.
	 */
	private function schedule_closing_reminder( object $vote, array $vote_settings = array() ): void {
		if ( empty( $vote->closing_date ) ) {
			return;
		}

		// Check per-vote setting.
		if ( isset( $vote_settings['notify_before_close'] ) && ! $vote_settings['notify_before_close'] ) {
			return;
		}

		// Target: 9am ET on closing day, with 15-hour minimum window before close.
		// If less than 15 hours between 9am ET and close, send 6pm ET the evening before.
		$close_timestamp = strtotime( $vote->closing_date );

		$et       = new DateTimeZone( 'America/New_York' );
		$close_dt = new DateTime( $vote->closing_date, $et );

		// 9am ET on the closing day.
		$reminder_dt = clone $close_dt;
		$reminder_dt->setTime( 9, 0, 0 );

		// If less than 15 hours between reminder and close, use 6pm ET the evening before.
		$hours_until_close = ( $close_timestamp - $reminder_dt->getTimestamp() ) / 3600;
		if ( $hours_until_close < 15 ) {
			$reminder_dt->modify( '-1 day' );
			$reminder_dt->setTime( 18, 0, 0 );
		}

		$reminder_time = $reminder_dt->getTimestamp();

		// Clear any previously scheduled reminder for this vote.
		wp_clear_scheduled_hook( 'wpvp_closing_reminder', array( $vote->id ) );

		// Only schedule if the reminder time is in the future.
		if ( $reminder_time > time() ) {
			wp_schedule_single_event( $reminder_time, 'wpvp_closing_reminder', array( $vote->id ) );
		}
	}

	/**
	 * Send reminder email at 9am on closing day.
	 *
	 * @param int $vote_id Vote ID.
	 */
	public function send_closing_reminder( int $vote_id ): void {
		if ( ! get_option( 'wpvp_enable_email_notifications', false ) ) {
			return;
		}

		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote || 'open' !== $vote->voting_stage ) {
			return;
		}

		$vote_settings = $this->get_vote_settings( $vote );

		// Use per-vote custom recipients if set, then global default, then admin.
		if ( ! empty( $vote_settings['notify_reminder_to'] ) ) {
			$recipients = $this->parse_email_list( $vote_settings['notify_reminder_to'] );
		} elseif ( '' !== get_option( 'wpvp_default_notify_reminder_to', '' ) ) {
			$recipients = $this->parse_email_list( get_option( 'wpvp_default_notify_reminder_to', '' ) );
		} else {
			$admin_email = $this->get_admin_notification_email();
			$recipients  = $admin_email ? array( $admin_email ) : array();
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: vote title */
			__( 'Vote Closing Today: %s', 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		$page_ids = get_option( 'wpvp_page_ids', array() );
		$vote_url = ! empty( $page_ids['cast-vote'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['cast-vote'] ) )
			: home_url();

		// Get ballot count.
		global $wpdb;
		$table        = $wpdb->prefix . 'wpvp_ballots';
		$ballot_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE vote_id = %d", $vote_id ) );

		$message = sprintf(
			/* translators: 1: vote title */
			__( "Reminder: The following vote closes today:\n\n%1\$s\n\n", 'wp-voting-plugin' ),
			$vote->proposal_name
		);

		$message .= sprintf(
			/* translators: %d: number of ballots */
			__( "Ballots cast so far: %d\n", 'wp-voting-plugin' ),
			intval( $ballot_count )
		);

		$voted_entities     = $this->get_voted_entities( $vote );
		$not_voted_entities = $this->get_not_voted_entities( $vote, $voted_entities );

		if ( ! empty( $voted_entities ) ) {
			$message .= "\n" . sprintf( 'Voted (%d):', count( $voted_entities ) ) . "\n";
			foreach ( $voted_entities as $title ) {
				$message .= '  - ' . $title . "\n";
			}
		}
		if ( ! empty( $not_voted_entities ) ) {
			$message .= "\n" . sprintf( 'Not Yet Voted (%d):', count( $not_voted_entities ) ) . "\n";
			foreach ( $not_voted_entities as $title ) {
				$message .= '  - ' . $title . "\n";
			}
		}

		if ( $vote->closing_date ) {
			$message .= sprintf(
				/* translators: %s: closing time */
				__( 'Voting closes: %s', 'wp-voting-plugin' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), WPVP_Database::local_timestamp( $vote->closing_date ) )
			);
		}

		$message .= "\n\n" . sprintf(
			/* translators: %s: vote URL */
			__( "Vote URL: %s", 'wp-voting-plugin' ),
			$vote_url
		);

		$this->send_bulk_email( $recipients, $subject, $message );
	}

	/**
	 * Build full HTML email for a closed/completed vote.
	 *
	 * @param object $vote Vote object.
	 * @return string HTML email body.
	 */
	private function build_closed_vote_html( object $vote ): string {
		$page_ids    = get_option( 'wpvp_page_ids', array() );
		$results_url = ! empty( $page_ids['vote-results'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['vote-results'] ) )
			: home_url();
		$site_name   = get_bloginfo( 'name' );
		$results     = WPVP_Database::get_results( $vote->id );

		// Vote type label.
		$types     = WPVP_Database::get_vote_types();
		$type_info = $types[ $vote->voting_type ] ?? array();
		$type_label = $type_info['label'] ?? $vote->voting_type;

		// Winner / outcome.
		$winner_html = '';
		if ( $results ) {
			$winner = $results->winner_data ? $results->winner_data : array();
			$final  = $results->final_results ? $results->final_results : array();

			// Check consent passed field.
			if ( isset( $final['passed'] ) ) {
				if ( $final['passed'] ) {
					$winner_html = '<div style="background:#ecf7ed;border:1px solid #46b450;color:#1e4620;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:16px;"><strong>RESULT:</strong> Passed by Consent</div>';
				} else {
					$winner_html = '<div style="background:#fce4e4;border:1px solid #d63638;color:#8a1f1f;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:16px;"><strong>RESULT:</strong> Objected</div>';
				}
			} elseif ( ! empty( $winner['tie'] ) && ! empty( $winner['tied_candidates'] ) ) {
				// Build a combined result line: winners + (tied candidates).
				$parts      = array();
				$has_winners = ! empty( $winner['winners'] );
				if ( $has_winners ) {
					foreach ( $winner['winners'] as $w ) {
						$parts[] = esc_html( $w );
					}
				}
				$parts[] = '(' . esc_html( implode( ', ', $winner['tied_candidates'] ) ) . ' tied)';
				$label   = $has_winners ? 'RESULTS' : 'TIE';
				$bg      = $has_winners ? '#fff8e5' : '#fff8e5';
				$border  = $has_winners ? '#dba617' : '#dba617';
				$color   = $has_winners ? '#654b00' : '#654b00';
				$winner_html = '<div style="background:' . $bg . ';border:1px solid ' . $border . ';color:' . $color . ';padding:12px 16px;border-radius:6px;margin:16px 0;font-size:16px;"><strong>' . $label . ':</strong> ' . implode( ', ', $parts ) . '</div>';
			} elseif ( ! empty( $winner['winners'] ) && count( $winner['winners'] ) > 1 ) {
				$winner_html = '<div style="background:#ecf7ed;border:1px solid #46b450;color:#1e4620;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:16px;"><strong>WINNERS:</strong> ' . esc_html( implode( ', ', $winner['winners'] ) ) . '</div>';
			} elseif ( ! empty( $winner['winner'] ) ) {
				$winner_html = '<div style="background:#ecf7ed;border:1px solid #46b450;color:#1e4620;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:16px;"><strong>WINNER:</strong> ' . esc_html( $winner['winner'] ) . '</div>';
			}
		}

		// Vote counts table.
		$counts_html = '';
		if ( $results && ! empty( $results->final_results['vote_counts'] ) ) {
			$counts = $results->final_results['vote_counts'];
			arsort( $counts );
			$counts_html .= '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
			$counts_html .= '<tr style="background:#f6f7f7;"><th style="padding:8px 12px;text-align:left;border-bottom:2px solid #dcdcde;font-size:13px;">Option</th><th style="padding:8px 12px;text-align:right;border-bottom:2px solid #dcdcde;font-size:13px;">Votes</th></tr>';
			foreach ( $counts as $option => $count ) {
				$counts_html .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #f0f0f1;">' . esc_html( $option ) . '</td><td style="padding:8px 12px;text-align:right;border-bottom:1px solid #f0f0f1;">' . intval( $count ) . '</td></tr>';
			}
			$counts_html .= '</table>';
		}

		// Voting options list.
		$options_html = '';
		$raw_options  = json_decode( $vote->voting_options, true );
		if ( is_array( $raw_options ) && ! empty( $raw_options ) ) {
			$options_html .= '<h3 style="font-size:14px;margin:16px 0 8px;">Voting Options</h3><ul style="margin:0 0 0 20px;padding:0;">';
			foreach ( $raw_options as $opt ) {
				$text = is_array( $opt ) ? ( $opt['text'] ?? '' ) : $opt;
				if ( $text ) {
					$options_html .= '<li style="padding:2px 0;">' . esc_html( $text ) . '</li>';
				}
			}
			$options_html .= '</ul>';
		}

		// Voted / Not Voted entities.
		$voted_entities     = $this->get_voted_entities( $vote );
		$not_voted_entities = $this->get_not_voted_entities( $vote, $voted_entities );

		$voted_html = '';
		if ( ! empty( $voted_entities ) ) {
			$voted_html .= '<h3 style="font-size:14px;margin:16px 0 8px;color:#1e4620;">Voted (' . count( $voted_entities ) . ')</h3>';
			$voted_html .= '<ul style="margin:0 0 0 20px;padding:0;">';
			foreach ( $voted_entities as $title ) {
				$voted_html .= '<li style="padding:2px 0;">' . esc_html( $title ) . '</li>';
			}
			$voted_html .= '</ul>';
		}

		$not_voted_html = '';
		if ( ! empty( $not_voted_entities ) ) {
			$not_voted_html .= '<h3 style="font-size:14px;margin:16px 0 8px;color:#8a1f1f;">Not Voted (' . count( $not_voted_entities ) . ')</h3>';
			$not_voted_html .= '<ul style="margin:0 0 0 20px;padding:0;">';
			foreach ( $not_voted_entities as $title ) {
				$not_voted_html .= '<li style="padding:2px 0;">' . esc_html( $title ) . '</li>';
			}
			$not_voted_html .= '</ul>';
		}

		// Build the HTML email.
		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#1d2327;line-height:1.6;max-width:640px;margin:0 auto;padding:20px;">';

		// Header.
		$html .= '<h2 style="margin:0 0 8px;">' . esc_html( $vote->proposal_name ) . '</h2>';
		$html .= '<p style="color:#646970;font-size:13px;margin:0 0 16px;">' . esc_html( $type_label );
		if ( ! empty( $vote->classification ) ) {
			$html .= ' &mdash; ' . esc_html( $vote->classification );
		}
		$html .= '</p>';

		// Description.
		if ( ! empty( $vote->proposal_description ) ) {
			$html .= '<div style="margin:0 0 16px;padding:12px 16px;background:#f9f9f9;border-left:4px solid #2271b1;border-radius:0 4px 4px 0;">' . wp_kses_post( wpautop( $vote->proposal_description ) ) . '</div>';
		}

		// Proposed by / Seconded by.
		if ( ! empty( $vote->proposed_by ) || ! empty( $vote->seconded_by ) ) {
			$html .= '<p style="color:#646970;font-size:13px;margin:0 0 16px;">';
			if ( ! empty( $vote->proposed_by ) ) {
				$html .= 'Proposed by: ' . esc_html( $vote->proposed_by );
			}
			if ( ! empty( $vote->seconded_by ) ) {
				$html .= ( ! empty( $vote->proposed_by ) ? ' | ' : '' ) . 'Seconded by: ' . esc_html( $vote->seconded_by );
			}
			$html .= '</p>';
		}

		$html .= $options_html;
		$html .= $winner_html;
		$html .= $counts_html;

		if ( $results ) {
			$html .= '<p style="color:#646970;font-size:13px;">Total votes: ' . intval( $results->total_votes ) . '</p>';
		}

		$html .= $voted_html;
		$html .= $not_voted_html;

		// Results link.
		$html .= '<p style="margin:24px 0 0;"><a href="' . esc_url( $results_url ) . '" style="display:inline-block;padding:10px 20px;background:#2271b1;color:#fff;text-decoration:none;border-radius:4px;font-weight:600;">View Full Results</a></p>';

		// Footer.
		$html .= '<hr style="border:none;border-top:1px solid #dcdcde;margin:24px 0;">';
		$html .= '<p style="color:#646970;font-size:12px;">' . esc_html( $site_name ) . '</p>';
		$html .= '</body></html>';

		return $html;
	}

	/**
	 * Get entity titles of entities that voted on a given vote.
	 * Returns alphabetically sorted, chronicles first.
	 *
	 * @param object $vote Vote object.
	 * @return string[] Entity titles.
	 */
	private function get_voted_entities( object $vote ): array {
		global $wpdb;
		$table   = WPVP_Database::ballots_table();
		$ballots = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ballot_data FROM {$table} WHERE vote_id = %d",
				$vote->id
			),
			ARRAY_A
		);

		if ( empty( $ballots ) ) {
			return array();
		}

		$chronicles = array();
		$others     = array();

		foreach ( $ballots as $ballot ) {
			$data        = json_decode( $ballot['ballot_data'], true );
			$voting_role = is_array( $data ) ? ( $data['voting_role'] ?? '' ) : '';
			if ( empty( $voting_role ) ) {
				continue;
			}

			$title = $this->resolve_entity_title( $voting_role );
			$parts = explode( '/', $voting_role );
			$type  = strtolower( $parts[0] ?? '' );

			if ( 'chronicle' === $type ) {
				$chronicles[ $title ] = true;
			} else {
				$others[ $title ] = true;
			}
		}

		$chronicle_titles = array_keys( $chronicles );
		$other_titles     = array_keys( $others );
		sort( $chronicle_titles );
		sort( $other_titles );

		return array_merge( $chronicle_titles, $other_titles );
	}

	/**
	 * Get entity titles of entities that did NOT vote on a given vote.
	 * Returns alphabetically sorted, chronicles first.
	 *
	 * @param object   $vote            Vote object.
	 * @param string[] $voted_entities  Entity titles that voted (from get_voted_entities).
	 * @return string[] Entity titles that did not vote.
	 */
	private function get_not_voted_entities( object $vote, array $voted_entities ): array {
		if ( 'restricted' !== $vote->voting_eligibility ) {
			return array();
		}

		$voted_set = array_flip( $voted_entities );

		// Get all eligible users.
		global $wpdb;
		$asc_mode = get_option( 'wpvp_accessschema_mode', 'none' );

		if ( 'none' !== $asc_mode ) {
			$all_users = $this->query_eligible_users( $vote );
		} else {
			$voting_roles = json_decode( $vote->voting_roles, true );
			$role_slugs   = array();
			if ( is_array( $voting_roles ) ) {
				foreach ( $voting_roles as $r ) {
					$r = sanitize_text_field( $r );
					if ( '' !== $r && false === strpos( $r, '/' ) && false === strpos( $r, '*' ) ) {
						$role_slugs[] = $r;
					}
				}
			}
			$all_users = get_users( array(
				'fields'   => array( 'ID', 'display_name' ),
				'role__in' => $role_slugs,
			) );
		}

		// Get voted user IDs.
		$table          = WPVP_Database::ballots_table();
		$voted_user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$table} WHERE vote_id = %d",
				$vote->id
			)
		);
		$voted_uid_set = array_flip( $voted_user_ids );

		$chronicles = array();
		$others     = array();

		foreach ( $all_users as $wp_user ) {
			$uid = (int) $wp_user->ID;
			if ( isset( $voted_uid_set[ $uid ] ) ) {
				continue;
			}

			$roles = WPVP_Permissions::get_eligible_voting_roles( $uid, $vote );
			if ( empty( $roles ) ) {
				continue;
			}

			$role  = $roles[0];
			$title = $this->resolve_entity_title( $role );

			// Skip if already in voted list (entity-level dedup).
			if ( isset( $voted_set[ $title ] ) ) {
				continue;
			}

			$parts = explode( '/', $role );
			$type  = strtolower( $parts[0] ?? '' );

			if ( 'chronicle' === $type ) {
				$chronicles[ $title ] = true;
			} else {
				$others[ $title ] = true;
			}
		}

		$chronicle_titles = array_keys( $chronicles );
		$other_titles     = array_keys( $others );
		sort( $chronicle_titles );
		sort( $other_titles );

		return array_merge( $chronicle_titles, $other_titles );
	}

	/**
	 * Resolve entity title from a voting role path.
	 *
	 * @param string $voting_role Role path (e.g., "chronicle/kony/cm").
	 * @return string Entity title.
	 */
	private function resolve_entity_title( string $voting_role ): string {
		$parts = explode( '/', trim( $voting_role, '/' ) );
		$type  = strtolower( $parts[0] ?? '' );
		$slug  = $parts[1] ?? '';

		if ( empty( $slug ) ) {
			return ucfirst( $type );
		}

		// Resolve via owbn-client.
		if ( function_exists( 'owc_resolve_asc_path' ) ) {
			$title = owc_resolve_asc_path( $type . '/' . $slug, 'title', false );
			if ( $title ) {
				return $title;
			}
		}

		return ucfirst( $slug );
	}

	/**
	 * Query users whose AccessSchema cached roles match the vote's role patterns.
	 *
	 * @param object $vote Vote object with voting_roles JSON.
	 * @return array Array of user objects with ID and display_name.
	 */
	private function query_eligible_users( object $vote ): array {
		global $wpdb;

		$role_patterns = array();
		$decoded       = isset( $vote->voting_roles ) ? json_decode( $vote->voting_roles, true ) : null;
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $r ) {
				$r = trim( $r );
				if ( '' !== $r ) {
					$role_patterns[] = $r;
				}
			}
		}

		$role_patterns = array_unique( $role_patterns );
		if ( empty( $role_patterns ) ) {
			return array();
		}

		$like_clauses = array();
		foreach ( $role_patterns as $pattern ) {
			$segments = preg_split( '/(\*\*|\*)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE );
			$like     = '';
			foreach ( $segments as $seg ) {
				if ( '**' === $seg || '*' === $seg ) {
					$like .= '%';
				} else {
					$like .= $wpdb->esc_like( $seg );
				}
			}
			$like           = '%' . $wpdb->esc_like( '"' ) . $like . $wpdb->esc_like( '"' ) . '%';
			$like_clauses[] = $wpdb->prepare( 'um.meta_value LIKE %s', $like );
		}

		if ( empty( $like_clauses ) ) {
			return array();
		}

		$where = implode( ' OR ', $like_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			"SELECT DISTINCT u.ID, u.display_name
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
			 WHERE um.meta_key = 'accessschema_cached_roles'
			   AND ( {$where} )"
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Format results summary for email.
	 *
	 * @param object $results Results object.
	 * @return string
	 */
	private function format_results_summary( object $results ): string {
		$final  = $results->final_results ? $results->final_results : array();
		$winner = $results->winner_data ? $results->winner_data : array();

		$output = '';

		// Winner display.
		if ( ! empty( $winner['winner'] ) ) {
			$output .= sprintf(
				/* translators: %s: winner name */
				__( 'WINNER: %s', 'wp-voting-plugin' ),
				$winner['winner']
			) . "\n\n";
		} elseif ( ! empty( $winner['winners'] ) ) {
			$output .= sprintf(
				/* translators: %s: comma-separated winner names */
				__( 'WINNERS: %s', 'wp-voting-plugin' ),
				implode( ', ', $winner['winners'] )
			) . "\n\n";
		}

		// Vote counts.
		if ( ! empty( $final['vote_counts'] ) ) {
			$counts = $final['vote_counts'];
			arsort( $counts );

			$output .= __( 'Results:', 'wp-voting-plugin' ) . "\n";
			foreach ( $counts as $option => $count ) {
				$percentage = isset( $final['percentages'][ $option ] ) ? $final['percentages'][ $option ] : 0;
				$output    .= sprintf( '  %s: %d (%s%%)', $option, $count, number_format( $percentage, 1 ) ) . "\n";
			}

			$output .= "\n" . sprintf(
				/* translators: %d: total vote count */
				__( 'Total votes: %d', 'wp-voting-plugin' ),
				intval( $results->total_votes )
			);
		}

		return $output;
	}
}
