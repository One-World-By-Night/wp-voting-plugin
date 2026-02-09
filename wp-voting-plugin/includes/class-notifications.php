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
	}

	/**
	 * Send "vote has closed" email to voters who participated.
	 */
	private function send_vote_closed_notification( object $vote ): void {
		// Only notify people who actually voted.
		$recipients = $this->get_voters( $vote->id );
		if ( empty( $recipients ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: 1: site name, 2: vote title */
			__( '[%1$s] Vote Closed: %2$s', 'wp-voting-plugin' ),
			$site_name,
			$vote->proposal_name
		);

		$page_ids    = get_option( 'wpvp_page_ids', array() );
		$results_url = ! empty( $page_ids['vote-results'] )
			? add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $page_ids['vote-results'] ) )
			: home_url();

		$message = sprintf(
			/* translators: 1: vote title, 2: results URL */
			__( "The following vote has closed:\n\n%1\$s\n\nView results: %2\$s", 'wp-voting-plugin' ),
			$vote->proposal_name,
			$results_url
		);

		$this->send_bulk_email( $recipients, $subject, $message );
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
}
