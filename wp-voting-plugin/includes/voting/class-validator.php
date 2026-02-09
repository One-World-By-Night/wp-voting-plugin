<?php
/**
 * Validates vote processing results for correctness.
 *
 * Each voting type gets generic checks plus type-specific checks.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Validator {

	/**
	 * Run all validation checks.
	 *
	 * @param string $type    Voting type key.
	 * @param array  $results Processed results from an algorithm.
	 * @param array  $options Valid option strings.
	 * @return array { is_valid: bool, errors: string[], warnings: string[] }
	 */
	public static function validate( string $type, array $results, array $options ): array {
		$v = array(
			'is_valid' => true,
			'errors'   => array(),
			'warnings' => array(),
		);

		// ---- Generic checks ----

		$total_votes = $results['total_votes'] ?? 0;
		$total_valid = $results['total_valid_votes'] ?? $total_votes;

		if ( $total_votes < 0 ) {
			$v['errors'][] = 'total_votes is negative.';
		}

		// vote_counts keys should be a subset of options.
		$counts       = $results['vote_counts'] ?? array();
		$unknown_keys = array_diff( array_keys( $counts ), $options );
		if ( ! empty( $unknown_keys ) ) {
			$v['warnings'][] = 'vote_counts contains unknown options: ' . implode( ', ', $unknown_keys );
		}

		// Sum of recognised vote_counts should equal total_valid_votes.
		$counted_sum = 0;
		foreach ( $options as $opt ) {
			$counted_sum += $counts[ $opt ] ?? 0;
		}
		if ( $counted_sum !== $total_valid ) {
			$v['warnings'][] = sprintf(
				'Sum of vote_counts (%d) does not match total_valid_votes (%d).',
				$counted_sum,
				$total_valid
			);
		}

		// ---- Type-specific checks ----

		switch ( $type ) {
			case 'singleton':
				$v = self::validate_singleton( $results, $options, $v );
				break;
			case 'rcv':
				$v = self::validate_rcv( $results, $options, $v );
				break;
			case 'stv':
				$v = self::validate_stv( $results, $options, $v );
				break;
			case 'condorcet':
				$v = self::validate_condorcet( $results, $options, $v );
				break;
			case 'disciplinary':
				$v = self::validate_disciplinary( $results, $options, $v );
				break;
			case 'consent':
				$v = self::validate_consent( $results, $v );
				break;
		}

		// Set is_valid based on presence of errors.
		$v['is_valid'] = empty( $v['errors'] );

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  Singleton (FPTP).
	 * ----------------------------------------------------------------*/

	private static function validate_singleton( array $r, array $opts, array $v ): array {
		// Winner should be in options (or null for tie).
		if ( ! empty( $r['winner'] ) && ! in_array( $r['winner'], $opts, true ) ) {
			$v['errors'][] = 'Winner is not a valid option.';
		}

		// If there's a winner, they should have the most votes.
		if ( ! empty( $r['winner'] ) ) {
			$counts       = $r['vote_counts'] ?? array();
			$winner_count = $counts[ $r['winner'] ] ?? 0;
			$max_count    = ! empty( $counts ) ? max( $counts ) : 0;
			if ( $winner_count < $max_count ) {
				$v['errors'][] = 'Winner does not have the highest vote count.';
			}
		}

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  RCV / IRV.
	 * ----------------------------------------------------------------*/

	private static function validate_rcv( array $r, array $opts, array $v ): array {
		$rounds = $r['rounds'] ?? array();
		if ( empty( $rounds ) ) {
			$v['warnings'][] = 'No round data recorded.';
			return $v;
		}

		// Each round's total should not exceed total_votes.
		foreach ( $rounds as $i => $round ) {
			$round_total = array_sum( $round['counts'] ?? array() );
			$exhausted   = $round['exhausted'] ?? 0;
			$active      = $round_total;

			if ( $active > ( $r['total_votes'] ?? 0 ) ) {
				$v['errors'][] = sprintf( 'Round %d active votes (%d) exceeds total_votes.', $i + 1, $active );
			}
		}

		// Winner (if not a tie) should have appeared in the last round.
		if ( ! empty( $r['winner'] ) && ! empty( $rounds ) ) {
			$last_round = end( $rounds );
			if ( ! isset( $last_round['counts'][ $r['winner'] ] ) ) {
				$v['warnings'][] = 'Winner not present in final round counts.';
			}
		}

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  STV.
	 * ----------------------------------------------------------------*/

	private static function validate_stv( array $r, array $opts, array $v ): array {
		$winners   = $r['winners'] ?? array();
		$num_seats = $r['num_seats'] ?? 1;

		if ( count( $winners ) > $num_seats ) {
			$v['errors'][] = sprintf(
				'More winners (%d) than seats (%d).',
				count( $winners ),
				$num_seats
			);
		}

		// Each winner should be a valid option.
		foreach ( $winners as $w ) {
			if ( ! in_array( $w, $opts, true ) ) {
				$v['errors'][] = "Winner '{$w}' is not a valid option.";
			}
		}

		// No duplicate winners.
		if ( count( $winners ) !== count( array_unique( $winners ) ) ) {
			$v['errors'][] = 'Duplicate winners detected.';
		}

		// Quota should be positive.
		$quota = $r['quota'] ?? 0;
		if ( $quota <= 0 && ( $r['total_votes'] ?? 0 ) > 0 ) {
			$v['warnings'][] = 'Quota is zero or negative with non-zero votes.';
		}

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  Condorcet.
	 * ----------------------------------------------------------------*/

	private static function validate_condorcet( array $r, array $opts, array $v ): array {
		$matrix = $r['pairwise_matrix'] ?? array();

		// Matrix should be square and cover all options.
		foreach ( $opts as $a ) {
			if ( ! isset( $matrix[ $a ] ) ) {
				$v['warnings'][] = "Pairwise matrix missing row for '{$a}'.";
				continue;
			}
			foreach ( $opts as $b ) {
				if ( $a === $b ) {
					continue;
				}
				if ( ! isset( $matrix[ $a ][ $b ] ) ) {
					$v['warnings'][] = "Pairwise matrix missing cell [{$a}][{$b}].";
				}
			}
		}

		// Condorcet winner (if declared) should beat all others head-to-head.
		if ( ! empty( $r['condorcet_winner'] ) ) {
			$cw = $r['condorcet_winner'];
			foreach ( $opts as $other ) {
				if ( $other === $cw ) {
					continue;
				}
				$cw_votes    = $matrix[ $cw ][ $other ] ?? 0;
				$other_votes = $matrix[ $other ][ $cw ] ?? 0;
				if ( $cw_votes <= $other_votes ) {
					$v['errors'][] = "Declared Condorcet winner '{$cw}' does not beat '{$other}' head-to-head.";
				}
			}
		}

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  Disciplinary.
	 * ----------------------------------------------------------------*/

	/*
	------------------------------------------------------------------
	 *  Consent.
	 * ----------------------------------------------------------------*/

	private static function validate_consent( array $r, array $v ): array {
		// Winner must be 'Passed' or 'Objected'.
		$winner = $r['winner'] ?? '';
		if ( ! in_array( $winner, array( 'Passed', 'Objected' ), true ) ) {
			$v['errors'][] = "Consent winner must be 'Passed' or 'Objected', got '{$winner}'.";
		}

		// If passed, objection_count must be 0.
		$passed    = $r['passed'] ?? null;
		$obj_count = $r['objection_count'] ?? 0;

		if ( true === $passed && $obj_count > 0 ) {
			$v['errors'][] = 'Consent marked as passed but has objections.';
		}
		if ( false === $passed && 0 === $obj_count ) {
			$v['errors'][] = 'Consent marked as objected but has no objections.';
		}

		return $v;
	}

	/*
	------------------------------------------------------------------
	 *  Disciplinary.
	 * ----------------------------------------------------------------*/

	private static function validate_disciplinary( array $r, array $opts, array $v ): array {
		$cascade = $r['cascade_rounds'] ?? array();

		if ( empty( $cascade ) ) {
			$v['warnings'][] = 'No cascade round data recorded.';
			return $v;
		}

		// Winner should be one of the punishment levels.
		if ( ! empty( $r['winner'] ) && ! in_array( $r['winner'], $opts, true ) ) {
			$v['errors'][] = 'Winner is not a valid punishment level.';
		}

		// Each cascade round's accumulated votes should be non-negative.
		foreach ( $cascade as $round ) {
			if ( ( $round['accumulated'] ?? 0 ) < 0 ) {
				$v['errors'][] = sprintf(
					'Negative accumulated votes in cascade round for %s.',
					$round['punishment'] ?? '?'
				);
			}
		}

		return $v;
	}
}
