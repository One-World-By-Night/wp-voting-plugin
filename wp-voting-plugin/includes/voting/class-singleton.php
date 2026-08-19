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
		$vote_counts   = array_fill_keys( $options, 0 );
		$invalid_votes = 0;
		$abstain_count = 0;
		$event_log     = array();

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
		if ( isset( $vote_counts[ WPVP_ABSTAIN_LABEL ] ) ) {
			$abstain_count = $vote_counts[ WPVP_ABSTAIN_LABEL ];
			unset( $vote_counts[ WPVP_ABSTAIN_LABEL ] );
		}

		$total_votes       = count( $ballots );
		$total_valid_votes = $total_votes - $invalid_votes - $abstain_count;

		arsort( $vote_counts );

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

		$percentages = array();
		if ( $total_valid_votes > 0 ) {
			foreach ( $vote_counts as $option => $count ) {
				$percentages[ $option ] = round( ( $count / $total_valid_votes ) * 100, 2 );
			}
		}

		// --- majority_threshold modifier ------------------------------------
		// A singleton vote may carry a win-condition modifier instead of plain
		// plurality: the affirmative option must clear a bar of the VALID
		// (non-abstain, non-invalid) votes.  'two_thirds' => ceil(valid * 2/3),
		// 'simple' (default) => floor(valid/2)+1 (a true majority).  This turns
		// an Approve/Deny proposition into a Passed / Did-Not-Pass result.
		// Only applied to proposition-style votes (an affirmative option is
		// present, or there are at most two non-abstain options); genuine
		// multi-candidate races keep pure plurality (passed stays null).
		$threshold_key       = isset( $config['majority_threshold'] ) ? (string) $config['majority_threshold'] : 'simple';
		$affirmative         = self::detect_affirmative( array_keys( $vote_counts ) );
		$is_proposition      = ( null !== $affirmative ) || ( count( $vote_counts ) <= 2 );
		$target              = ( null !== $affirmative ) ? $affirmative : $winner;
		$passed              = null;
		$required            = null;
		$threshold_label     = '';
		$affirmative_votes   = null;
		$affirmative_percent = null;

		if ( $is_proposition && null !== $target && $total_valid_votes > 0 ) {
			$target_votes = isset( $vote_counts[ $target ] ) ? (int) $vote_counts[ $target ] : 0;
			if ( 'two_thirds' === $threshold_key ) {
				$required        = (int) ceil( $total_valid_votes * 2 / 3 );
				$threshold_label = __( '2/3 majority', 'wp-voting-plugin' );
			} else {
				$required        = (int) floor( $total_valid_votes / 2 ) + 1;
				$threshold_label = __( 'simple majority', 'wp-voting-plugin' );
			}
			$passed              = ( $target_votes >= $required );
			$affirmative_votes   = $target_votes;
			$affirmative_percent = round( ( $target_votes / $total_valid_votes ) * 100, 2 );

			$event_log[] = sprintf(
				/* translators: 1: option, 2: votes, 3: valid total, 4: required, 5: threshold label */
				'%1$s received %2$d of %3$d valid votes; %4$d required for %5$s — %6$s.',
				$target,
				$target_votes,
				$total_valid_votes,
				$required,
				$threshold_label,
				$passed ? 'PASSED' : 'DID NOT PASS'
			);
		}

		if ( $abstain_count > 0 ) {
			$vote_counts[ WPVP_ABSTAIN_LABEL ] = $abstain_count;
		}

		$ranking_counts = $vote_counts;
		unset( $ranking_counts[ WPVP_ABSTAIN_LABEL ] );
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
			'winner_votes'      => $max_votes,
			// majority_threshold modifier outputs (null on a pure plurality race).
			'passed'              => $passed,
			'threshold_key'       => $threshold_key,
			'threshold_label'     => $threshold_label,
			'threshold_required'  => $required,
			'affirmative_option'  => $affirmative,
			'target_option'       => $target,
			'affirmative_votes'   => $affirmative_votes,
			'affirmative_percent' => $affirmative_percent,
			'event_log'         => $event_log,
			'validation'        => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}

	/**
	 * Find the affirmative option among a proposition's choices, if any.
	 *
	 * Used by the majority_threshold modifier to decide which option the bar
	 * applies to (e.g. an R&U's "Approve"). Returns the matching option string,
	 * or null for a candidate race with no yes/for option.
	 *
	 * @param string[] $options Option labels (Abstain already removed).
	 * @return string|null
	 */
	private static function detect_affirmative( array $options ) {
		$affirmatives = array( 'approve', 'approved', 'yes', 'for', 'in favor', 'in favour', 'accept', 'aye', 'ratify', 'confirm', 'adopt', 'affirm', 'pass' );
		foreach ( $options as $option ) {
			if ( in_array( strtolower( trim( (string) $option ) ), $affirmatives, true ) ) {
				return $option;
			}
		}
		return null;
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
