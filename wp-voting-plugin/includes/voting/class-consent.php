<?php
/**
 * Consent agenda voting.
 *
 * The proposal is posted for a defined period. Members may "Object" during
 * that window. When the vote closes:
 *
 *   - 0 objections → Passed (consent by silence).
 *   - 1+ objections → Objected (requires full vote or withdrawal).
 *
 * This is not ranked voting. The only ballot action is to file an objection.
 * Members who do not object are consenting implicitly.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Consent implements WPVP_Voting_Algorithm {

	public function get_type(): string {
		return 'consent';
	}

	public function get_label(): string {
		return __( 'Consent Agenda', 'wp-voting-plugin' );
	}

	public function get_description(): string {
		return __( 'Proposal passes automatically unless someone objects during the review period.', 'wp-voting-plugin' );
	}

	public function process( array $ballots, array $options, array $config = array() ): array {
		$total_ballots = count( $ballots );
		$event_log     = array();

		// Every ballot cast is an objection.
		$objectors = array();
		$invalid   = 0;

		foreach ( $ballots as $ballot ) {
			$choice = $ballot['ballot_data'];
			if ( is_array( $choice ) ) {
				$choice = $choice[0] ?? null;
			}

			// Any ballot is treated as an objection regardless of content.
			if ( ! empty( $choice ) ) {
				$voter_name  = $ballot['display_name'] ?? $ballot['voter_name'] ?? ( 'User #' . ( $ballot['user_id'] ?? '?' ) );
				$objectors[] = $voter_name;
			} else {
				++$invalid;
			}
		}

		$objection_count = count( $objectors );
		$passed          = ( 0 === $objection_count );

		if ( $passed ) {
			$winner      = 'Passed';
			$event_log[] = 'No objections received. Proposal passes by consent.';
		} else {
			$winner      = 'Objected';
			$event_log[] = sprintf(
				'%d objection(s) received. Proposal did NOT pass by consent.',
				$objection_count
			);
			foreach ( $objectors as $name ) {
				$event_log[] = sprintf( 'Objection filed by: %s', $name );
			}
		}

		// Build vote_counts: "Object" count and implicit "Consent" (not cast).
		$vote_counts = array(
			'Passed'   => $passed ? 1 : 0,
			'Objected' => $objection_count,
		);

		$total_valid = $total_ballots - $invalid;

		return array(
			'winner'            => $winner,
			'winners'           => array( $winner ),
			'vote_counts'       => $vote_counts,
			'percentages'       => array(),
			'rankings'          => array(),
			'rounds'            => array(),
			'tie'               => false,
			'tied_candidates'   => array(),
			'total_votes'       => $total_ballots,
			'total_valid_votes' => $total_valid,
			'invalid_votes'     => $invalid,
			'objectors'         => $objectors,
			'objection_count'   => $objection_count,
			'passed'            => $passed,
			'event_log'         => $event_log,
			'validation'        => array(
				'is_valid' => true,
				'errors'   => array(),
				'warnings' => array(),
			),
		);
	}
}
