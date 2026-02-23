<?php
/**
 * Orchestrates vote processing: loads ballots, routes to the correct
 * algorithm, validates, and saves results.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Processor {

	/**
	 * Registry of algorithm instances keyed by type.
	 *
	 * @var array<string, WPVP_Voting_Algorithm>
	 */
	private static $algorithms = array();

	/**
	 * Register a voting algorithm.
	 */
	public static function register( WPVP_Voting_Algorithm $algo ): void {
		self::$algorithms[ $algo->get_type() ] = $algo;
	}

	/**
	 * Get an algorithm by type key.
	 *
	 * @return WPVP_Voting_Algorithm|null
	 */
	public static function get_algorithm( string $type ): ?WPVP_Voting_Algorithm {
		return self::$algorithms[ $type ] ?? null;
	}

	/**
	 * Get all registered algorithms.
	 *
	 * @return array<string, WPVP_Voting_Algorithm>
	 */
	public static function get_algorithms(): array {
		return self::$algorithms;
	}

	/**
	 * Register the built-in algorithms.
	 * Called once during plugin init.
	 */
	public static function register_defaults(): void {
		self::register( new WPVP_Singleton() );
		self::register( new WPVP_RCV() );
		self::register( new WPVP_STV() );
		self::register( new WPVP_Sequential_RCV() );
		self::register( new WPVP_Condorcet() );
		self::register( new WPVP_Disciplinary() );
		self::register( new WPVP_Consent() );
	}

	/**
	 * Process a vote end-to-end.
	 *
	 * @param int $vote_id The vote to process.
	 * @return array|WP_Error  Processed results array, or WP_Error on failure.
	 */
	public static function process( int $vote_id ) {
		$vote = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			return new WP_Error( 'vote_not_found', __( 'Vote not found.', 'wp-voting-plugin' ) );
		}

		$algo = self::get_algorithm( $vote->voting_type );
		if ( ! $algo ) {
			return new WP_Error(
				'unknown_type',
				sprintf(
					/* translators: %s: voting type key */
					__( 'Unknown voting type: %s', 'wp-voting-plugin' ),
					$vote->voting_type
				)
			);
		}

		// Load ballots.
		$ballots = WPVP_Database::get_ballots( $vote_id );

		// Build the flat list of valid option strings.
		$raw_options = json_decode( $vote->voting_options, true );
		if ( ! is_array( $raw_options ) ) {
			return new WP_Error( 'bad_options', __( 'Could not decode voting options.', 'wp-voting-plugin' ) );
		}
		$options = array_column( $raw_options, 'text' );

		// Algorithm config.
		$config = array(
			'num_seats' => max( 1, intval( $vote->number_of_winners ) ),
		);

		// Run the algorithm.
		$start     = microtime( true );
		$results   = $algo->process( $ballots, $options, $config );
		$calc_time = microtime( true ) - $start;

		// Validate.
		$results['validation'] = WPVP_Validator::validate( $vote->voting_type, $results, $options );

		// Persist.
		$saved = WPVP_Database::save_results( $vote_id, $results, $calc_time );
		if ( ! $saved ) {
			return new WP_Error( 'save_failed', __( 'Failed to save results.', 'wp-voting-plugin' ) );
		}

		return $results;
	}
}

// Register built-in algorithms once the plugin is loaded.
add_action( 'wpvp_plugin_loaded', array( 'WPVP_Processor', 'register_defaults' ) );
