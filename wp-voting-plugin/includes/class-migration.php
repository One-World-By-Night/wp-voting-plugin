<?php
/**
 * Migration utility: import data from the original wp-voting-plugin.
 *
 * Reads from the old `{prefix}wpvp_votes`, `{prefix}wpvp_ballots`, and
 * `{prefix}wpvp_results` tables (if they exist) and imports them into the
 * new v2 tables.
 *
 * Migration is idempotent: already-migrated votes are skipped via a
 * `_wpvp_migrated_from` post meta-style marker stored in the settings JSON.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Migration {

	/** @var array Migration log messages. */
	private $log = array();

	/** @var int Counters. */
	private $migrated_votes   = 0;
	private $migrated_ballots = 0;
	private $skipped          = 0;
	private $errors           = 0;

	public function __construct() {
		add_action( 'wp_ajax_wpvp_run_migration', array( $this, 'ajax_run_migration' ) );
		add_action( 'wp_ajax_wpvp_check_migration', array( $this, 'ajax_check_migration' ) );
	}

	/*
	------------------------------------------------------------------
	 *  AJAX handlers.
	 * ----------------------------------------------------------------*/

	/**
	 * Check if old tables exist and report how many records are available.
	 */
	public function ajax_check_migration(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		global $wpdb;
		$old_table = $wpdb->prefix . 'wpvp_votes';

		// Check if old table exists (could be in current DB from the v1 plugin).
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$old_table
			)
		);

		if ( ! $exists ) {
			wp_send_json_error( __( 'No v1 voting tables found. Nothing to migrate.', 'wp-voting-plugin' ) );
		}

		$vote_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$old_table}" );
		$ballot_table = $wpdb->prefix . 'wpvp_ballots';
		$ballot_count = 0;

		$ballot_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$ballot_table
			)
		);

		if ( $ballot_exists ) {
			$ballot_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ballot_table}" );
		}

		wp_send_json_success(
			array(
				'votes'   => $vote_count,
				'ballots' => $ballot_count,
				'message' => sprintf(
					__( 'Found %1$d votes and %2$d ballots to migrate.', 'wp-voting-plugin' ),
					$vote_count,
					$ballot_count
				),
			)
		);
	}

	/**
	 * Run the migration.
	 */
	public function ajax_run_migration(): void {
		check_ajax_referer( 'wpvp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		$this->run();

		wp_send_json_success(
			array(
				'migrated_votes'   => $this->migrated_votes,
				'migrated_ballots' => $this->migrated_ballots,
				'skipped'          => $this->skipped,
				'errors'           => $this->errors,
				'log'              => $this->log,
				'message'          => sprintf(
					__( 'Migration complete. Votes: %1$d migrated, %2$d skipped, %3$d errors.', 'wp-voting-plugin' ),
					$this->migrated_votes,
					$this->skipped,
					$this->errors
				),
			)
		);
	}

	/*
	------------------------------------------------------------------
	 *  Migration logic.
	 * ----------------------------------------------------------------*/

	/**
	 * Run the full migration from v1 tables to v2 tables.
	 */
	public function run(): void {
		global $wpdb;

		$old_votes_table   = $wpdb->prefix . 'wpvp_votes';
		$old_ballots_table = $wpdb->prefix . 'wpvp_ballots';

		// Check if old table exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$old_votes_table
			)
		);

		if ( ! $exists ) {
			$this->log[] = 'No v1 tables found.';
			return;
		}

		// Get already-migrated vote IDs to skip.
		$migrated_ids = $this->get_migrated_ids();

		// Fetch old votes.
		$old_votes = $wpdb->get_results( "SELECT * FROM {$old_votes_table} ORDER BY id ASC" );

		if ( empty( $old_votes ) ) {
			$this->log[] = 'No votes found in v1 tables.';
			return;
		}

		foreach ( $old_votes as $old_vote ) {
			$old_id = (int) $old_vote->id;

			// Skip if already migrated.
			if ( in_array( $old_id, $migrated_ids, true ) ) {
				++$this->skipped;
				$this->log[] = sprintf( 'Skipped vote #%d (already migrated).', $old_id );
				continue;
			}

			$new_id = $this->migrate_vote( $old_vote );
			if ( ! $new_id ) {
				++$this->errors;
				$this->log[] = sprintf( 'Failed to migrate vote #%d.', $old_id );
				continue;
			}

			++$this->migrated_votes;
			$this->log[] = sprintf( 'Migrated vote #%d → #%d: %s', $old_id, $new_id, $old_vote->proposal_name );

			// Migrate ballots for this vote.
			$ballot_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
					DB_NAME,
					$old_ballots_table
				)
			);

			if ( $ballot_exists ) {
				$this->migrate_ballots( $old_id, $new_id, $old_ballots_table );
			}
		}

		// Migrate results if table exists.
		$old_results_table = $wpdb->prefix . 'wpvp_results';
		$results_exists    = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$old_results_table
			)
		);

		if ( $results_exists ) {
			$this->log[] = 'Note: Old results were not migrated. Re-process votes to regenerate results.';
		}
	}

	/**
	 * Migrate a single vote record.
	 *
	 * @return int|false New vote ID or false on failure.
	 */
	private function migrate_vote( object $old ): int|false {
		// Map old voting type names to new ones.
		$type_map = array(
			'singleton'    => 'singleton',
			'single'       => 'singleton',
			'fptp'         => 'singleton',
			'rcv'          => 'rcv',
			'irv'          => 'rcv',
			'ranked'       => 'rcv',
			'stv'          => 'stv',
			'condorcet'    => 'condorcet',
			'disciplinary' => 'disciplinary',
		);

		// Map old stage names to new ones.
		$stage_map = array(
			'draft'     => 'draft',
			'active'    => 'open',
			'open'      => 'open',
			'closed'    => 'closed',
			'completed' => 'completed',
			'archived'  => 'archived',
		);

		$voting_type = strtolower( trim( $old->voting_type ?? 'singleton' ) );
		$voting_type = $type_map[ $voting_type ] ?? 'singleton';

		$voting_stage = strtolower( trim( $old->voting_stage ?? 'draft' ) );
		$voting_stage = $stage_map[ $voting_stage ] ?? 'draft';

		// Decode options — old plugin may have stored them as serialized or JSON.
		$options = $this->safe_decode( $old->voting_options ?? '' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		// Normalize options to new format: [{text: '', description: ''}]
		$normalized_options = array();
		foreach ( $options as $opt ) {
			if ( is_string( $opt ) ) {
				$normalized_options[] = array(
					'text'        => $opt,
					'description' => '',
				);
			} elseif ( is_array( $opt ) && isset( $opt['text'] ) ) {
				$normalized_options[] = array(
					'text'        => sanitize_text_field( $opt['text'] ),
					'description' => sanitize_textarea_field( $opt['description'] ?? '' ),
				);
			}
		}

		// Decode allowed roles.
		$allowed_roles = $this->safe_decode( $old->allowed_roles ?? '' );
		if ( ! is_array( $allowed_roles ) ) {
			$allowed_roles = array();
		}

		// Decode settings.
		$settings = $this->safe_decode( $old->settings ?? '' );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Mark as migrated in settings.
		$settings['_wpvp_migrated_from'] = (int) $old->id;

		$data = array(
			'proposal_name'        => sanitize_text_field( $old->proposal_name ?? '' ),
			'proposal_description' => wp_kses_post( $old->proposal_description ?? '' ),
			'voting_type'          => $voting_type,
			'voting_options'       => $normalized_options,
			'number_of_winners'    => max( 1, (int) ( $old->number_of_winners ?? 1 ) ),
			'allowed_roles'        => $allowed_roles,
			'visibility'           => sanitize_key( $old->visibility ?? 'private' ),
			'voting_stage'         => $voting_stage,
			'opening_date'         => $old->opening_date ?? '',
			'closing_date'         => $old->closing_date ?? '',
			'settings'             => $settings,
		);

		return WPVP_Database::save_vote( $data );
	}

	/**
	 * Migrate ballots from old vote ID to new vote ID.
	 */
	private function migrate_ballots( int $old_vote_id, int $new_vote_id, string $old_table ): void {
		global $wpdb;

		$old_ballots = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$old_table} WHERE vote_id = %d ORDER BY id ASC",
				$old_vote_id
			)
		);

		if ( empty( $old_ballots ) ) {
			return;
		}

		foreach ( $old_ballots as $old_ballot ) {
			$user_id = (int) ( $old_ballot->user_id ?? 0 );
			if ( ! $user_id ) {
				continue;
			}

			// Decode ballot data.
			$ballot_data = $this->safe_decode( $old_ballot->ballot_data ?? '' );

			$result = WPVP_Database::cast_ballot( $new_vote_id, $user_id, $ballot_data );
			if ( $result ) {
				++$this->migrated_ballots;
			}
		}
	}

	/*
	------------------------------------------------------------------
	 *  Helpers.
	 * ----------------------------------------------------------------*/

	/**
	 * Safely decode data that may be JSON or PHP-serialized.
	 *
	 * @return mixed Decoded data, or the original string if decoding fails.
	 */
	private function safe_decode( string $data ) {
		if ( empty( $data ) ) {
			return $data;
		}

		// Try JSON first.
		$json = json_decode( $data, true );
		if ( null !== $json ) {
			return $json;
		}

		// Try PHP unserialize with safety constraints.
		if ( is_serialized( $data ) ) {
			return unserialize( $data, array( 'allowed_classes' => false ) );
		}

		return $data;
	}

	/**
	 * Get IDs of old votes that have already been migrated.
	 *
	 * @return int[]
	 */
	private function get_migrated_ids(): array {
		global $wpdb;

		$table = WPVP_Database::votes_table();
		$rows  = $wpdb->get_col(
			"SELECT settings FROM {$table} WHERE settings LIKE '%_wpvp_migrated_from%'"
		);

		$ids = array();
		foreach ( $rows as $json ) {
			$settings = json_decode( $json, true );
			if ( isset( $settings['_wpvp_migrated_from'] ) ) {
				$ids[] = (int) $settings['_wpvp_migrated_from'];
			}
		}

		return $ids;
	}
}
