<?php
/**
 * Ranked Choice Voting / Instant Runoff Voting (IRV).
 *
 * Voters rank options by preference. Each round, the candidate with the fewest
 * first-choice votes is eliminated and their votes transfer to the next
 * preference. Continues until one candidate achieves a majority of active
 * (non-exhausted) ballots.
 *
 * Key fix from v1: eliminates ONE candidate per round (not batch elimination).
 * Tiebreaker for last place: candidate with fewer first-round votes is
 * eliminated first. If still tied, the candidate appearing first alphabetically
 * is eliminated (deterministic, documented).
 */

defined( 'ABSPATH' ) || exit;

class WPVP_RCV implements WPVP_Voting_Algorithm {

	/** Maximum rounds to prevent infinite loops. */
	private const MAX_ROUNDS = 200;

	public function get_type(): string {
		return 'rcv';
	}

	public function get_label(): string {
		return __( 'Ranked Choice Voting', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Voters rank options by preference. The lowest-ranked candidate is eliminated each round until one achieves a majority.', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$total_votes = count( $ballots );
		$event_log   = array();
		$rounds      = array();
		$eliminated  = array();

		// Exclude Abstain from candidate pool — track separately.
		$abstain_count = 0;
		$options       = array_values( array_filter( $options, function ( $o ) { return 'Abstain' !== $o; } ) );

		// Build working ballots: each is an ordered array of preferences.
		$working = array();
		foreach ( $ballots as $ballot ) {
			$ballot_payload = $ballot['ballot_data'];

			// Extract choice from ballot_data (handles both new and legacy formats).
			if ( is_array( $ballot_payload ) && isset( $ballot_payload['choice'] ) ) {
				$ranking = $ballot_payload['choice'];
			} else {
				// Legacy format - ballot_data is the choice directly.
				$ranking = $ballot_payload;
			}

			if ( is_string( $ranking ) ) {
				$ranking = array( $ranking );
			}
			if ( ! is_array( $ranking ) ) {
				continue;
			}
			// Check for abstain before filtering to valid candidates.
			$has_abstain = in_array( 'Abstain', $ranking, true );
			// Filter to valid options only and preserve order.
			$ranking = array_values(
				array_filter(
					$ranking,
					function ( $opt ) use ( $options ) {
						return in_array( $opt, $options, true );
					}
				)
			);
			if ( ! empty( $ranking ) ) {
				$working[] = $ranking;
			} elseif ( $has_abstain ) {
				++$abstain_count;
			}
		}

		// Abstain ballots are tracked but excluded from the election.
		if ( $abstain_count > 0 ) {
			$event_log[] = sprintf( '%d abstention(s) recorded but not counted toward the result.', $abstain_count );
			$total_votes -= $abstain_count;
		}

		// Snapshot first-round counts (used for tiebreaking).
		$initial_counts = self::count_first_choices( $working, $options );

		$active_candidates = $options;
		$round_number      = 0;

		while ( count( $active_candidates ) > 1 && $round_number < self::MAX_ROUNDS ) {
			++$round_number;

			// Count first-choice votes among active candidates.
			$counts    = self::count_first_choices( $working, $active_candidates );
			$exhausted = count( $working ) > 0
				? $total_votes - array_sum( $counts ) - count(
					array_filter(
						$working,
						function ( $b ) use ( $active_candidates ) {
							return empty( array_intersect( $b, $active_candidates ) );
						}
					)
				)
				: $total_votes;
			// Simpler: exhausted = total_votes minus ballots that still have an active first choice.
			$active_ballot_count = array_sum( $counts );
			$exhausted_count     = $total_votes - $active_ballot_count;

			$majority_threshold = floor( $active_ballot_count / 2 ) + 1;

			// Record round.
			$round_data = array(
				'round'              => $round_number,
				'counts'             => $counts,
				'exhausted'          => $exhausted_count,
				'majority_threshold' => $majority_threshold,
				'eliminated'         => array(),
				'transfers'          => array(),
			);

			// Check for majority winner.
			$max_votes = max( $counts );
			if ( $max_votes >= $majority_threshold ) {
				$winner      = array_search( $max_votes, $counts, true );
				$rounds[]    = $round_data;
				$event_log[] = sprintf( 'Round %d: %s wins with %d votes (majority %d).', $round_number, $winner, $max_votes, $majority_threshold );

				return $this->build_result( $winner, $counts, $rounds, $eliminated, $total_votes, $event_log, $options );
			}

			// Check for complete tie (all remaining candidates have equal votes).
			$unique_counts = array_unique( array_values( $counts ) );
			if ( count( $unique_counts ) === 1 && count( $active_candidates ) > 1 ) {
				$rounds[]    = $round_data;
				$event_log[] = sprintf( 'Round %d: All %d remaining candidates tied at %d votes.', $round_number, count( $active_candidates ), $unique_counts[0] );

				return $this->build_result( null, $counts, $rounds, $eliminated, $total_votes, $event_log, $options, $active_candidates );
			}

			// Eliminate ONE candidate with the fewest votes.
			$min_votes  = min( $counts );
			$last_place = array_keys( $counts, $min_votes, true );

			if ( count( $last_place ) > 1 ) {
				// Tiebreaker: eliminate the one with fewer first-round votes.
				usort(
					$last_place,
					function ( $a, $b ) use ( $initial_counts ) {
						$diff = ( $initial_counts[ $a ] ?? 0 ) - ( $initial_counts[ $b ] ?? 0 );
						return 0 !== $diff ? $diff : strcmp( $a, $b ); // alphabetical final tiebreaker
					}
				);
			}
			$to_eliminate = $last_place[0];

			$event_log[]       = sprintf( 'Round %d: Eliminated %s (%d votes).', $round_number, $to_eliminate, $min_votes );
			$eliminated[]      = $to_eliminate;
			$active_candidates = array_values( array_diff( $active_candidates, array( $to_eliminate ) ) );

			// Transfer ballots: remove the eliminated candidate from every ballot.
			$transfers = array();
			foreach ( $working as &$ballot ) {
				if ( isset( $ballot[0] ) && $ballot[0] === $to_eliminate ) {
					// This ballot's first choice was eliminated — it transfers.
					array_shift( $ballot );
					// Skip any other eliminated candidates.
					while ( ! empty( $ballot ) && ! in_array( $ballot[0], $active_candidates, true ) ) {
						array_shift( $ballot );
					}
					$next               = $ballot[0] ?? 'exhausted';
					$transfers[ $next ] = ( $transfers[ $next ] ?? 0 ) + 1;
				}
			}
			unset( $ballot );

			$round_data['eliminated'] = array( $to_eliminate );
			$round_data['transfers']  = $transfers;
			$rounds[]                 = $round_data;
		}

		// If we get here, one candidate remains.
		if ( count( $active_candidates ) === 1 ) {
			$winner       = $active_candidates[0];
			$final_counts = self::count_first_choices( $working, $active_candidates );
			$event_log[]  = sprintf( '%s wins as the last remaining candidate.', $winner );

			return $this->build_result( $winner, $final_counts, $rounds, $eliminated, $total_votes, $event_log, $options );
		}

		// Max rounds reached — should not happen in practice.
		$event_log[] = 'Maximum rounds reached without a winner.';
		return $this->build_result( null, array(), $rounds, $eliminated, $total_votes, $event_log, $options, $active_candidates );
	}

	/**
	 * Count first-choice votes for active candidates.
	 */
	private static function count_first_choices( array $working, array $active ): array {
		$counts = array_fill_keys( $active, 0 );
		foreach ( $working as $ballot ) {
			foreach ( $ballot as $choice ) {
				if ( in_array( $choice, $active, true ) ) {
					++$counts[ $choice ];
					break;
				}
			}
		}
		return $counts;
	}

	/**
	 * Build the standardised result array.
	 */
	private function build_result(
		?string $winner,
		array $final_counts,
		array $rounds,
		array $eliminated,
		int $total_votes,
		array $event_log,
		array $options,
		array $tied = array()
	): array {
		arsort( $final_counts );
		$total_valid = array_sum( $final_counts );

		$percentages = array();
		if ( $total_valid > 0 ) {
			foreach ( $final_counts as $opt => $c ) {
				$percentages[ $opt ] = round( ( $c / $total_valid ) * 100, 2 );
			}
		}

		return array(
			'winner'                => $winner,
			'winners'               => $winner ? array( $winner ) : array(),
			'vote_counts'           => $final_counts,
			'percentages'           => $percentages,
			'rankings'              => array(), // Not meaningful for RCV.
			'rounds'                => $rounds,
			'tie'                   => ! empty( $tied ),
			'tied_candidates'       => $tied,
			'eliminated_candidates' => $eliminated,
			'total_votes'           => $total_votes,
			'total_valid_votes'     => $total_valid,
			'event_log'             => $event_log,
			'validation'            => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}
}
