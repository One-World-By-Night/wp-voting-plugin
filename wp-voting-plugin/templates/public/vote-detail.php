<?php
/**
 * Template: Single vote detail page.
 *
 * Shows vote info, ballot form (if eligible), and results (if viewable).
 *
 * Variables available:
 *   $vote     object  Vote row from database.
 *   $vote_id  int     Vote ID.
 *   $user_id  int     Current user ID (0 if not logged in).
 *
 * @package WP_Voting_Plugin
 */

defined( 'ABSPATH' ) || exit;

$stages           = WPVP_Database::get_vote_stages();
$types            = WPVP_Database::get_vote_types();
$type_info        = $types[ $vote->voting_type ] ?? array();
$stage_label      = $stages[ $vote->voting_stage ] ?? $vote->voting_stage;
$decoded_settings = json_decode( $vote->settings, true );
$settings         = $decoded_settings ? $decoded_settings : array();
?>

<div class="wpvp-vote-detail">
	<div class="wpvp-vote-detail__header">
		<h2 class="wpvp-vote-detail__title"><?php echo esc_html( $vote->proposal_name ); ?></h2>
		<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
			<?php echo esc_html( $stage_label ); ?>
		</span>
	</div>

	<div class="wpvp-vote-detail__meta">
		<span class="wpvp-vote-detail__type">
			<?php echo esc_html( $type_info['label'] ?? $vote->voting_type ); ?>
		</span>
		<?php if ( $vote->opening_date ) : ?>
			<span class="wpvp-vote-detail__opens">
				<?php
				printf(
					esc_html__( 'Opens: %s', 'wp-voting-plugin' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->opening_date ) ) )
				);
				?>
			</span>
		<?php endif; ?>
		<?php if ( $vote->closing_date ) : ?>
			<span class="wpvp-vote-detail__closes">
				<?php
				printf(
					esc_html__( 'Closes: %s', 'wp-voting-plugin' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) ) )
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $vote->proposal_description ) ) : ?>
		<div class="wpvp-vote-detail__description">
			<?php echo wp_kses_post( $vote->proposal_description ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $type_info['description'] ) ) : ?>
		<p class="wpvp-vote-detail__type-desc">
			<em><?php echo esc_html( $type_info['description'] ); ?></em>
		</p>
	<?php endif; ?>

	<?php
	// Ballot form section.
	if ( 'open' === $vote->voting_stage ) :
		if ( ! $user_id ) :
			?>
			<div class="wpvp-notice wpvp-notice--info">
				<p><?php esc_html_e( 'You must be logged in to vote.', 'wp-voting-plugin' ); ?></p>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="wpvp-btn wpvp-btn--primary">
					<?php esc_html_e( 'Log In', 'wp-voting-plugin' ); ?>
				</a>
			</div>
			<?php
		else :
			WPVP_Ballot::render_form( $vote, $user_id );
		endif;
	endif;
	?>

	<?php
	// Show current results for open votes (after ballot form).
	if ( 'open' === $vote->voting_stage && $user_id && ! empty( $settings['show_results_before_closing'] ) ) :
		$current_results = null;

		try {
			// Try to get existing results, or calculate on-the-fly.
			$current_results = WPVP_Database::get_results( $vote_id );

			// If no results exist, calculate live results.
			if ( ! $current_results ) {
				$ballots = WPVP_Database::get_ballots( $vote_id );
				if ( ! empty( $ballots ) ) {
					$algo = WPVP_Processor::get_algorithm( $vote->voting_type );
					if ( $algo ) {
						// Build options list.
						$raw_options = json_decode( $vote->voting_options, true );
						$options     = is_array( $raw_options ) ? array_column( $raw_options, 'text' ) : array();
						$config      = array( 'num_seats' => max( 1, intval( $vote->number_of_winners ) ) );

						// Run algorithm.
						$calculated = $algo->process( $ballots, $options, $config );

						if ( $calculated && is_array( $calculated ) ) {
							// Create a temporary results object.
							// Note: These are arrays, not JSON strings, because get_results()
							// normally decodes them from the database.
							$current_results = (object) array(
								'vote_id'        => $vote_id,
								'winner_data'    => array(
									'winner'          => $calculated['winner'] ?? '',
									'tie'             => $calculated['tie'] ?? false,
									'tied_candidates' => $calculated['tied_candidates'] ?? array(),
								),
								'final_results'  => $calculated,
								'rounds_data'    => $calculated['rounds'] ?? array(),
								'statistics'     => $calculated,
								'total_votes'    => $calculated['total_votes'] ?? 0,
								'calculated_at'  => gmdate( 'Y-m-d H:i:s' ),
							);
						}
					}
				}
			}

			if ( $current_results ) :
				?>
				<div class="wpvp-vote-detail__current-results">
					<h3><?php esc_html_e( 'Current Results', 'wp-voting-plugin' ); ?></h3>
					<p class="wpvp-notice wpvp-notice--info" style="font-size: 0.9em; margin-bottom: 1em;">
						<?php esc_html_e( 'These are live results. They will update as more votes are cast.', 'wp-voting-plugin' ); ?>
					</p>
					<?php WPVP_Results_Display::render( $vote, $current_results ); ?>
				</div>
				<?php
			endif;
		} catch ( Throwable $e ) {
			// Silently fail - just don't show results if there's an error.
			// Optionally log for admins:
			if ( current_user_can( 'manage_options' ) ) {
				echo '<!-- Live results error: ' . esc_html( $e->getMessage() ) . ' -->';
			}
		}
	endif;
	?>

	<?php
	// Results section.
	$can_view_results = $user_id ? WPVP_Permissions::can_view_results( $user_id, $vote_id ) : false;
	if ( $can_view_results ) :
		$results = WPVP_Database::get_results( $vote_id );
		if ( $results ) :
			?>
			<div class="wpvp-vote-detail__results">
				<h3><?php esc_html_e( 'Results', 'wp-voting-plugin' ); ?></h3>
				<?php WPVP_Results_Display::render( $vote, $results ); ?>
			</div>
			<?php
		endif;
	endif;
	?>

	<?php
	// Role-based vote history: show ballots matching user's current eligible roles
	// or cast by the current user themselves (users can always see their own vote).
	if ( $user_id ) :
		$eligible_roles = WPVP_Permissions::get_eligible_voting_roles( $user_id, $vote );

		global $wpdb;
		$ballots_table = $wpdb->prefix . 'wpvp_ballots';
		$all_ballots   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, ballot_data, voted_at FROM {$ballots_table} WHERE vote_id = %d ORDER BY voted_at DESC",
				$vote_id
			),
			ARRAY_A
		);

		if ( ! empty( $all_ballots ) ) :
			// Filter ballots: match by voting_role OR by user_id.
			$matching_ballots = array();
			$seen_ids         = array();

			foreach ( $all_ballots as $ballot ) {
				$ballot_data = json_decode( $ballot['ballot_data'], true );
				if ( ! is_array( $ballot_data ) ) {
					continue;
				}

				$ballot_role = $ballot_data['voting_role'] ?? '';
				$ballot_uid  = (int) $ballot['user_id'];
				$unique_key  = $ballot_uid . '|' . $ballot_role;

				// Skip duplicates.
				if ( isset( $seen_ids[ $unique_key ] ) ) {
					continue;
				}

				$role_match = ! empty( $eligible_roles ) && in_array( $ballot_role, $eligible_roles, true );
				$user_match = ( $ballot_uid === $user_id );

				if ( $role_match || $user_match ) {
					$seen_ids[ $unique_key ] = true;
					$matching_ballots[]     = array(
						'ballot_data' => $ballot_data,
						'voted_at'    => $ballot['voted_at'],
						'user_id'     => $ballot_uid,
					);
				}
			}

				if ( ! empty( $matching_ballots ) ) :
					$allow_comments = ! empty( $settings['allow_voter_comments'] );
					?>
					<div class="wpvp-vote-detail__role-history" style="margin-top: 2em;">
						<h3><?php esc_html_e( "Your Role's Vote History", 'wp-voting-plugin' ); ?></h3>
						<p style="font-size: 0.9em; color: #646970; margin-bottom: 1em;">
							<?php esc_html_e( 'Showing votes cast under your current eligible roles.', 'wp-voting-plugin' ); ?>
						</p>
						<table class="wpvp-voters-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Role', 'wp-voting-plugin' ); ?></th>
									<th><?php esc_html_e( 'Cast By', 'wp-voting-plugin' ); ?></th>
									<th><?php esc_html_e( 'Vote', 'wp-voting-plugin' ); ?></th>
									<?php if ( $allow_comments ) : ?>
										<th><?php esc_html_e( 'Comment', 'wp-voting-plugin' ); ?></th>
									<?php endif; ?>
									<th><?php esc_html_e( 'Date', 'wp-voting-plugin' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $matching_ballots as $mb ) :
									$bd     = $mb['ballot_data'];
									$choice = $bd['choice'] ?? '';

									// Format choice for display.
									if ( is_array( $choice ) ) {
										$choice_display = implode( ' &rsaquo; ', array_map( 'esc_html', $choice ) );
									} else {
										$choice_display = esc_html( $choice );
									}

									$cast_by = $bd['display_name'] ?? '';
									if ( ! empty( $bd['username'] ) ) {
										$cast_by .= ' (' . $bd['username'] . ')';
									}

									$vote_date = wp_date(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										strtotime( $mb['voted_at'] )
									);
								?>
								<tr>
									<td><?php echo esc_html( $bd['voting_role'] ?? '' ); ?></td>
									<td><?php echo esc_html( $cast_by ); ?></td>
									<td><?php echo $choice_display; ?></td>
									<?php if ( $allow_comments ) : ?>
										<td><?php echo esc_html( $bd['voter_comment'] ?? '' ); ?></td>
									<?php endif; ?>
									<td><?php echo esc_html( $vote_date ); ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
				endif;
		endif;
	endif;
	?>
</div>
