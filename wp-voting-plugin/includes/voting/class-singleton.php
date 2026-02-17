<?php
/**
 * Singleton / First Past The Post voting algorithm.
 *
 * Each voter selects one option. The option with the most votes wins.
 * Unrecognised votes are tracked separately (total_votes vs total_valid_votes).
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Singleton implements WPVP_Voting_Algorithm {

	public function get_type(): string {
		return 'singleton';
	}

	public function get_label(): string {
		return __( 'Single Choice (FPTP)', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Each voter selects one option. The option with the most votes wins.', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		// Initialise counts.
		$vote_counts   = array_fill_keys( $options, 0 );
		$invalid_votes = 0;
		$abstain_count = 0;
		$event_log     = array();

		// Count votes.
		foreach ( $ballots as $ballot ) {
			$ballot_payload = $ballot['ballot_data'];

			// Extract choice from ballot_data (handles both new and legacy formats).
			if ( is_array( $ballot_payload ) && isset( $ballot_payload['choice'] ) ) {
				$choice = $ballot_payload['choice'];
			} else {
				// Legacy format - ballot_data is the choice directly.
				$choice = $ballot_payload;
			}

			// Choice may be a string or the first element of an array.
			if ( is_array( $choice ) ) {
				$choice = $choice[0] ?? null;
			}

			if ( is_string( $choice ) && isset( $vote_counts[ $choice ] ) ) {
				++$vote_counts[ $choice ];
			} else {
				++$invalid_votes;
				$event_log[] = sprintf(
					'Ballot from user %d contained unrecognised option and was not counted.',
					$ballot['user_id'] ?? 0
				);
			}
		}

		// Abstain votes are tracked but excluded from winner determination and percentages.
		if ( isset( $vote_counts['Abstain'] ) ) {
			$abstain_count = $vote_counts['Abstain'];
			unset( $vote_counts['Abstain'] );
		}

		$total_votes       = count( $ballots );
		$total_valid_votes = $total_votes - $invalid_votes - $abstain_count;

		// Sort by votes descending.
		arsort( $vote_counts );

		// Determine winner (Abstain excluded).
		$max_votes      = ! empty( $vote_counts ) ? max( $vote_counts ) : 0;
		$top_candidates = array_keys( $vote_counts, $max_votes, true );

		$tie    = count( $top_candidates ) > 1;
		$winner = $tie ? null : $top_candidates[0];

		if ( $tie ) {
			$event_log[] = 'Tie between: ' . implode( ', ', $top_candidates );
		} elseif ( $winner ) {
			$event_log[] = sprintf( '%s wins with %d votes.', $winner, $max_votes );
		}

		if ( $abstain_count > 0 ) {
			$event_log[] = sprintf( '%d abstention(s) recorded but not counted toward the result.', $abstain_count );
		}

		// Percentages (Abstain excluded from denominator).
		$percentages = array();
		if ( $total_valid_votes > 0 ) {
			foreach ( $vote_counts as $option => $count ) {
				$percentages[ $option ] = round( ( $count / $total_valid_votes ) * 100, 2 );
			}
		}

		// Re-add Abstain to vote_counts for display (after winner determination).
		if ( $abstain_count > 0 ) {
			$vote_counts['Abstain'] = $abstain_count;
		}

		// Rankings (competition / "1224" style) — excludes Abstain.
		$ranking_counts = $vote_counts;
		unset( $ranking_counts['Abstain'] );
		$rankings = self::build_rankings( $ranking_counts );

		return array(
			'winner'            => $winner,
			'winners'           => $winner ? array( $winner ) : array(),
			'vote_counts'       => $vote_counts,
			'percentages'       => $percentages,
			'rankings'          => $rankings,
			'rounds'            => array(),
			'tie'               => $tie,
			'tied_candidates'   => $tie ? $top_candidates : array(),
			'total_votes'       => $total_votes,
			'total_valid_votes' => $total_valid_votes,
			'invalid_votes'     => $invalid_votes,
			'event_log'         => $event_log,
			'validation'        => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}

	/**
	 * Build competition rankings ("1224" style).
	 */
	private static function build_rankings( array $vote_counts ): array {
		$rankings = array();
		$rank     = 1;
		$prev     = null;
		$skip     = 0;

		foreach ( $vote_counts as $option => $count ) {
			if ( null !== $prev && $count < $prev ) {
				$rank += $skip;
				$skip  = 0;
			}
			$rankings[ $rank ][] = $option;
			++$skip;
			$prev = $count;
		}

		return $rankings;
	}
}
