<?php
/**
 * Template: Public vote list.
 *
 * Variables available:
 *   $votes  array  List of vote objects from WPVP_Database::get_votes().
 *   $atts   array  Shortcode attributes.
 *
 * @package WP_Voting_Plugin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpvp-vote-list">
	<?php if ( empty( $votes ) ) : ?>
		<p class="wpvp-vote-list__empty">
			<?php esc_html_e( 'No votes found.', 'wp-voting-plugin' ); ?>
		</p>
	<?php else : ?>
		<div class="wpvp-vote-search">
			<input type="text" class="wpvp-vote-search__input" placeholder="<?php esc_attr_e( 'Search by title...', 'wp-voting-plugin' ); ?>">
		</div>
		<table class="wpvp-vote-table wpvp-sortable">
			<thead>
				<tr>
					<th class="wpvp-sortable__col" data-col="0"><?php esc_html_e( 'Title', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="1"><?php esc_html_e( 'Proposal Type', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="2"><?php esc_html_e( 'Votes', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="3"><?php esc_html_e( 'Start Date', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="4"><?php esc_html_e( 'End Date', 'wp-voting-plugin' ); ?></th>
					<th class="wpvp-sortable__col" data-col="5"><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Action', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				$stages      = WPVP_Database::get_vote_stages();

				foreach ( $votes as $vote ) :
					$stage_label = $stages[ $vote->voting_stage ] ?? $vote->voting_stage;

					$page_ids        = get_option( 'wpvp_page_ids', array() );
					$detail_page     = ! empty( $page_ids['cast-vote'] ) ? $page_ids['cast-vote'] : 0;
					$results_page    = ! empty( $page_ids['vote-results'] ) ? $page_ids['vote-results'] : 0;
					$current_user_id = get_current_user_id();
					$is_results_context = isset( $is_results_context ) && $is_results_context;

					if ( $detail_page ) {
						$url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $detail_page ) );
					} else {
						$url = '#';
					}

					if ( $results_page ) {
						$results_url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $results_page ) );
					} else {
						$results_url = $url;
					}
					?>
					<tr class="wpvp-vote-row wpvp-vote-row--<?php echo esc_attr( $vote->voting_stage ); ?>">
						<?php
					$type_list    = json_decode( $vote->classification, true );
					$type_display = is_array( $type_list ) && ! empty( $type_list ) ? implode( ', ', $type_list ) : '';
					$ballot_count = WPVP_Database::get_ballot_count( (int) $vote->id );
					$open_ts      = $vote->opening_date ? WPVP_Database::local_timestamp( $vote->opening_date ) : 0;
					$close_ts     = $vote->closing_date ? WPVP_Database::local_timestamp( $vote->closing_date ) : 0;
					?>
					<td class="wpvp-vote-table__title" data-sort-value="<?php echo esc_attr( strtolower( $vote->proposal_name ) ); ?>">
							<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-vote-table__link">
								<?php echo esc_html( $vote->proposal_name ); ?>
							</a>
						</td>
						<td class="wpvp-vote-table__type" data-label="<?php esc_attr_e( 'Proposal Type', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( strtolower( $type_display ) ); ?>">
							<?php echo $type_display ? esc_html( $type_display ) : '—'; ?>
						</td>
						<td class="wpvp-vote-table__votes" data-label="<?php esc_attr_e( 'Votes', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $ballot_count ); ?>">
							<?php echo esc_html( $ballot_count ); ?>
						</td>
						<td class="wpvp-vote-table__date" data-label="<?php esc_attr_e( 'Start Date', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $open_ts ); ?>">
							<?php
							if ( $vote->opening_date ) {
								echo esc_html( wp_date( $date_format . ' ' . $time_format, $open_ts ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="wpvp-vote-table__date" data-label="<?php esc_attr_e( 'End Date', 'wp-voting-plugin' ); ?>" data-sort-value="<?php echo esc_attr( $close_ts ); ?>">
							<?php
							if ( $vote->closing_date ) {
								echo esc_html( wp_date( $date_format . ' ' . $time_format, $close_ts ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="wpvp-vote-table__status" data-sort-value="<?php echo esc_attr( $vote->voting_stage ); ?>">
							<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
								<?php echo esc_html( $stage_label ); ?>
							</span>
						</td>
						<td class="wpvp-vote-table__action">
							<?php
							// Check if voting is truly available (open AND opening_date has passed).
							$is_actually_open = ( 'open' === $vote->voting_stage );
							if ( $is_actually_open && ! empty( $vote->opening_date ) && $vote->opening_date > current_time( 'mysql' ) ) {
								$is_actually_open = false;
							}

							// Direct link URL for the "open in new tab" button.
							$direct_url = in_array( $vote->voting_stage, array( 'closed', 'completed', 'archived' ), true )
								? $results_url
								: $url;
							?>
							<?php if ( $is_actually_open ) : ?>
								<?php if ( $is_results_context ) : ?>
									<a href="#" data-lightbox-url="<?php echo esc_url( $results_url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
										<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
									</a>
								<?php else :
									$can_vote   = $current_user_id && WPVP_Permissions::can_cast_vote( $current_user_id, (int) $vote->id );
									$has_voted  = $current_user_id && WPVP_Database::user_has_voted( $current_user_id, (int) $vote->id );
									$is_consent = 'consent' === $vote->voting_type;
								?>
									<?php if ( $is_consent && $can_vote && ! $has_voted ) : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--danger wpvp-btn--small">
											<?php esc_html_e( 'Object', 'wp-voting-plugin' ); ?>
										</a>
									<?php elseif ( $is_consent && $has_voted ) : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--success wpvp-btn--small">
											<?php esc_html_e( 'Objected', 'wp-voting-plugin' ); ?>
										</a>
									<?php elseif ( $can_vote && $has_voted ) : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--primary wpvp-btn--small">
											<?php esc_html_e( 'Update', 'wp-voting-plugin' ); ?>
										</a>
									<?php elseif ( $can_vote ) : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--primary wpvp-btn--small">
											<?php esc_html_e( 'Vote', 'wp-voting-plugin' ); ?>
										</a>
									<?php elseif ( $has_voted ) : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--success wpvp-btn--small">
											<?php esc_html_e( 'Voted', 'wp-voting-plugin' ); ?>
										</a>
									<?php else : ?>
										<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
											<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
										</a>
									<?php endif; ?>
								<?php endif; ?>
							<?php elseif ( 'open' === $vote->voting_stage && ! empty( $vote->opening_date ) ) : ?>
								<span class="wpvp-vote-table__opens-label">
									<?php
									printf(
										esc_html__( 'Opens %s', 'wp-voting-plugin' ),
										esc_html( wp_date( $date_format, WPVP_Database::local_timestamp( $vote->opening_date ) ) )
									);
									?>
								</span>
							<?php elseif ( in_array( $vote->voting_stage, array( 'closed', 'completed', 'archived' ), true ) ) : ?>
								<a href="#" data-lightbox-url="<?php echo esc_url( $results_url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
									<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $direct_url && '#' !== $direct_url ) : ?>
								<a href="<?php echo esc_url( $direct_url ); ?>" target="_blank" rel="noopener"
								   class="wpvp-vote-table__new-tab" title="<?php esc_attr_e( 'Open in new tab', 'wp-voting-plugin' ); ?>">&#8599;</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
