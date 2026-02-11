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
		<table class="wpvp-vote-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Start Date', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'End Date', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></th>
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
						<td class="wpvp-vote-table__title">
							<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-vote-table__link">
								<?php echo esc_html( $vote->proposal_name ); ?>
							</a>
						</td>
						<td class="wpvp-vote-table__date">
							<?php
							if ( $vote->opening_date ) {
								echo esc_html( wp_date( $date_format . ' ' . $time_format, strtotime( $vote->opening_date ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="wpvp-vote-table__date">
							<?php
							if ( $vote->closing_date ) {
								echo esc_html( wp_date( $date_format . ' ' . $time_format, strtotime( $vote->closing_date ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td class="wpvp-vote-table__status">
							<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
								<?php echo esc_html( $stage_label ); ?>
							</span>
						</td>
						<td class="wpvp-vote-table__action">
							<?php if ( 'open' === $vote->voting_stage ) : ?>
								<?php if ( $is_results_context ) : ?>
									<a href="#" data-lightbox-url="<?php echo esc_url( $results_url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
										<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
									</a>
								<?php elseif ( $current_user_id && WPVP_Permissions::can_cast_vote( $current_user_id, (int) $vote->id ) ) : ?>
									<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--primary wpvp-btn--small">
										<?php esc_html_e( 'Vote', 'wp-voting-plugin' ); ?>
									</a>
								<?php else : ?>
									<a href="#" data-lightbox-url="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
										<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
									</a>
								<?php endif; ?>
							<?php elseif ( in_array( $vote->voting_stage, array( 'closed', 'completed', 'archived' ), true ) ) : ?>
								<a href="#" data-lightbox-url="<?php echo esc_url( $results_url ); ?>" class="wpvp-btn wpvp-btn--secondary wpvp-btn--small">
									<?php esc_html_e( 'View', 'wp-voting-plugin' ); ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
