<?php
/**
 * All database operations: schema, CRUD, queries.
 *
 * Every query uses $wpdb->prepare(). ORDER BY columns are whitelisted.
 * LIKE searches use $wpdb->esc_like(). No SQL comments in dbDelta SQL.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Database {

	/*
	------------------------------------------------------------------
	 *  Table names (prefixed).
	 * ----------------------------------------------------------------*/

	public static function votes_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpvp_votes';
	}

	public static function ballots_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpvp_ballots';
	}

	public static function results_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpvp_results';
	}

	/*
	------------------------------------------------------------------
	 *  Schema — create / upgrade via dbDelta.
	 * ----------------------------------------------------------------*/

	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$sql_votes = 'CREATE TABLE ' . self::votes_table() . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            proposal_name varchar(255) NOT NULL,
            proposal_description longtext,
            voting_type varchar(50) NOT NULL DEFAULT 'singleton',
            voting_options longtext NOT NULL,
            number_of_winners int NOT NULL DEFAULT 1,
            allowed_roles text,
            visibility varchar(50) NOT NULL DEFAULT 'private',
            voting_roles text,
            voting_eligibility varchar(50) NOT NULL DEFAULT 'private',
            voting_stage varchar(50) NOT NULL DEFAULT 'draft',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            opening_date datetime DEFAULT NULL,
            closing_date datetime DEFAULT NULL,
            settings longtext,
            PRIMARY KEY  (id),
            KEY created_by (created_by),
            KEY voting_stage (voting_stage),
            KEY date_range (opening_date,closing_date)
        ) {$charset};";

		$sql_ballots = 'CREATE TABLE ' . self::ballots_table() . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vote_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            voter_name varchar(255) NOT NULL DEFAULT '',
            ballot_data longtext NOT NULL,
            voted_at datetime NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_vote (vote_id,user_id),
            KEY vote_id (vote_id),
            KEY user_id (user_id),
            KEY voted_at (voted_at)
        ) {$charset};";

		$sql_results = 'CREATE TABLE ' . self::results_table() . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vote_id bigint(20) unsigned NOT NULL,
            total_votes int NOT NULL DEFAULT 0,
            total_voters int NOT NULL DEFAULT 0,
            final_results longtext,
            winner_data longtext,
            rounds_data longtext,
            statistics longtext,
            calculated_at datetime NOT NULL,
            calculation_time float NOT NULL DEFAULT 0,
            validation_status varchar(50) NOT NULL DEFAULT 'pending',
            validation_notes text,
            PRIMARY KEY  (id),
            UNIQUE KEY vote_id (vote_id),
            KEY calculated_at (calculated_at)
        ) {$charset};";

		dbDelta( $sql_votes );
		dbDelta( $sql_ballots );
		dbDelta( $sql_results );
	}

	/**
	 * Upgrade to version 2.2.0: Add voting eligibility columns and migrate data.
	 * Separates visibility (who can VIEW) from voting eligibility (who can VOTE).
	 */
	public static function upgrade_to_220(): void {
		global $wpdb;

		$table = self::votes_table();

		// Check if columns already exist.
		$columns = $wpdb->get_col( "DESC {$table}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$needs_voting_eligibility = ! in_array( 'voting_eligibility', $columns, true );
		$needs_voting_roles       = ! in_array( 'voting_roles', $columns, true );

		// Add missing columns.
		if ( $needs_voting_eligibility ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN voting_eligibility varchar(50) NOT NULL DEFAULT 'private' AFTER visibility" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( $needs_voting_roles ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN voting_roles text AFTER visibility" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Migrate existing data: copy visibility → voting_eligibility, allowed_roles → voting_roles.
		// Only update rows where the new fields are empty/default.
		$wpdb->query(
			"UPDATE {$table}
			SET voting_eligibility = visibility,
			    voting_roles = allowed_roles
			WHERE voting_eligibility = 'private' AND voting_roles IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/*
	------------------------------------------------------------------
	 *  Votes — CRUD.
	 * ----------------------------------------------------------------*/

	/**
	 * Insert a new vote. Returns the new vote ID or false on failure.
	 */
	public static function save_vote( array $data ) {
		global $wpdb;

		$row = array(
			'proposal_name'        => sanitize_text_field( $data['proposal_name'] ?? '' ),
			'proposal_description' => wp_kses_post( $data['proposal_description'] ?? '' ),
			'voting_type'          => sanitize_key( $data['voting_type'] ?? 'singleton' ),
			'voting_options'       => wp_json_encode( $data['voting_options'] ?? array() ),
			'number_of_winners'    => max( 1, intval( $data['number_of_winners'] ?? 1 ) ),
			'allowed_roles'        => wp_json_encode( $data['allowed_roles'] ?? array() ),
			'visibility'           => sanitize_key( $data['visibility'] ?? 'private' ),
			'voting_roles'         => wp_json_encode( $data['voting_roles'] ?? array() ),
			'voting_eligibility'   => sanitize_key( $data['voting_eligibility'] ?? 'private' ),
			'voting_stage'         => self::sanitize_stage( $data['voting_stage'] ?? 'draft' ),
			'created_by'           => get_current_user_id(),
			'created_at'           => current_time( 'mysql' ),
			'opening_date'         => self::sanitize_datetime( $data['opening_date'] ?? null ),
			'closing_date'         => self::sanitize_datetime( $data['closing_date'] ?? null ),
			'settings'             => wp_json_encode( $data['settings'] ?? array() ),
		);

		$formats = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$result = $wpdb->insert( self::votes_table(), $row, $formats );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing vote. Returns true on success.
	 */
	public static function update_vote( int $vote_id, array $data ): bool {
		global $wpdb;

		$row     = array();
		$formats = array();

		$string_fields = array(
			'proposal_name'      => 'sanitize_text_field',
			'voting_type'        => 'sanitize_key',
			'visibility'         => 'sanitize_key',
			'voting_eligibility' => 'sanitize_key',
		);

		foreach ( $string_fields as $field => $sanitizer ) {
			if ( isset( $data[ $field ] ) ) {
				$row[ $field ] = $sanitizer( $data[ $field ] );
				$formats[]     = '%s';
			}
		}

		if ( isset( $data['proposal_description'] ) ) {
			$row['proposal_description'] = wp_kses_post( $data['proposal_description'] );
			$formats[]                   = '%s';
		}

		if ( isset( $data['voting_options'] ) ) {
			$row['voting_options'] = wp_json_encode( $data['voting_options'] );
			$formats[]             = '%s';
		}

		if ( isset( $data['number_of_winners'] ) ) {
			$row['number_of_winners'] = max( 1, intval( $data['number_of_winners'] ) );
			$formats[]                = '%d';
		}

		if ( isset( $data['allowed_roles'] ) ) {
			$row['allowed_roles'] = wp_json_encode( $data['allowed_roles'] );
			$formats[]            = '%s';
		}

		if ( isset( $data['voting_roles'] ) ) {
			$row['voting_roles'] = wp_json_encode( $data['voting_roles'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['voting_stage'] ) ) {
			$row['voting_stage'] = self::sanitize_stage( $data['voting_stage'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['opening_date'] ) ) {
			$row['opening_date'] = self::sanitize_datetime( $data['opening_date'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['closing_date'] ) ) {
			$row['closing_date'] = self::sanitize_datetime( $data['closing_date'] );
			$formats[]           = '%s';
		}

		if ( isset( $data['settings'] ) ) {
			$row['settings'] = wp_json_encode( $data['settings'] );
			$formats[]       = '%s';
		}

		if ( empty( $row ) ) {
			return false;
		}

		$result = $wpdb->update( self::votes_table(), $row, array( 'id' => $vote_id ), $formats, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Delete a vote and all related ballots and results.
	 */
	public static function delete_vote( int $vote_id ): bool {
		global $wpdb;

		// Delete in dependency order: results, ballots, vote.
		$wpdb->delete( self::results_table(), array( 'vote_id' => $vote_id ), array( '%d' ) );
		$wpdb->delete( self::ballots_table(), array( 'vote_id' => $vote_id ), array( '%d' ) );
		$result = $wpdb->delete( self::votes_table(), array( 'id' => $vote_id ), array( '%d' ) );

		return false !== $result;
	}

	/**
	 * Get a single vote by ID.
	 *
	 * @return object|null
	 */
	public static function get_vote( int $vote_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::votes_table() . ' WHERE id = %d',
				$vote_id
			)
		);
	}

	/**
	 * Get paginated votes with search, filters, and safe sorting.
	 *
	 * @param array $args {
	 *     @type int    $per_page  Items per page.
	 *     @type int    $page      Current page.
	 *     @type string $search    Search term.
	 *     @type string $status    Filter by voting_stage.
	 *     @type string $type      Filter by voting_type.
	 *     @type string $orderby   Column to sort by (whitelisted).
	 *     @type string $order     ASC or DESC.
	 * }
	 * @return array
	 */
	public static function get_votes( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'search'   => '',
			'status'   => '',
			'type'     => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);
		$args     = wp_parse_args( $args, $defaults );

		// Build WHERE clauses.
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(v.proposal_name LIKE %s OR v.proposal_description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'v.voting_stage = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'v.voting_type = %s';
			$values[] = sanitize_key( $args['type'] );
		}

		$where_sql = implode( ' AND ', $where );

		// Whitelist ORDER BY to prevent SQL injection.
		$allowed_orderby = array(
			'id',
			'proposal_name',
			'voting_type',
			'voting_stage',
			'created_at',
			'opening_date',
			'closing_date',
		);
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Pagination.
		$per_page = max( 1, intval( $args['per_page'] ) );
		$offset   = max( 0, ( intval( $args['page'] ) - 1 ) * $per_page );

		$sql = 'SELECT v.*, u.display_name AS creator_name
                FROM ' . self::votes_table() . " v
                LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID
                WHERE {$where_sql}
                ORDER BY v.{$orderby} {$order}
                LIMIT %d OFFSET %d";

		$values[] = $per_page;
		$values[] = $offset;

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Count votes matching the same filters as get_votes().
	 */
	public static function get_vote_count( array $args = array() ): int {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(proposal_name LIKE %s OR proposal_description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'voting_stage = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'voting_type = %s';
			$values[] = sanitize_key( $args['type'] );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = 'SELECT COUNT(*) FROM ' . self::votes_table() . " WHERE {$where_sql}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/*
	------------------------------------------------------------------
	 *  Ballots — cast, update, query.
	 * ----------------------------------------------------------------*/

	/**
	 * Cast a ballot. Returns ballot ID or false on failure.
	 *
	 * The UNIQUE KEY (vote_id, user_id) prevents double-voting at the DB level.
	 *
	 * @param int   $vote_id    The vote being voted on.
	 * @param int   $user_id    The voter.
	 * @param mixed $ballot_data Ballot payload (will be JSON-encoded).
	 * @return int|false
	 */
	public static function cast_ballot( int $vote_id, int $user_id, $ballot_data ) {
		global $wpdb;

		$user = get_userdata( $user_id );

		$row = array(
			'vote_id'     => $vote_id,
			'user_id'     => $user_id,
			'voter_name'  => $user ? sanitize_text_field( $user->display_name ) : '',
			'ballot_data' => wp_json_encode( $ballot_data ),
			'voted_at'    => current_time( 'mysql' ),
			'ip_address'  => self::get_client_ip(),
			'user_agent'  => self::get_user_agent(),
		);

		$formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

		$result = $wpdb->insert( self::ballots_table(), $row, $formats );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing ballot (for revoting).
	 */
	public static function update_ballot( int $vote_id, int $user_id, $ballot_data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			self::ballots_table(),
			array(
				'ballot_data' => wp_json_encode( $ballot_data ),
				'voted_at'    => current_time( 'mysql' ),
				'ip_address'  => self::get_client_ip(),
				'user_agent'  => self::get_user_agent(),
			),
			array(
				'vote_id' => $vote_id,
				'user_id' => $user_id,
			),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Check whether a user has already voted.
	 */
	public static function user_has_voted( int $user_id, int $vote_id ): bool {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::ballots_table() . ' WHERE vote_id = %d AND user_id = %d',
				$vote_id,
				$user_id
			)
		);

		return $count > 0;
	}

	/**
	 * Get ballot count for a vote.
	 */
	public static function get_ballot_count( int $vote_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::ballots_table() . ' WHERE vote_id = %d',
				$vote_id
			)
		);
	}

	/**
	 * Get all ballots for a vote (for processing).
	 */
	public static function get_ballots( int $vote_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT b.*, u.user_login, u.display_name
             FROM ' . self::ballots_table() . " b
             LEFT JOIN {$wpdb->users} u ON b.user_id = u.ID
             WHERE b.vote_id = %d
             ORDER BY b.voted_at ASC",
				$vote_id
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return array();
		}

		// Decode ballot_data JSON for each row.
		foreach ( $rows as &$row ) {
			$decoded            = json_decode( $row['ballot_data'], true );
			$row['ballot_data'] = is_array( $decoded ) || is_string( $decoded ) ? $decoded : $row['ballot_data'];
		}

		return $rows;
	}

	/*
	------------------------------------------------------------------
	 *  Results — save, fetch, export.
	 * ----------------------------------------------------------------*/

	/**
	 * Save (upsert) calculated vote results.
	 *
	 * @param int   $vote_id The vote.
	 * @param array $results Processed results from a voting algorithm.
	 * @param float $calc_time  Calculation duration in seconds.
	 */
	public static function save_results( int $vote_id, array $results, float $calc_time = 0.0 ): bool {
		global $wpdb;

		$total_voters = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT user_id) FROM ' . self::ballots_table() . ' WHERE vote_id = %d',
				$vote_id
			)
		);

		// Determine validation status with correct operator precedence.
		$is_valid          = isset( $results['validation']['is_valid'] ) ? (bool) $results['validation']['is_valid'] : true;
		$validation_status = $is_valid ? 'valid' : 'invalid';

		$table = self::results_table();

		// Use modern INSERT ... AS alias syntax for MySQL 8.0.20+ compat,
		// with fallback VALUES() for older MySQL.  WordPress still supports
		// MySQL 5.7, so we use VALUES() which works everywhere.
		$sql = $wpdb->prepare(
			"INSERT INTO {$table}
                (vote_id, total_votes, total_voters, final_results, winner_data,
                 rounds_data, statistics, calculated_at, calculation_time,
                 validation_status, validation_notes)
             VALUES (%d, %d, %d, %s, %s, %s, %s, %s, %f, %s, %s)
             ON DUPLICATE KEY UPDATE
                total_votes       = VALUES(total_votes),
                total_voters      = VALUES(total_voters),
                final_results     = VALUES(final_results),
                winner_data       = VALUES(winner_data),
                rounds_data       = VALUES(rounds_data),
                statistics        = VALUES(statistics),
                calculated_at     = VALUES(calculated_at),
                calculation_time  = VALUES(calculation_time),
                validation_status = VALUES(validation_status),
                validation_notes  = VALUES(validation_notes)",
			$vote_id,
			intval( $results['total_votes'] ?? 0 ),
			$total_voters,
			wp_json_encode(
				array(
					'vote_counts' => $results['vote_counts'] ?? array(),
					'percentages' => $results['percentages'] ?? array(),
					'rankings'    => $results['rankings'] ?? array(),
				)
			),
			wp_json_encode(
				array(
					'winner'          => $results['winner'] ?? null,
					'winners'         => $results['winners'] ?? array(),
					'winner_votes'    => $results['winner_votes'] ?? 0,
					'tie'             => $results['tie'] ?? false,
					'tied_candidates' => $results['tied_candidates'] ?? array(),
				)
			),
			wp_json_encode( $results['rounds'] ?? array() ),
			wp_json_encode(
				array(
					'vote_distribution' => $results['vote_counts'] ?? array(),
					'percentages'       => $results['percentages'] ?? array(),
					'eliminated_order'  => $results['eliminated_candidates'] ?? array(),
					'event_log'         => $results['event_log'] ?? array(),
				)
			),
			current_time( 'mysql' ),
			$calc_time,
			$validation_status,
			wp_json_encode( $results['validation'] ?? array() )
		);

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WPVP save_results error: ' . $wpdb->last_error );
			}
			return false;
		}

		// Mark the vote as completed.
		self::update_vote( $vote_id, array( 'voting_stage' => 'completed' ) );
		wp_cache_delete( "wpvp_results_{$vote_id}", 'wpvp' );

		return true;
	}

	/**
	 * Get saved results for a vote (with object cache).
	 *
	 * @return object|null
	 */
	public static function get_results( int $vote_id ) {
		$cache_key = "wpvp_results_{$vote_id}";
		$cached    = wp_cache_get( $cache_key, 'wpvp' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::results_table() . ' WHERE vote_id = %d',
				$vote_id
			)
		);

		if ( $row ) {
			// Decode JSON fields.
			foreach ( array( 'final_results', 'winner_data', 'rounds_data', 'statistics', 'validation_notes' ) as $field ) {
				if ( ! empty( $row->$field ) ) {
					$row->$field = json_decode( $row->$field, true );
				}
			}
			wp_cache_set( $cache_key, $row, 'wpvp', 300 );
		}

		return $row;
	}

	/*
	------------------------------------------------------------------
	 *  Cleanup / uninstall.
	 * ----------------------------------------------------------------*/

	/**
	 * Drop all plugin tables (used during uninstall).
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$tables = array( 'wpvp_results', 'wpvp_ballots', 'wpvp_votes' );
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/*
	------------------------------------------------------------------
	 *  Static data helpers.
	 * ----------------------------------------------------------------*/

	/**
	 * Valid voting types with labels and descriptions.
	 */
	public static function get_vote_types(): array {
		return array(
			'singleton'    => array(
				'label'       => __( 'Single Choice (FPTP)', 'wp-voting-plugin' ),
				'description' => __( 'Each voter selects one option. The option with the most votes wins.', 'wp-voting-plugin' ),
			),
			'rcv'          => array(
				'label'       => __( 'Ranked Choice Voting', 'wp-voting-plugin' ),
				'description' => __( 'Voters rank options by preference. The lowest-ranked candidate is eliminated each round until one achieves a majority.', 'wp-voting-plugin' ),
			),
			'stv'          => array(
				'label'       => __( 'Single Transferable Vote', 'wp-voting-plugin' ),
				'description' => __( 'Multi-winner ranked voting using the Droop quota. Surplus votes transfer by weight.', 'wp-voting-plugin' ),
			),
			'condorcet'    => array(
				'label'       => __( 'Condorcet Method', 'wp-voting-plugin' ),
				'description' => __( 'Voters rank options. The candidate who wins every head-to-head matchup wins. Falls back to Schulze method if no Condorcet winner exists.', 'wp-voting-plugin' ),
			),
			'disciplinary' => array(
				'label'       => __( 'Disciplinary Vote', 'wp-voting-plugin' ),
				'description' => __( 'Voters select a punishment level. Votes cascade from most severe to least severe until a threshold is met.', 'wp-voting-plugin' ),
			),
			'consent'      => array(
				'label'       => __( 'Consent Agenda', 'wp-voting-plugin' ),
				'description' => __( 'Proposal passes automatically unless someone objects during the review period.', 'wp-voting-plugin' ),
			),
		);
	}

	/**
	 * Valid voting stages.
	 */
	public static function get_vote_stages(): array {
		return array(
			'draft'     => __( 'Draft', 'wp-voting-plugin' ),
			'open'      => __( 'Open', 'wp-voting-plugin' ),
			'closed'    => __( 'Closed', 'wp-voting-plugin' ),
			'completed' => __( 'Completed', 'wp-voting-plugin' ),
			'archived'  => __( 'Archived', 'wp-voting-plugin' ),
		);
	}

	/**
	 * Valid visibility options (who can VIEW).
	 */
	public static function get_visibility_options(): array {
		return array(
			'public'     => __( 'Public (anyone can view)', 'wp-voting-plugin' ),
			'private'    => __( 'Private (logged-in users can view)', 'wp-voting-plugin' ),
			'restricted' => __( 'Restricted (specific roles can view)', 'wp-voting-plugin' ),
		);
	}

	/**
	 * Valid voting eligibility options (who can VOTE).
	 */
	public static function get_voting_eligibility_options(): array {
		return array(
			'public'     => __( 'Anyone (public voting)', 'wp-voting-plugin' ),
			'private'    => __( 'Logged-in users only', 'wp-voting-plugin' ),
			'restricted' => __( 'Specific roles/groups', 'wp-voting-plugin' ),
		);
	}

	/**
	 * Predefined options for disciplinary votes.
	 */
	public static function get_disciplinary_options(): array {
		return array(
			array(
				'text'        => 'Permanent Ban',
				'description' => 'Permanent removal from the organization',
			),
			array(
				'text'        => 'Indefinite Ban / 3 Strikes',
				'description' => 'Indefinite ban equivalent to 3 strikes',
			),
			array(
				'text'        => 'Temporary Ban',
				'description' => 'Temporary ban for a defined period',
			),
			array(
				'text'        => '2 Strikes',
				'description' => 'Second formal strike',
			),
			array(
				'text'        => '1 Strike',
				'description' => 'First formal strike',
			),
			array(
				'text'        => 'Probation',
				'description' => 'Placed on probationary status',
			),
			array(
				'text'        => 'Censure',
				'description' => 'Formal censure on record',
			),
			array(
				'text'        => 'Condemnation',
				'description' => 'Formal condemnation statement',
			),
		);
	}

	/*
	------------------------------------------------------------------
	 *  Private helpers.
	 * ----------------------------------------------------------------*/

	/**
	 * Sanitize a voting stage value against the allowed list.
	 */
	private static function sanitize_stage( string $stage ): string {
		$valid = array_keys( self::get_vote_stages() );
		return in_array( $stage, $valid, true ) ? $stage : 'draft';
	}

	/**
	 * Sanitize a datetime string. Returns null if invalid.
	 */
	private static function sanitize_datetime( ?string $datetime ): ?string {
		if ( empty( $datetime ) ) {
			return null;
		}
		// Accept MySQL datetime or datetime-local input format.
		$timestamp = strtotime( $datetime );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	/**
	 * Get the client IP address (sanitized).
	 */
	private static function get_client_ip(): string {
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Get the user agent string (sanitized and truncated).
	 */
	private static function get_user_agent(): string {
		$ua = '';
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}
		return mb_substr( $ua, 0, 255 );
	}
}
