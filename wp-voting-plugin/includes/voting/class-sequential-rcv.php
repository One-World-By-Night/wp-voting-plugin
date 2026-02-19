<?php
/**
 * Sequential Ranked Choice Voting (Multi-Seat IRO).
 *
 * Fills multiple seats by running independent IRO (Instant Runoff) elections
 * in sequence. After each seat is filled, the winner is removed from all
 * ballots and a fresh IRO election runs for the next seat.
 *
 * Every voter's ballot carries full weight in every seat election — there is
 * no fractional surplus transfer (unlike STV/WIGM).
 *
 * Tie-breaking within each IRO round uses batch elimination: when multiple
 * candidates tie for last place, all tied candidates are eliminated
 * simultaneously. This matches OWBN's historical multi-seat election practice.
 *
 * Config:
 *   num_seats (int) — Number of seats to fill. Default 1.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Sequential_RCV implements WPVP_Voting_Algorithm {

	public function get_type(): string {
		return 'sequential_rcv';
	}

	public function get_label(): string {
		return __( 'Sequential Ranked Choice (Multi-Seat)', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Multi-winner ranked voting. Runs independent IRO elections for each seat. After each winner, they are removed and a fresh election runs at full ballot weight.', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$num_seats   = max( 1, isset( $config['num_seats'] ) ? intval( $config['num_seats'] ) : 1 );
		$total_votes = count( $ballots );
		$event_log   = array();
		$winners     = array();
		$seats       = array();

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
				$ranking = $ballot_payload;
			}

			if ( is_string( $ranking ) ) {
				$ranking = array( $ranking );
			}
			if ( ! is_array( $ranking ) ) {
				continue;
			}

			$has_abstain = in_array( 'Abstain', $ranking, true );

			// Filter to valid options only.
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

		if ( $abstain_count > 0 ) {
			$event_log[] = sprintf( '%d abstention(s) recorded but not counted toward the result.', $abstain_count );
			$total_votes -= $abstain_count;
		}

		$working_options = $options;
		$last_counts     = array();

		for ( $seat = 1; $seat <= $num_seats; $seat++ ) {
			if ( empty( $working_options ) ) {
				$event_log[] = sprintf( 'Seat %d: No candidates remaining.', $seat );
				break;
			}

			// If only one candidate remains, auto-elect.
			if ( count( $working_options ) === 1 ) {
				$auto_winner = $working_options[0];
				$winners[]   = $auto_winner;
				$event_log[] = sprintf( 'Seat %d: %s wins unopposed (last remaining candidate).', $seat, $auto_winner );

				$seats[] = array(
					'seat_number' => $seat,
					'winner'      => $auto_winner,
					'rounds'      => array(),
					'eliminated'  => array(),
				);

				$working_options = array();
				break;
			}

			$event_log[] = sprintf( '--- Seat %d election (%d candidates, %d ballots) ---', $seat, count( $working_options ), count( $working ) );

			// Build synthetic ballot rows for the inner RCV call.
			$iro_ballots = array();
			foreach ( $working as $ranking ) {
				$iro_ballots[] = array(
					'ballot_data'  => array( 'choice' => $ranking ),
					'user_id'      => 0,
					'display_name' => '',
				);
			}

			// Run a full IRO election with batch tie elimination.
			$iro = new WPVP_RCV();
			$iro_result = $iro->process( $iro_ballots, $working_options, array(
				'batch_eliminate_ties' => true,
			) );

			$seat_winner = $iro_result['winner'];

			if ( null === $seat_winner ) {
				// Tie — record and stop.
				$event_log[] = sprintf( 'Seat %d: Ended in a tie. Cannot fill remaining seats.', $seat );
				$seats[] = array(
					'seat_number'      => $seat,
					'winner'           => null,
					'rounds'           => isset( $iro_result['rounds'] ) ? $iro_result['rounds'] : array(),
					'eliminated'       => isset( $iro_result['eliminated_candidates'] ) ? $iro_result['eliminated_candidates'] : array(),
					'tied_candidates'  => isset( $iro_result['tied_candidates'] ) ? $iro_result['tied_candidates'] : array(),
				);

				// Merge the per-seat event log.
				if ( ! empty( $iro_result['event_log'] ) ) {
					foreach ( $iro_result['event_log'] as $entry ) {
						$event_log[] = '  ' . $entry;
					}
				}
				break;
			}

			$winners[]   = $seat_winner;
			$last_counts = isset( $iro_result['vote_counts'] ) ? $iro_result['vote_counts'] : array();
			$event_log[] = sprintf( 'Seat %d: %s elected.', $seat, $seat_winner );

			// Merge the per-seat event log.
			if ( ! empty( $iro_result['event_log'] ) ) {
				foreach ( $iro_result['event_log'] as $entry ) {
					$event_log[] = '  ' . $entry;
				}
			}

			$seats[] = array(
				'seat_number' => $seat,
				'winner'      => $seat_winner,
				'rounds'      => isset( $iro_result['rounds'] ) ? $iro_result['rounds'] : array(),
				'eliminated'  => isset( $iro_result['eliminated_candidates'] ) ? $iro_result['eliminated_candidates'] : array(),
			);

			// Remove the winner from all ballots and options.
			$working_options = array_values( array_diff( $working_options, array( $seat_winner ) ) );
			$working         = self::strip_candidate( $working, $seat_winner );
		}

		// Build final result.
		$first_winner  = ! empty( $winners ) ? $winners[0] : null;
		$total_valid   = $total_votes; // All non-abstain ballots are valid.
		$all_counts    = ! empty( $last_counts ) ? $last_counts : array();

		$percentages = array();
		$count_sum   = array_sum( $all_counts );
		if ( $count_sum > 0 ) {
			foreach ( $all_counts as $opt => $c ) {
				$percentages[ $opt ] = round( ( $c / $count_sum ) * 100, 2 );
			}
		}

		return array(
			'winner'                => $first_winner,
			'winners'               => $winners,
			'vote_counts'           => $all_counts,
			'percentages'           => $percentages,
			'rankings'              => array(),
			'rounds'                => array(), // Rounds are inside seats[].
			'seats'                 => $seats,
			'num_seats'             => $num_seats,
			'tie'                   => false,
			'tied_candidates'       => array(),
			'eliminated_candidates' => array(),
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

	/**
	 * Remove a candidate from all ballots.
	 *
	 * Returns only ballots that still have at least one preference remaining.
	 *
	 * @param array  $working  Array of ranking arrays.
	 * @param string $candidate Candidate to remove.
	 * @return array Filtered working ballots.
	 */
	private static function strip_candidate( array $working, string $candidate ): array {
		$result = array();
		foreach ( $working as $ranking ) {
			$filtered = array_values(
				array_filter(
					$ranking,
					function ( $opt ) use ( $candidate ) {
						return $opt !== $candidate;
					}
				)
			);
			if ( ! empty( $filtered ) ) {
				$result[] = $filtered;
			}
		}
		return $result;
	}
}
