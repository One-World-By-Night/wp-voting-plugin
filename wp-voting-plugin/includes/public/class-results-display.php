<?php
/**
 * Results rendering and CSV export.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Results_Display {

	public function __construct() {
		add_action( 'wp_ajax_wpvp_export_csv', array( $this, 'ajax_export_csv' ) );
	}

	/*
	------------------------------------------------------------------
	 *  Static render helpers (called from templates).
	 * ----------------------------------------------------------------*/

	/**
	 * Render results for a vote based on its type.
	 */
	public static function render( object $vote, object $results ): void {
		$final           = $results->final_results ? $results->final_results : array();
		$winner          = $results->winner_data ? $results->winner_data : array();
		$rounds          = $results->rounds_data ? $results->rounds_data : array();
		$stats           = $results->statistics ? $results->statistics : array();
		$type            = $vote->voting_type;
		$decoded_options = json_decode( $vote->voting_options, true );
		$options         = $decoded_options ? $decoded_options : array();

		?>
		<div class="wpvp-results" data-type="<?php echo esc_attr( $type ); ?>">

			<?php self::render_winner_banner( $winner, $type ); ?>

			<?php
			switch ( $type ) {
				case 'singleton':
					self::render_singleton( $final, $results );
					break;
				case 'rcv':
					self::render_rcv( $final, $rounds, $results );
					break;
				case 'stv':
					self::render_stv( $final, $rounds, $winner, $results );
					break;
				case 'condorcet':
					self::render_condorcet( $final, $stats, $results );
					break;
				case 'disciplinary':
					self::render_disciplinary( $final, $stats, $results );
					break;
				case 'consent':
					self::render_consent( $final, $stats, $results );
					break;
			}
			?>

			<div class="wpvp-results__meta">
				<p>
					<?php
					printf(
						esc_html__( 'Total votes: %1$d | Calculated: %2$s', 'wp-voting-plugin' ),
						intval( $results->total_votes ),
						esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $results->calculated_at ) ) )
					);
					?>
				</p>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wpvp_export_csv&vote_id=' . intval( $vote->id ) . '&_wpnonce=' . wp_create_nonce( 'wpvp_export_csv' ) ) ); ?>"
						class="wpvp-btn wpvp-btn--secondary">
						<?php esc_html_e( 'Export CSV', 'wp-voting-plugin' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $stats['event_log'] ) && current_user_can( 'manage_options' ) ) : ?>
				<details class="wpvp-results__log">
					<summary><?php esc_html_e( 'Processing Log', 'wp-voting-plugin' ); ?></summary>
					<ol>
						<?php foreach ( (array) $stats['event_log'] as $entry ) : ?>
							<li><?php echo esc_html( $entry ); ?></li>
						<?php endforeach; ?>
					</ol>
				</details>
			<?php endif; ?>

		<?php self::render_voter_list( $vote ); ?>
		<?php self::render_voter_comments( $vote ); ?>
		</div>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Winner banner.
	 * ----------------------------------------------------------------*/

	private static function render_winner_banner( array $winner, string $type ): void {
		if ( ! empty( $winner['tie'] ) ) {
			?>
			<div class="wpvp-results__banner wpvp-results__banner--tie">
				<strong><?php esc_html_e( 'Result: Tie', 'wp-voting-plugin' ); ?></strong>
				<?php if ( ! empty( $winner['tied_candidates'] ) ) : ?>
					<span>
						<?php echo esc_html( implode( ', ', (array) $winner['tied_candidates'] ) ); ?>
					</span>
				<?php endif; ?>
			</div>
			<?php
		} elseif ( ! empty( $winner['winner'] ) ) {
			if ( 'consent' === $type ) {
				$label = __( 'Result:', 'wp-voting-plugin' );
			} elseif ( 'disciplinary' === $type ) {
				$label = __( 'Outcome:', 'wp-voting-plugin' );
			} else {
				$label = __( 'Winner:', 'wp-voting-plugin' );
			}
			?>
			<div class="wpvp-results__banner wpvp-results__banner--winner">
				<strong><?php echo esc_html( $label ); ?></strong>
				<span><?php echo esc_html( $winner['winner'] ); ?></span>
			</div>
			<?php
		} elseif ( ! empty( $winner['winners'] ) ) {
			?>
			<div class="wpvp-results__banner wpvp-results__banner--winner">
				<strong><?php esc_html_e( 'Winners:', 'wp-voting-plugin' ); ?></strong>
				<span><?php echo esc_html( implode( ', ', (array) $winner['winners'] ) ); ?></span>
			</div>
			<?php
		}
	}

	/*
	------------------------------------------------------------------
	 *  Type-specific renderers.
	 * ----------------------------------------------------------------*/

	private static function render_singleton( array $final, object $results ): void {
		$counts      = $final['vote_counts'] ?? array();
		$percentages = $final['percentages'] ?? array();
		$is_tie      = ! empty( $final['tie'] );

		if ( empty( $counts ) ) {
			return;
		}

		arsort( $counts );
		$max_count = max( $counts );
		?>
		<table class="wpvp-results__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Option', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( '%', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Bar', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $counts as $option => $count ) : ?>
					<?php
					// Highlight winner (option with max count).
					$is_winner = ( $count === $max_count && $max_count > 0 );
					$badge_label = $is_tie && $is_winner ? __( 'Tied', 'wp-voting-plugin' ) : __( 'Winner', 'wp-voting-plugin' );
					?>
					<tr<?php echo $is_winner ? ' class="wpvp-results__row--winner"' : ''; ?>>
						<td>
							<?php echo esc_html( $option ); ?>
							<?php if ( $is_winner ) : ?>
								<span class="wpvp-results__winner-badge"><?php echo esc_html( $badge_label ); ?></span>
							<?php endif; ?>
						</td>
						<td><strong><?php echo esc_html( $count ); ?></strong></td>
						<td><strong><?php echo esc_html( number_format( $percentages[ $option ] ?? 0, 1 ) . '%' ); ?></strong></td>
						<td>
							<div class="wpvp-bar">
								<div class="wpvp-bar__fill<?php echo $is_winner ? ' wpvp-bar__fill--winner' : ''; ?>" style="width:<?php echo esc_attr( $max_count > 0 ? round( ( $count / $max_count ) * 100 ) : 0 ); ?>%;"></div>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function render_rcv( array $final, $rounds_data, object $results ): void {
		$rounds = is_array( $rounds_data ) ? $rounds_data : array();

		if ( empty( $rounds ) ) {
			self::render_singleton( $final, $results );
			return;
		}

		?>
		<h4><?php esc_html_e( 'Round-by-Round Results', 'wp-voting-plugin' ); ?></h4>
		<?php foreach ( $rounds as $i => $round ) : ?>
			<div class="wpvp-round">
				<h5>
					<?php printf( esc_html__( 'Round %d', 'wp-voting-plugin' ), intval( $i ) + 1 ); ?>
					<?php if ( ! empty( $round['eliminated'] ) ) : ?>
						<span class="wpvp-round__eliminated">
							<?php printf( esc_html__( '(Eliminated: %s)', 'wp-voting-plugin' ), esc_html( $round['eliminated'] ) ); ?>
						</span>
					<?php endif; ?>
				</h5>
				<?php if ( ! empty( $round['counts'] ) ) : ?>
					<table class="wpvp-results__table wpvp-results__table--compact">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Candidate', 'wp-voting-plugin' ); ?></th>
								<th><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$round_counts = $round['counts'];
							arsort( $round_counts );
							foreach ( $round_counts as $candidate => $count ) :
								?>
								<tr>
									<td><?php echo esc_html( $candidate ); ?></td>
									<td><?php echo esc_html( $count ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php
	}

	private static function render_stv( array $final, $rounds_data, array $winner, object $results ): void {
		// STV renders similarly to RCV but shows elected candidates per round.
		$rounds = is_array( $rounds_data ) ? $rounds_data : array();

		if ( ! empty( $winner['winners'] ) ) {
			echo '<p class="wpvp-results__seats">';
			printf(
				esc_html__( 'Elected (%1$d seats): %2$s', 'wp-voting-plugin' ),
				count( $winner['winners'] ),
				esc_html( implode( ', ', $winner['winners'] ) )
			);
			echo '</p>';
		}

		if ( ! empty( $rounds ) ) {
			self::render_rcv( $final, $rounds, $results );
		} else {
			self::render_singleton( $final, $results );
		}
	}

	private static function render_condorcet( array $final, array $stats, object $results ): void {
		$counts      = $final['vote_counts'] ?? array();
		$percentages = $final['percentages'] ?? array();

		// Pairwise matrix if available.
		$rankings = $final['rankings'] ?? array();

		if ( ! empty( $counts ) ) {
			arsort( $counts );
			?>
			<h4><?php esc_html_e( 'First-Choice Votes', 'wp-voting-plugin' ); ?></h4>
			<table class="wpvp-results__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Candidate', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'First-Choice', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( '%', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $counts as $option => $count ) : ?>
						<tr>
							<td><?php echo esc_html( $option ); ?></td>
							<td><?php echo esc_html( $count ); ?></td>
							<td><?php echo esc_html( ( $percentages[ $option ] ?? 0 ) . '%' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		// Rankings.
		if ( ! empty( $rankings ) ) {
			?>
			<h4><?php esc_html_e( 'Final Rankings', 'wp-voting-plugin' ); ?></h4>
			<ol class="wpvp-rankings">
				<?php foreach ( $rankings as $rank => $candidates ) : ?>
					<li value="<?php echo esc_attr( $rank ); ?>">
						<?php echo esc_html( is_array( $candidates ) ? implode( ', ', $candidates ) : $candidates ); ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php
		}
	}

	private static function render_disciplinary( array $final, array $stats, object $results ): void {
		$counts = $final['vote_counts'] ?? array();

		if ( empty( $counts ) ) {
			return;
		}

		// Show cascade breakdown.
		$cascade = $stats['event_log'] ?? array();
		?>
		<h4><?php esc_html_e( 'Vote Distribution', 'wp-voting-plugin' ); ?></h4>
		<table class="wpvp-results__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Punishment Level', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Direct Votes', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $counts as $punishment => $count ) : ?>
					<tr>
						<td><?php echo esc_html( $punishment ); ?></td>
						<td><?php echo esc_html( $count ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function render_consent( array $final, array $stats, object $results ): void {
		$passed    = $final['passed'] ?? false;
		$objectors = $final['objectors'] ?? array();
		$obj_count = $final['objection_count'] ?? count( $objectors );

		?>
		<div class="wpvp-results__consent">
			<?php if ( $passed ) : ?>
				<div class="wpvp-results__consent-status wpvp-results__consent-status--passed">
					<strong><?php esc_html_e( 'Passed by Consent', 'wp-voting-plugin' ); ?></strong>
					<p><?php esc_html_e( 'No objections were filed during the review period.', 'wp-voting-plugin' ); ?></p>
				</div>
			<?php else : ?>
				<div class="wpvp-results__consent-status wpvp-results__consent-status--objected">
					<strong><?php esc_html_e( 'Did Not Pass', 'wp-voting-plugin' ); ?></strong>
					<p>
						<?php
						printf(
							esc_html(
								_n(
									'%d objection was filed during the review period.',
									'%d objections were filed during the review period.',
									$obj_count,
									'wp-voting-plugin'
								)
							),
							intval( $obj_count )
						);
						?>
					</p>
				</div>

				<?php
				// Check if vote has anonymous voting enabled.
				if ( ! empty( $objectors ) && current_user_can( 'manage_options' ) ) :
					$vote_obj         = WPVP_Database::get_vote( $results->vote_id );
					$decoded_settings = json_decode( $vote_obj->settings ?? '{}', true );
					$anonymous_voting = ! empty( $decoded_settings['anonymous_voting'] );

					if ( ! $anonymous_voting ) :
						?>
						<h4><?php esc_html_e( 'Objectors', 'wp-voting-plugin' ); ?></h4>
						<ul class="wpvp-results__objectors">
							<?php foreach ( $objectors as $name ) : ?>
								<li><?php echo esc_html( $name ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  CSV export.
	 * ----------------------------------------------------------------*/

	/**
	 * AJAX handler for CSV export.
	 */
	public function ajax_export_csv(): void {
		// Only admins can export.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-voting-plugin' ) );
		}

		check_admin_referer( 'wpvp_export_csv' );

		$vote_id = isset( $_GET['vote_id'] ) ? absint( $_GET['vote_id'] ) : 0;
		$vote    = WPVP_Database::get_vote( $vote_id );
		if ( ! $vote ) {
			wp_die( esc_html__( 'Vote not found.', 'wp-voting-plugin' ) );
		}

		$results = WPVP_Database::get_results( $vote_id );
		if ( ! $results ) {
			wp_die( esc_html__( 'No results to export.', 'wp-voting-plugin' ) );
		}

		$filename = sanitize_file_name( 'vote-results-' . $vote_id . '-' . gmdate( 'Y-m-d' ) . '.csv' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility.
		fwrite( $output, "\xEF\xBB\xBF" );

		// Metadata header.
		fputcsv( $output, array( 'Vote Title', self::csv_safe( $vote->proposal_name ) ) );
		fputcsv( $output, array( 'Vote Type', self::csv_safe( $vote->voting_type ) ) );
		fputcsv( $output, array( 'Total Votes', $results->total_votes ) );
		fputcsv( $output, array( 'Total Voters', $results->total_voters ) );
		fputcsv( $output, array( 'Calculated At', $results->calculated_at ) );
		fputcsv( $output, array() );

		// Results.
		$final  = $results->final_results ? $results->final_results : array();
		$counts = $final['vote_counts'] ?? array();

		if ( ! empty( $counts ) ) {
			fputcsv( $output, array( 'Option', 'Votes', 'Percentage' ) );
			$percentages = $final['percentages'] ?? array();
			foreach ( $counts as $option => $count ) {
				fputcsv(
					$output,
					array(
						self::csv_safe( $option ),
						$count,
						( $percentages[ $option ] ?? 0 ) . '%',
					)
				);
			}
		}

		// Winner.
		$winner = $results->winner_data ? $results->winner_data : array();
		fputcsv( $output, array() );
		if ( ! empty( $winner['winner'] ) ) {
			fputcsv( $output, array( 'Winner', self::csv_safe( $winner['winner'] ) ) );
		} elseif ( ! empty( $winner['winners'] ) ) {
			fputcsv( $output, array( 'Winners', self::csv_safe( implode( ', ', $winner['winners'] ) ) ) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Sanitize a string for CSV to prevent formula injection.
	 * Prefix formula-triggering characters with a tab.
	 */
	private static function csv_safe( string $value ): string {
		$triggers = array( '=', '+', '-', '@', "\t", "\r", "\n" );
		if ( '' !== $value && in_array( $value[0], $triggers, true ) ) {
			$value = "\t" . $value;
		}
		return $value;
	}

	/*
	------------------------------------------------------------------
	 *  Voter list for non-anonymous votes.
	 * ----------------------------------------------------------------*/

	/**
	 * Render detailed voter list for non-anonymous votes.
	 * Shows: Display Name, Role, Vote Choice, Vote Date (sorted oldest first).
	 */
	private static function render_voter_list( object $vote ): void {
		// Check if vote is anonymous.
		$decoded_settings = json_decode( $vote->settings ?? '{}', true );
		$anonymous_voting = ! empty( $decoded_settings['anonymous_voting'] );

		// Only show voter list to admins (they can see it on all votes, including anonymous).
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get all ballots for this vote.
		global $wpdb;
		$table   = $wpdb->prefix . 'wpvp_ballots';
		$ballots = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, ballot_data, voted_at FROM {$table} WHERE vote_id = %d ORDER BY voted_at ASC",
				$vote->id
			),
			ARRAY_A
		);

		if ( empty( $ballots ) ) {
			return;
		}

		?>
		<?php $allow_comments = ! empty( $decoded_settings['allow_voter_comments'] ); ?>
		<div class="wpvp-results__voters">
			<h3><?php esc_html_e( 'Voter List', 'wp-voting-plugin' ); ?></h3>
			<table class="wpvp-voters-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Voter', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Role', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Vote', 'wp-voting-plugin' ); ?></th>
						<?php if ( $allow_comments ) : ?>
							<th><?php esc_html_e( 'Comment', 'wp-voting-plugin' ); ?></th>
						<?php endif; ?>
						<th><?php esc_html_e( 'Date', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ballots as $ballot ) : ?>
						<?php
						$ballot_data = json_decode( $ballot['ballot_data'], true );
						if ( ! is_array( $ballot_data ) ) {
							$ballot_data = array( 'choice' => $ballot_data );
						}

						// Extract data (new format or legacy).
						$choice       = $ballot_data['choice'] ?? $ballot_data;
						$voting_role  = $ballot_data['voting_role'] ?? '';
						$stored_name  = $ballot_data['display_name'] ?? '';
						$stored_login = $ballot_data['username'] ?? '';

						// Try to get current user data.
						$user_id      = (int) $ballot['user_id'];
						$current_user = $user_id ? get_userdata( $user_id ) : null;

						if ( $current_user ) {
							$display_name = $current_user->display_name;
						} else {
							$display_name = $stored_name ?: ( 'User #' . $user_id );
						}

						// Format vote choice for display.
						if ( is_array( $choice ) ) {
							$choice_display = implode( ' > ', array_map( 'esc_html', $choice ) );
						} else {
							$choice_display = esc_html( $choice );
						}

						// Format date.
						$vote_date = wp_date(
							get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
							strtotime( $ballot['voted_at'] )
						);
						?>
						<tr>
							<td><?php echo esc_html( $display_name ); ?></td>
							<td><?php echo esc_html( $voting_role ); ?></td>
							<td><?php echo $choice_display; // Already escaped above. ?></td>
							<?php if ( $allow_comments ) : ?>
								<td><?php echo esc_html( $ballot_data['voter_comment'] ?? '' ); ?></td>
							<?php endif; ?>
							<td><?php echo esc_html( $vote_date ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
	/*
	------------------------------------------------------------------
	 *  Anonymous voter comments.
	 * ----------------------------------------------------------------*/

	/**
	 * Render voter comments for results display.
	 *
	 * - Anonymous votes: shows unattributed comments to everyone.
	 * - Non-anonymous votes: shows attributed comments to everyone.
	 *   (Admins already see comments in the voter list table, so skip for them.)
	 */
	private static function render_voter_comments( object $vote ): void {
		$decoded_settings = json_decode( $vote->settings ?? '{}', true );
		$allow_comments   = ! empty( $decoded_settings['allow_voter_comments'] );
		$anonymous_voting = ! empty( $decoded_settings['anonymous_voting'] );

		if ( ! $allow_comments ) {
			return;
		}

		// Admins see comments in the voter list table on all votes.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table   = $wpdb->prefix . 'wpvp_ballots';
		$ballots = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ballot_data FROM {$table} WHERE vote_id = %d ORDER BY voted_at ASC",
				$vote->id
			),
			ARRAY_A
		);

		if ( empty( $ballots ) ) {
			return;
		}

		$comments = array();
		foreach ( $ballots as $ballot ) {
			$ballot_data = json_decode( $ballot['ballot_data'], true );
			if ( is_array( $ballot_data ) && ! empty( $ballot_data['voter_comment'] ) ) {
				$entry = array( 'comment' => $ballot_data['voter_comment'] );
				if ( ! $anonymous_voting ) {
					$entry['display_name'] = $ballot_data['display_name'] ?? '';
					$entry['voting_role']  = $ballot_data['voting_role'] ?? '';
				}
				$comments[] = $entry;
			}
		}

		if ( empty( $comments ) ) {
			return;
		}

		?>
		<div class="wpvp-results__voter-comments">
			<h3><?php esc_html_e( 'Voter Comments', 'wp-voting-plugin' ); ?></h3>
			<ul class="wpvp-voter-comments-list" style="list-style: none; padding: 0;">
				<?php foreach ( $comments as $entry ) : ?>
					<li style="padding: 8px 12px; margin-bottom: 8px; background: #f9f9f9; border-left: 3px solid #0073aa; border-radius: 2px;">
						<?php if ( ! $anonymous_voting && ! empty( $entry['display_name'] ) ) : ?>
							<strong><?php echo esc_html( $entry['display_name'] ); ?></strong>
							<?php if ( ! empty( $entry['voting_role'] ) ) : ?>
								<span style="color: #646970;">(<?php echo esc_html( $entry['voting_role'] ); ?>)</span>
							<?php endif; ?>
							<br>
						<?php endif; ?>
						<?php echo esc_html( $entry['comment'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

// Boot the results/export handler.
new WPVP_Results_Display();
