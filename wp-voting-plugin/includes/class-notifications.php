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
	}

	/*
	------------------------------------------------------------------
	 *  Cron scheduling.
	 * ----------------------------------------------------------------*/

	/**
	 * Ensure the daily cron event is scheduled.
	 */
	public function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'wpvp_daily_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'wpvp_daily_cron' );
		}
	}

	/**
	 * Cron callback: check for votes that should auto-open or auto-close.
	 */
	public function run_cron(): void {
		$this->auto_open_votes();
		$this->auto_close_votes();
	}

	/*
	------------------------------------------------------------------
	 *  Auto-open: draft → open when opening_date has passed.
	 * ----------------------------------------------------------------*/

	private function auto_open_votes(): void {
		global $wpdb;

		$now   = current_time( 'mysql' );
		$table = WPVP_Database::votes_table();

		// Find draft votes whose opening_date has passed.
		$vote_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
             WHERE voting_stage = 'draft'
               AND opening_date IS NOT NULL
               AND opening_date <= %s",
				$now
			)
		);

		foreach ( $vote_ids as $vote_id ) {
			WPVP_Database::update_vote( (int) $vote_id, array( 'voting_stage' => 'open' ) );

			/**
			 * Fires when a vote stage changes.
			 *
			 * @param int    $vote_id   The vote ID.
			 * @param string $new_stage The new stage.
			 * @param string $old_stage The previous stage.
			 */
			do_action( 'wpvp_vote_stage_changed', (int) $vote_id, 'open', 'draft' );
		}
	}

	/*
	------------------------------------------------------------------
	 *  Auto-close: open → closed when closing_date has passed.
	 * ----------------------------------------------------------------*/

	private function auto_close_votes(): void {
		global $wpdb;

		$now   = current_time( 'mysql' );
		$table = WPVP_Database::votes_table();

		// Find open votes whose closing_date has passed.
		$vote_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
             WHERE voting_stage = 'open'
               AND closing_date IS NOT NULL
               AND closing_date <= %s",
				$now
			)
		);

		foreach ( $vote_ids as $vote_id ) {
			WPVP_Database::update_vote( (int) $vote_id, array( 'voting_stage' => 'closed' ) );

			do_action( 'wpvp_vote_stage_changed', (int) $vote_id, 'closed', 'open' );

			// Auto-process results on close.
			WPVP_Processor::process( (int) $vote_id );
		}
	}

	/*
	------------------------------------------------------------------
	 *  Email notifications on stage change.
	 * ----------------------------------------------------------------*/

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

		switch ( $new_stage ) {
			case 'open':
				$this->send_vote_opened_notification( $vote );
				break;
			case 'closed':
			case 'completed':
				$this->send_vote_closed_notification( $vote );
				break;
		}
	}

	/**
	 * Send "vote is now open" email to eligible voters.
	 */
	private function send_vote_opened_notification( object $vote ): void {
		$recipients = $this->get_notification_recipients( $vote );
		if ( empty( $recipients ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: 1: site name, 2: vote title */
			__( '[%1$s] Vote Now Open: %2$s', 'wp-voting-plugin' ),
			$site_name,
			$vote->proposal_name
		);

		$page_ids = get_option( 'wpvp_page_ids', array() );
		$vote_url = ! empty( $page_ids['cast-vote'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['cast-vote'] ) )
			: home_url();

		$message = sprintf(
			/* translators: 1: vote title, 2: vote URL */
			__( "A new vote is now open for your participation:\n\n%1\$s\n\nCast your vote: %2\$s", 'wp-voting-plugin' ),
			$vote->proposal_name,
			$vote_url
		);

		if ( $vote->closing_date ) {
			$message .= "\n\n" . sprintf(
				/* translators: %s: closing date */
				__( 'Voting closes: %s', 'wp-voting-plugin' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) )
			);
		}

		$this->send_bulk_email( $recipients, $subject, $message );

		// Also send to admin notification email.
		$admin_email = $this->get_admin_notification_email();
		if ( $admin_email ) {
			wp_mail( $admin_email, $subject, $message );
		}

		// Schedule 9am reminder on closing day.
		$this->schedule_closing_reminder( $vote );
	}

	/**
	 * Send "vote has closed" email to voters who participated.
	 */
	private function send_vote_closed_notification( object $vote ): void {
		// Only notify people who actually voted.
		$recipients = $this->get_voters( $vote->id );

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: 1: site name, 2: vote title */
			__( '[%1$s] Vote Completed: %2$s', 'wp-voting-plugin' ),
			$site_name,
			$vote->proposal_name
		);

		$page_ids    = get_option( 'wpvp_page_ids', array() );
		$results_url = ! empty( $page_ids['vote-results'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['vote-results'] ) )
			: home_url();

		// Get results for formatting.
		$results = WPVP_Database::get_results( $vote->id );
		$results_summary = $results ? $this->format_results_summary( $results ) : '';

		$message = sprintf(
			/* translators: 1: vote title, 2: results URL */
			__( "The following vote has been completed:\n\n%1\$s\n\n%2\$s\n\nView full results: %3\$s", 'wp-voting-plugin' ),
			$vote->proposal_name,
			$results_summary,
			$results_url
		);

		// Send to voters.
		if ( ! empty( $recipients ) ) {
			$this->send_bulk_email( $recipients, $subject, $message );
		}

		// Send to admin notification email with results.
		$admin_email = $this->get_admin_notification_email();
		if ( $admin_email ) {
			wp_mail( $admin_email, $subject, $message );
		}
	}

	/*
	------------------------------------------------------------------
	 *  Helpers.
	 * ----------------------------------------------------------------*/

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

	/**
	 * Get email addresses of users who voted on a proposal.
	 *
	 * @return string[]
	 */
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

	/**
	 * Get admin email addresses.
	 *
	 * @return string[]
	 */
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
	private function send_bulk_email( array $recipients, string $subject, string $message ): void {
		if ( empty( $recipients ) ) {
			return;
		}

		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$headers = array(
			'From: ' . $site_name . ' <' . $admin_email . '>',
		);

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

	/*
	------------------------------------------------------------------
	 *  Voter confirmation emails (sent when user casts ballot).
	 * ----------------------------------------------------------------*/

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

		if ( ! $vote || ! $user ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: 1: site name, 2: vote title */
			__( '[%1$s] Vote Confirmation: %2$s', 'wp-voting-plugin' ),
			$site_name,
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
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) )
			);
		}

		$headers = array(
			'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
		);

		wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Format ballot data for email display.
	 *
	 * @param mixed  $ballot_data Ballot data.
	 * @param string $voting_type Voting type.
	 * @return string
	 */
	private function format_ballot_for_email( $ballot_data, string $voting_type ): string {
		$decoded = is_string( $ballot_data ) ? json_decode( $ballot_data, true ) : $ballot_data;

		// Ranked voting types.
		if ( in_array( $voting_type, array( 'rcv', 'stv', 'condorcet' ), true ) && is_array( $decoded ) ) {
			$output = '';
			foreach ( $decoded as $rank => $choice ) {
				$output .= '  ' . ( $rank + 1 ) . '. ' . $choice . "\n";
			}
			return $output;
		}

		// Single choice voting types.
		return '  ' . ( is_string( $decoded ) ? $decoded : wp_json_encode( $decoded ) );
	}

	/*
	------------------------------------------------------------------
	 *  9am closing reminder.
	 * ----------------------------------------------------------------*/

	/**
	 * Schedule a reminder email for 9am on the day the vote closes.
	 *
	 * @param object $vote Vote object.
	 */
	private function schedule_closing_reminder( object $vote ): void {
		if ( empty( $vote->closing_date ) ) {
			return;
		}

		// Calculate 9am on the closing date in site timezone.
		$close_timestamp = strtotime( $vote->closing_date );
		$close_date_only = gmdate( 'Y-m-d', $close_timestamp );
		$reminder_time   = strtotime( $close_date_only . ' 09:00:00' );

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

		$admin_email = $this->get_admin_notification_email();
		if ( ! $admin_email ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: 1: site name, 2: vote title */
			__( '[%1$s] Vote Closing Today: %2$s', 'wp-voting-plugin' ),
			$site_name,
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
			__( "Ballots cast so far: %d\n\n", 'wp-voting-plugin' ),
			intval( $ballot_count )
		);

		if ( $vote->closing_date ) {
			$message .= sprintf(
				/* translators: %s: closing time */
				__( 'Voting closes: %s', 'wp-voting-plugin' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) )
			);
		}

		$message .= "\n\n" . sprintf(
			/* translators: %s: vote URL */
			__( "Vote URL: %s", 'wp-voting-plugin' ),
			$vote_url
		);

		$headers = array(
			'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
		);

		wp_mail( $admin_email, $subject, $message, $headers );
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
