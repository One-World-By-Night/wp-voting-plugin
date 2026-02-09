<?php
/**
 * Contract that every voting algorithm must implement.
 */

defined( 'ABSPATH' ) || exit;

interface WPVP_Voting_Algorithm {

	/**
	 * Process ballots and return results.
	 *
	 * @param array $ballots  Array of ballot rows from the database.
	 *                        Each row has 'ballot_data' (decoded), 'user_id', 'display_name'.
	 * @param array $options  Ordered list of valid option strings.
	 * @param array $config   Algorithm-specific settings (e.g. num_seats for STV).
	 *
	 * @return array {
	 *     @type string|null   $winner              Winning option (null if tie or no result).
	 *     @type array         $winners             All winners (for multi-winner methods).
	 *     @type array         $vote_counts          Option => count map.
	 *     @type array         $percentages          Option => percentage map.
	 *     @type array         $rankings             Rank => [options] map (competition ranking).
	 *     @type array         $rounds               Round-by-round data (ranked methods).
	 *     @type bool          $tie                  Whether the result is a tie.
	 *     @type array         $tied_candidates      Options involved in the tie.
	 *     @type int           $total_votes          Total ballots cast.
	 *     @type int           $total_valid_votes    Ballots with recognised options.
	 *     @type array         $event_log            Human-readable processing log.
	 *     @type array         $validation           { is_valid: bool, errors: [], warnings: [] }
	 * }
	 */
	public function process( array $ballots, array $options, array $config = array() ): array;

	/**
	 * Machine-readable type key (e.g. 'singleton', 'rcv').
	 */
	public function get_type(): string;

	/**
	 * Human-readable label.
	 */
	public function get_label(): string;

	/**
	 * Short description of how the algorithm works.
	 */
	public function get_description(): string;
}
