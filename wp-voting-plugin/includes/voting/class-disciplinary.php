<?php
/**
 * Disciplinary cascade voting.
 *
 * Voters select a punishment level from 8 predefined options ordered from
 * most severe to least severe. The algorithm:
 *
 *   1. Start from the most severe punishment (Permanent Ban).
 *   2. If it meets its threshold (66.7% for Permanent Ban, 50%+1 for all
 *      others), that punishment wins.
 *   3. If not, those votes cascade DOWN to the next-less-severe punishment,
 *      accumulating with votes already there.
 *   4. Repeat until a threshold is met or we reach the least severe option.
 *
 * This finds the MOST SEVERE punishment that accumulates enough support.
 *
 * The punishment levels (most → least severe):
 *   Permanent Ban → Indefinite Ban/3 Strikes → Temporary Ban → 2 Strikes →
 *   1 Strike → Probation → Censure → Condemnation
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Disciplinary implements WPVP_Voting_Algorithm {

	public function get_type(): string {
		return 'disciplinary';
	}

	public function get_label(): string {
		return __( 'Disciplinary Vote', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Voters select a punishment level. Votes cascade from most severe to least severe until a threshold is met.', 'wp-voting-plugin' );
	}

	/**
	 * Default punishment levels, most severe first.
	 */
	public static function default_punishments(): array {
		return array(
			'Permanent Ban',
			'Indefinite Ban / 3 Strikes',
			'Temporary Ban',
			'2 Strikes',
			'1 Strike',
			'Probation',
			'Censure',
			'Condemnation',
		);
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$total_votes = count( $ballots );
		$event_log   = array();

		// Use provided options (they should already be in most→least order).
		// If empty, use defaults.
		$punishments = ! empty( $options ) ? $options : self::default_punishments();

		// Count raw votes per punishment.
		$raw_counts = array_fill_keys( $punishments, 0 );
		$invalid    = 0;

		foreach ( $ballots as $ballot ) {
			$ballot_payload = $ballot['ballot_data'];

			// Extract choice from ballot_data (handles both new and legacy formats).
			if ( is_array( $ballot_payload ) && isset( $ballot_payload['choice'] ) ) {
				$choice = $ballot_payload['choice'];
			} else {
				// Legacy format - ballot_data is the choice directly.
				$choice = $ballot_payload;
			}

			if ( is_array( $choice ) ) {
				$choice = $choice[0] ?? null;
			}
			if ( is_string( $choice ) && isset( $raw_counts[ $choice ] ) ) {
				++$raw_counts[ $choice ];
			} else {
				++$invalid;
			}
		}

		// Abstain votes are tracked but excluded from winner determination.
		$abstain_count = 0;
		if ( isset( $raw_counts[ WPVP_ABSTAIN_LABEL ] ) ) {
			$abstain_count = $raw_counts[ WPVP_ABSTAIN_LABEL ];
			unset( $raw_counts[ WPVP_ABSTAIN_LABEL ] );
			$punishments = array_values( array_filter( $punishments, function ( $p ) { return WPVP_ABSTAIN_LABEL !== $p; } ) );
		}

		$total_valid = $total_votes - $invalid - $abstain_count;
		$event_log[] = sprintf( 'Total valid votes: %d. Invalid: %d.', $total_valid, $invalid );

		if ( $abstain_count > 0 ) {
			$event_log[] = sprintf( '%d abstention(s) recorded but not counted toward the result.', $abstain_count );
		}

		// Cascade from most severe to least severe.
		$cascade_rounds = array();
		$accumulated    = 0;
		$winner         = null;
		$winner_votes   = 0;

		for ( $i = 0; $i < count( $punishments ); $i++ ) {
			$punishment   = $punishments[ $i ];
			$accumulated += $raw_counts[ $punishment ];

			// Threshold: 66.7% (2/3) for the most severe, 50%+1 for all others.
			if ( 0 === $i ) {
				$threshold       = $total_valid > 0 ? ceil( $total_valid * 2 / 3 ) : 1;
				$threshold_label = '66.7%';
			} else {
				$threshold       = $total_valid > 0 ? floor( $total_valid / 2 ) + 1 : 1;
				$threshold_label = '50%+1';
			}

			$percentage = $total_valid > 0 ? round( ( $accumulated / $total_valid ) * 100, 2 ) : 0;
			$met        = $accumulated >= $threshold;

			$cascade_rounds[] = array(
				'punishment'      => $punishment,
				'raw_votes'       => $raw_counts[ $punishment ],
				'accumulated'     => $accumulated,
				'threshold'       => $threshold,
				'threshold_label' => $threshold_label,
				'percentage'      => $percentage,
				'met'             => $met,
			);

			$event_log[] = sprintf(
				'%s: %d raw + %d cascaded = %d accumulated (%.1f%%). Threshold: %d (%s) — %s',
				$punishment,
				$raw_counts[ $punishment ],
				$accumulated - $raw_counts[ $punishment ],
				$accumulated,
				$percentage,
				$threshold,
				$threshold_label,
				$met ? 'MET' : 'not met'
			);

			if ( $met && ! $winner ) {
				$winner       = $punishment;
				$winner_votes = $accumulated;
			}
		}

		// If no threshold was met (unlikely but possible with rounding),
		// the least severe punishment with any accumulated votes wins.
		if ( ! $winner && $total_valid > 0 ) {
			$winner       = end( $punishments );
			$winner_votes = $accumulated;
			$event_log[]  = sprintf( 'No threshold met. Defaulting to least severe: %s.', $winner );
		}

		if ( $winner ) {
			$event_log[] = sprintf( 'Result: %s with %d accumulated votes.', $winner, $winner_votes );
		}

		// Build vote_counts and percentages from raw counts (Abstain excluded from denominator).
		$percentages = array();
		if ( $total_valid > 0 ) {
			foreach ( $raw_counts as $p => $count ) {
				$percentages[ $p ] = round( ( $count / $total_valid ) * 100, 2 );
			}
		}

		// Re-add Abstain to vote_counts for display (after winner determination).
		if ( $abstain_count > 0 ) {
			$raw_counts[ WPVP_ABSTAIN_LABEL ] = $abstain_count;
		}

		return array(
			'winner'            => $winner,
			'winners'           => $winner ? array( $winner ) : array(),
			'winner_votes'      => $winner_votes,
			'vote_counts'       => $raw_counts,
			'percentages'       => $percentages,
			'rankings'          => array(),
			'rounds'            => array(),
			'cascade_rounds'    => $cascade_rounds,
			'tie'               => false,
			'tied_candidates'   => array(),
			'total_votes'       => $total_votes,
			'total_valid_votes' => $total_valid,
			'invalid_votes'     => $invalid,
			'event_log'         => $event_log,
			'validation'        => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}
}
