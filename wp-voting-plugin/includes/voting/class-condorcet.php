<?php
/**
 * Condorcet voting method with Schulze fallback.
 *
 * Voters rank options by preference. The Condorcet winner is the candidate who
 * beats every other candidate in pairwise comparison. If no Condorcet winner
 * exists, the Schulze method (beatpath) determines the winner.
 *
 * Key fixes from v1:
 *  - Unranked candidates are treated as ranked below all ranked candidates.
 *  - Smith set uses the correct iterative dominance algorithm.
 *  - Division-by-zero handled for 0 or 1 candidate.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Condorcet implements WPVP_Voting_Algorithm {

	public function get_type(): string {
		return 'condorcet';
	}

	public function get_label(): string {
		return __( 'Condorcet Method', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Voters rank options. The candidate who wins every head-to-head matchup wins. Falls back to Schulze method if no Condorcet winner exists.', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$total_votes = count( $ballots );
		$event_log   = array();

		// Exclude Abstain from candidate pool — track separately.
		$abstain_count = 0;
		$options       = array_values( array_filter( $options, function ( $o ) { return WPVP_ABSTAIN_LABEL !== $o; } ) );

		$n             = count( $options );

		// Edge cases.
		if ( 0 === $n ) {
			return $this->build_result( null, array(), array(), array(), $total_votes, $event_log, $options );
		}
		if ( 1 === $n ) {
			$event_log[] = $options[0] . ' wins uncontested.';
			return $this->build_result( $options[0], array(), array(), array(), $total_votes, $event_log, $options );
		}

		// ---- Build pairwise matrix ----
		// $matrix[$a][$b] = number of voters who prefer $a over $b.
		$matrix = array();
		foreach ( $options as $a ) {
			$matrix[ $a ] = array();
			foreach ( $options as $b ) {
				$matrix[ $a ][ $b ] = 0;
			}
		}

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
			$has_abstain = in_array( WPVP_ABSTAIN_LABEL, $ranking, true );

			// Filter to valid options, preserve order.
			$ranked = array_values(
				array_filter(
					$ranking,
					function ( $o ) use ( $options ) {
						return in_array( $o, $options, true );
					}
				)
			);

			// Abstain-only ballot: skip pairwise comparisons entirely.
			if ( empty( $ranked ) ) {
				if ( $has_abstain ) {
					++$abstain_count;
				}
				continue;
			}

			// Unranked candidates are treated as ranked below all ranked ones.
			$unranked = array_values( array_diff( $options, $ranked ) );

			// All ranked candidates beat all unranked candidates.
			foreach ( $ranked as $r ) {
				foreach ( $unranked as $u ) {
					++$matrix[ $r ][ $u ];
				}
			}

			// Among ranked candidates: earlier beats later.
			$len = count( $ranked );
			for ( $i = 0; $i < $len; $i++ ) {
				for ( $j = $i + 1; $j < $len; $j++ ) {
					++$matrix[ $ranked[ $i ] ][ $ranked[ $j ] ];
				}
			}

			// Among unranked candidates: no preference either way (tied).
		}

		// Abstain ballots are tracked but excluded from the election.
		if ( $abstain_count > 0 ) {
			$event_log[] = sprintf( '%d abstention(s) recorded but not counted toward the result.', $abstain_count );
			$total_votes -= $abstain_count;
		}

		// ---- Log pairwise results ----
		$pairwise_winners = array();
		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = $i + 1; $j < $n; $j++ ) {
				$a   = $options[ $i ];
				$b   = $options[ $j ];
				$a_v = $matrix[ $a ][ $b ];
				$b_v = $matrix[ $b ][ $a ];

				if ( $a_v > $b_v ) {
					$pairwise_winners[ $a ][ $b ] = true;
					$event_log[]                  = sprintf( '%s vs %s: %s wins (%d-%d)', $a, $b, $a, $a_v, $b_v );
				} elseif ( $b_v > $a_v ) {
					$pairwise_winners[ $b ][ $a ] = true;
					$event_log[]                  = sprintf( '%s vs %s: %s wins (%d-%d)', $a, $b, $b, $b_v, $a_v );
				} else {
					$event_log[] = sprintf( '%s vs %s: Tie (%d-%d)', $a, $b, $a_v, $b_v );
				}
			}
		}

		// ---- Find Condorcet winner ----
		$condorcet_winner = null;
		$win_counts       = array_fill_keys( $options, 0 );

		foreach ( $options as $a ) {
			$beats_all = true;
			foreach ( $options as $b ) {
				if ( $a === $b ) {
					continue;
				}
				if ( $matrix[ $a ][ $b ] > $matrix[ $b ][ $a ] ) {
					++$win_counts[ $a ];
				} else {
					$beats_all = false;
				}
			}
			if ( $beats_all ) {
				$condorcet_winner = $a;
			}
		}

		if ( $condorcet_winner ) {
			$event_log[] = sprintf( 'Condorcet winner: %s (beats all others head-to-head).', $condorcet_winner );
		} else {
			$event_log[] = 'No Condorcet winner exists. Using Schulze method.';
		}

		// ---- Schulze method (beatpath) ----
		// Strongest path strength from i to j.
		$strength = array();
		foreach ( $options as $a ) {
			$strength[ $a ] = array();
			foreach ( $options as $b ) {
				if ( $a !== $b ) {
					$strength[ $a ][ $b ] = $matrix[ $a ][ $b ] > $matrix[ $b ][ $a ]
						? $matrix[ $a ][ $b ]
						: 0;
				} else {
					$strength[ $a ][ $b ] = 0;
				}
			}
		}

		// Floyd-Warshall to find strongest paths.
		foreach ( $options as $k ) {
			foreach ( $options as $i ) {
				if ( $i === $k ) {
					continue;
				}
				foreach ( $options as $j ) {
					if ( $j === $i || $j === $k ) {
						continue;
					}
					$via_k = min( $strength[ $i ][ $k ], $strength[ $k ][ $j ] );
					if ( $via_k > $strength[ $i ][ $j ] ) {
						$strength[ $i ][ $j ] = $via_k;
					}
				}
			}
		}

		// Score each candidate: number of others they beat via strongest path.
		$schulze_scores = array();
		foreach ( $options as $a ) {
			$score = 0;
			foreach ( $options as $b ) {
				if ( $a !== $b && $strength[ $a ][ $b ] > $strength[ $b ][ $a ] ) {
					++$score;
				}
			}
			$schulze_scores[ $a ] = $score;
		}

		arsort( $schulze_scores );
		$max_score      = max( $schulze_scores );
		$schulze_top    = array_keys( $schulze_scores, $max_score, true );
		$schulze_winner = count( $schulze_top ) === 1 ? $schulze_top[0] : null;

		$winner = $condorcet_winner ? $condorcet_winner : $schulze_winner;
		$tie    = ! $condorcet_winner && count( $schulze_top ) > 1;

		if ( $schulze_winner && ! $condorcet_winner ) {
			$event_log[] = sprintf( 'Schulze winner: %s (score %d).', $schulze_winner, $max_score );
		} elseif ( $tie ) {
			$event_log[] = 'Schulze method resulted in a tie between: ' . implode( ', ', $schulze_top );
		}

		// ---- Smith set ----
		$smith_set   = self::find_smith_set( $options, $matrix );
		$event_log[] = 'Smith set: ' . implode( ', ', $smith_set );

		// ---- Build percentages from pairwise win counts ----
		$comparisons = $n - 1;
		$percentages = array();
		foreach ( $win_counts as $opt => $wins ) {
			$percentages[ $opt ] = $comparisons > 0 ? round( ( $wins / $comparisons ) * 100, 2 ) : 0;
		}

		return $this->build_result(
			$winner,
			$matrix,
			$schulze_scores,
			$smith_set,
			$total_votes,
			$event_log,
			$options,
			$condorcet_winner,
			$win_counts,
			$percentages,
			$tie ? $schulze_top : array()
		);
	}

	/**
	 * Find the Smith set: the smallest non-empty set S where every member of S
	 * beats every non-member in pairwise comparison.
	 *
	 * Algorithm: start with every candidate. Iteratively remove candidates that
	 * are beaten by some candidate outside the current set. But also: if a
	 * candidate is removed, the candidates that beat them might now become
	 * "outsiders" and beat remaining members. We iterate until stable.
	 *
	 * Correct approach: Strongly connected components of the "beats" relation.
	 * The Smith set is the union of all components in the top cycle.
	 */
	private static function find_smith_set( array $options, array $matrix ): array {
		$n = count( $options );
		if ( $n <= 1 ) {
			return $options;
		}

		// Build a "beats" adjacency: $a beats $b if matrix[$a][$b] > matrix[$b][$a].
		$beats = array();
		foreach ( $options as $a ) {
			$beats[ $a ] = array();
			foreach ( $options as $b ) {
				if ( $a !== $b && $matrix[ $a ][ $b ] > $matrix[ $b ][ $a ] ) {
					$beats[ $a ][] = $b;
				}
			}
		}

		// Find strongly connected components (Tarjan's algorithm simplified via
		// iterative Kosaraju's which is easier to follow).

		// Step 1: DFS order on the beats graph.
		$visited = array();
		$order   = array();
		foreach ( $options as $node ) {
			if ( empty( $visited[ $node ] ) ) {
				self::dfs( $node, $beats, $visited, $order );
			}
		}

		// Step 2: Build reverse graph.
		$reverse = array();
		foreach ( $options as $a ) {
			$reverse[ $a ] = array();
		}
		foreach ( $beats as $a => $neighbours ) {
			foreach ( $neighbours as $b ) {
				$reverse[ $b ][] = $a;
			}
		}

		// Step 3: DFS on reverse graph in reverse finish order → SCCs.
		$visited = array();
		$sccs    = array();
		foreach ( array_reverse( $order ) as $node ) {
			if ( empty( $visited[ $node ] ) ) {
				$component = array();
				self::dfs( $node, $reverse, $visited, $component );
				$sccs[] = $component;
			}
		}

		// Step 4: The Smith set is the first SCC (in topological order) that
		// has no incoming edges from outside itself.
		// In a DAG of SCCs, the root components form the Smith set.
		// Since Kosaraju's returns SCCs in reverse topological order of the
		// original graph, the FIRST SCC is the one with no incoming "beats"
		// from outside — i.e., the dominant set.
		//
		// Actually Kosaraju's in the form above returns SCCs in topological
		// order of the condensation. The first SCC is the source component,
		// which is the set that beats all others — the Smith set.

		if ( ! empty( $sccs ) ) {
			// The Smith set may span multiple root SCCs if there are ties at the top.
			// Expand: include any SCC that is not beaten by a candidate outside it.
			$smith     = $sccs[0];
			$smith_set = array_flip( $smith );

			// Check if any later SCC has members that beat members of our set.
			for ( $i = 1; $i < count( $sccs ); $i++ ) {
				$is_dominated = false;
				foreach ( $sccs[ $i ] as $outside ) {
					foreach ( $smith as $inside ) {
						if ( $matrix[ $inside ][ $outside ] > $matrix[ $outside ][ $inside ] ) {
							$is_dominated = true;
							break 2;
						}
					}
				}
				if ( ! $is_dominated ) {
					// This SCC ties with the Smith set — include it.
					$smith = array_merge( $smith, $sccs[ $i ] );
				} else {
					break; // All subsequent SCCs are dominated.
				}
			}

			return $smith;
		}

		return $options; // Fallback: all candidates.
	}

	/**
	 * Depth-first search helper.
	 */
	private static function dfs( string $node, array &$graph, array &$visited, array &$order ): void {
		$visited[ $node ] = true;
		foreach ( $graph[ $node ] ?? array() as $neighbour ) {
			if ( empty( $visited[ $neighbour ] ) ) {
				self::dfs( $neighbour, $graph, $visited, $order );
			}
		}
		$order[] = $node;
	}

	private function build_result(
		?string $winner,
		array $matrix,
		array $schulze_scores,
		array $smith_set,
		int $total_votes,
		array $event_log,
		array $options,
		?string $condorcet_winner = null,
		array $win_counts = array(),
		array $percentages = array(),
		array $tied = array()
	): array {
		arsort( $win_counts );

		return array(
			'winner'            => $winner,
			'winners'           => $winner ? array( $winner ) : array(),
			'vote_counts'       => $win_counts,
			'percentages'       => $percentages,
			'rankings'          => array(),
			'rounds'            => array(),
			'tie'               => ! empty( $tied ),
			'tied_candidates'   => $tied,
			'total_votes'       => $total_votes,
			'total_valid_votes' => $total_votes,
			'event_log'         => $event_log,
			'pairwise_matrix'   => $matrix,
			'schulze_scores'    => $schulze_scores,
			'smith_set'         => $smith_set,
			'condorcet_winner'  => $condorcet_winner,
			'validation'        => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}
}
