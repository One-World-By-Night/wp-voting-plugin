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
		<?php
		foreach ( $votes as $vote ) :
			$decoded_options = json_decode( $vote->voting_options, true );
			$options         = $decoded_options ? $decoded_options : array();
			$stages          = WPVP_Database::get_vote_stages();
			$types           = WPVP_Database::get_vote_types();
			$type_label      = $types[ $vote->voting_type ]['label'] ?? $vote->voting_type;
			$stage_label     = $stages[ $vote->voting_stage ] ?? $vote->voting_stage;
			?>
			<div class="wpvp-vote-card wpvp-vote-card--<?php echo esc_attr( $vote->voting_stage ); ?>">
				<div class="wpvp-vote-card__header">
					<h3 class="wpvp-vote-card__title">
						<?php echo esc_html( $vote->proposal_name ); ?>
					</h3>
					<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
						<?php echo esc_html( $stage_label ); ?>
					</span>
				</div>

				<div class="wpvp-vote-card__meta">
					<span class="wpvp-vote-card__type"><?php echo esc_html( $type_label ); ?></span>
					<?php if ( $vote->closing_date ) : ?>
						<span class="wpvp-vote-card__date">
							<?php
							if ( 'open' === $vote->voting_stage ) {
								printf(
									esc_html__( 'Closes: %s', 'wp-voting-plugin' ),
									esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) ) )
								);
							}
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $vote->proposal_description ) ) : ?>
					<div class="wpvp-vote-card__excerpt">
						<?php echo wp_kses_post( wp_trim_words( $vote->proposal_description, 30 ) ); ?>
					</div>
				<?php endif; ?>

				<div class="wpvp-vote-card__actions">
					<?php
					$page_ids       = get_option( 'wpvp_page_ids', array() );
					$detail_page    = ! empty( $page_ids['cast-vote'] ) ? $page_ids['cast-vote'] : 0;
					$results_page   = ! empty( $page_ids['vote-results'] ) ? $page_ids['vote-results'] : 0;
					$current_user_id = get_current_user_id();

					if ( $detail_page ) {
						$url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $detail_page ) );
					} else {
						$url = '#';
					}
					?>
					<?php if ( 'open' === $vote->voting_stage ) : ?>
						<?php if ( $current_user_id && WPVP_Permissions::can_cast_vote( $current_user_id, (int) $vote->id ) ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--primary">
								<?php esc_html_e( 'Vote Now', 'wp-voting-plugin' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( $url ); ?>" class="wpvp-btn wpvp-btn--secondary">
								<?php esc_html_e( 'View Details', 'wp-voting-plugin' ); ?>
							</a>
						<?php endif; ?>
					<?php elseif ( in_array( $vote->voting_stage, array( 'closed', 'completed', 'archived' ), true ) ) : ?>
						<?php
						if ( $results_page ) {
							$results_url = add_query_arg( 'wpvp_vote', $vote->id, get_permalink( $results_page ) );
						} else {
							$results_url = $url;
						}
						?>
						<a href="<?php echo esc_url( $results_url ); ?>" class="wpvp-btn wpvp-btn--secondary">
							<?php esc_html_e( 'View Results', 'wp-voting-plugin' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
