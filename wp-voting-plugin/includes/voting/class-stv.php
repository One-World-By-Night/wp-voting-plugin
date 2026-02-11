<?php
/**
 * Single Transferable Vote (STV) — multi-winner ranked voting.
 *
 * Uses the Droop quota: floor(votes / (seats + 1)) + 1.
 * Surplus transfer uses the Weighted Inclusive Gregory Method (WIGM):
 *   transfer_value = surplus / elected_candidate_votes
 *   Each ballot's weight is multiplied by transfer_value.
 *
 * Key fix from v1: eliminates ONE candidate per round (not batch).
 * Tiebreaker for elimination: fewest first-round votes, then alphabetical.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_STV implements WPVP_Voting_Algorithm {

	private const MAX_ROUNDS = 200;

	public function get_type(): string {
		return 'stv';
	}

	public function get_label(): string {
		return __( 'Single Transferable Vote', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Multi-winner ranked voting using the Droop quota. Surplus votes transfer by weight (WIGM).', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$num_seats   = max( 1, $config['num_seats'] ?? 1 );
		$total_votes = count( $ballots );
		$event_log   = array();
		$rounds      = array();
		$winners     = array();
		$eliminated  = array();

		if ( 0 === $total_votes || empty( $options ) ) {
			return $this->build_result( $winners, array(), $rounds, $eliminated, 0, $total_votes, $num_seats, $event_log, $options );
		}

		$quota       = floor( $total_votes / ( $num_seats + 1 ) ) + 1;
		$event_log[] = sprintf( 'Droop quota: %d (votes=%d, seats=%d).', $quota, $total_votes, $num_seats );

		// Working ballots: each has 'ranking' (array of prefs) and 'weight' (float).
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
			$ranking = array_values(
				array_filter(
					$ranking,
					function ( $o ) use ( $options ) {
						return in_array( $o, $options, true );
					}
				)
			);
			if ( ! empty( $ranking ) ) {
				$working[] = array(
					'ranking' => $ranking,
					'weight'  => 1.0,
				);
			}
		}

		// Snapshot first-round counts for tiebreaking.
		$initial_counts = self::count_weighted( $working, $options );

		$active = $options;
		$round  = 0;

		while ( count( $winners ) < $num_seats && ! empty( $active ) && $round < self::MAX_ROUNDS ) {
			++$round;

			// Count weighted first-choice votes among active candidates.
			$counts = self::count_weighted( $working, $active );

			$round_data = array(
				'round'      => $round,
				'counts'     => $counts,
				'elected'    => array(),
				'eliminated' => array(),
				'transfers'  => array(),
			);

			// Elect candidates who meet the quota.
			$elected_this_round = array();
			foreach ( $counts as $candidate => $votes ) {
				if ( $votes >= $quota ) {
					$elected_this_round[] = $candidate;
				}
			}

			if ( ! empty( $elected_this_round ) ) {
				// Sort by votes desc so we process the highest surplus first.
				usort(
					$elected_this_round,
					function ( $a, $b ) use ( $counts ) {
						return $counts[ $b ] <=> $counts[ $a ];
					}
				);

				foreach ( $elected_this_round as $elected ) {
					$winners[]   = $elected;
					$active      = array_values( array_diff( $active, array( $elected ) ) );
					$event_log[] = sprintf( 'Round %d: %s elected with %.2f votes (quota %d).', $round, $elected, $counts[ $elected ], $quota );

					// Transfer surplus.
					$surplus = $counts[ $elected ] - $quota;
					if ( $surplus > 0 && $counts[ $elected ] > 0 ) {
						$transfer_value = $surplus / $counts[ $elected ];
						$transfers      = array();

						foreach ( $working as &$wb ) {
							// Find ballots whose current first choice is the elected candidate.
							$first_active = self::first_active_choice( $wb['ranking'], array_merge( $active, array( $elected ) ) );
							if ( $first_active !== $elected ) {
								continue;
							}

							// Apply transfer weight.
							$wb['weight'] *= $transfer_value;

							// Remove elected from ranking.
							$wb['ranking'] = array_values( array_diff( $wb['ranking'], array( $elected ) ) );

							$next              = self::first_active_choice( $wb['ranking'], $active );
							$key               = $next ? $next : 'exhausted';
							$transfers[ $key ] = ( $transfers[ $key ] ?? 0 ) + $wb['weight'];
						}
						unset( $wb );

						$round_data['transfers'] = array_merge( $round_data['transfers'], $transfers );
					} else {
						// No surplus — just remove from rankings.
						foreach ( $working as &$wb ) {
							$wb['ranking'] = array_values( array_diff( $wb['ranking'], array( $elected ) ) );
						}
						unset( $wb );
					}

					if ( count( $winners ) >= $num_seats ) {
						break;
					}
				}

				$round_data['elected'] = $elected_this_round;
				$rounds[]              = $round_data;
				continue;
			}

			// No one met quota — check if remaining candidates <= remaining seats.
			$remaining_seats = $num_seats - count( $winners );
			if ( count( $active ) <= $remaining_seats ) {
				foreach ( $active as $candidate ) {
					$winners[]   = $candidate;
					$event_log[] = sprintf( 'Round %d: %s elected (seats >= remaining candidates).', $round, $candidate );
				}
				$round_data['elected'] = $active;
				$active                = array();
				$rounds[]              = $round_data;
				break;
			}

			// Eliminate ONE candidate with the fewest votes.
			$min_votes  = min( $counts );
			$last_place = array_keys( $counts, $min_votes, true );

			if ( count( $last_place ) > 1 ) {
				usort(
					$last_place,
					function ( $a, $b ) use ( $initial_counts ) {
						$diff = ( $initial_counts[ $a ] ?? 0 ) - ( $initial_counts[ $b ] ?? 0 );
						return 0 !== $diff ? $diff : strcmp( $a, $b );
					}
				);
			}
			$to_eliminate = $last_place[0];

			$event_log[]  = sprintf( 'Round %d: Eliminated %s (%.2f votes).', $round, $to_eliminate, $min_votes );
			$eliminated[] = $to_eliminate;
			$active       = array_values( array_diff( $active, array( $to_eliminate ) ) );

			// Transfer eliminated candidate's ballots at their current weight.
			$transfers = array();
			foreach ( $working as &$wb ) {
				$first_active = self::first_active_choice( $wb['ranking'], array_merge( $active, array( $to_eliminate ) ) );
				if ( $first_active !== $to_eliminate ) {
					continue;
				}
				$wb['ranking']     = array_values( array_diff( $wb['ranking'], array( $to_eliminate ) ) );
				$next              = self::first_active_choice( $wb['ranking'], $active );
				$key               = $next ? $next : 'exhausted';
				$transfers[ $key ] = ( $transfers[ $key ] ?? 0 ) + $wb['weight'];
			}
			unset( $wb );

			$round_data['eliminated'] = array( $to_eliminate );
			$round_data['transfers']  = $transfers;
			$rounds[]                 = $round_data;
		}

		return $this->build_result( $winners, self::count_weighted( $working, $active ), $rounds, $eliminated, $quota, $total_votes, $num_seats, $event_log, $options );
	}

	/**
	 * Count weighted first-choice votes for active candidates.
	 */
	private static function count_weighted( array $working, array $active ): array {
		$counts = array_fill_keys( $active, 0.0 );
		foreach ( $working as $wb ) {
			$choice = self::first_active_choice( $wb['ranking'], $active );
			if ( null !== $choice ) {
				$counts[ $choice ] += $wb['weight'];
			}
		}
		return $counts;
	}

	/**
	 * Get the first choice from a ranking that is still active.
	 */
	private static function first_active_choice( array $ranking, array $active ): ?string {
		foreach ( $ranking as $opt ) {
			if ( in_array( $opt, $active, true ) ) {
				return $opt;
			}
		}
		return null;
	}

	private function build_result(
		array $winners,
		array $final_counts,
		array $rounds,
		array $eliminated,
		$quota,
		int $total_votes,
		int $num_seats,
		array $event_log,
		array $options
	): array {
		return array(
			'winner'                => ! empty( $winners ) ? $winners[0] : null,
			'winners'               => $winners,
			'vote_counts'           => $final_counts,
			'percentages'           => array(),
			'rankings'              => array(),
			'rounds'                => $rounds,
			'tie'                   => false,
			'tied_candidates'       => array(),
			'eliminated_candidates' => $eliminated,
			'total_votes'           => $total_votes,
			'total_valid_votes'     => $total_votes,
			'quota'                 => $quota,
			'num_seats'             => $num_seats,
			'event_log'             => $event_log,
			'validation'            => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}
}
