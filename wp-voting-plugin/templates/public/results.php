<?php
/**
 * Template: Results display (used by [wpvp_results] shortcode).
 *
 * Variables available:
 *   $vote     object  Vote row from database.
 *   $results  object  Results row from database (with decoded JSON fields).
 *   $vote_id  int     Vote ID.
 *   $user_id  int     Current user ID.
 *
 */

defined( 'ABSPATH' ) || exit;
?>

<?php
$stages    = WPVP_Database::get_vote_stages();
$types     = WPVP_Database::get_vote_types();
$type_info = $types[ $vote->voting_type ] ?? array();
?>
<div class="wpvp-results-wrap">
	<h2 class="wpvp-results-wrap__title">
		<?php echo esc_html( $vote->proposal_name ); ?>
		<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
			<?php echo esc_html( $stages[ $vote->voting_stage ] ?? $vote->voting_stage ); ?>
		</span>
		<?php if ( ! empty( $results->is_live ) ) : ?>
			<span class="wpvp-badge wpvp-badge--live"><?php esc_html_e( 'Live Results', 'wp-voting-plugin' ); ?></span>
		<?php endif; ?>
	</h2>

	<div class="wpvp-results-wrap__meta">
		<span class="wpvp-results-wrap__type">
			<?php echo esc_html( $type_info['label'] ?? $vote->voting_type ); ?>
		</span>
		<?php if ( ! empty( $vote->classification ) ) : ?>
			<span class="wpvp-results-wrap__classification">
				<?php echo esc_html( $vote->classification ); ?>
			</span>
		<?php endif; ?>
		<?php if ( $vote->opening_date ) : ?>
			<span class="wpvp-results-wrap__opens">
				<?php
				printf(
					esc_html__( 'Opens: %s', 'wp-voting-plugin' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->opening_date ) ) )
				);
				?>
			</span>
		<?php endif; ?>
		<?php if ( $vote->closing_date ) : ?>
			<span class="wpvp-results-wrap__closes">
				<?php
				printf(
					esc_html__( 'Closes: %s', 'wp-voting-plugin' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->closing_date ) ) )
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $vote->proposed_by ) || ! empty( $vote->seconded_by ) ) : ?>
		<div class="wpvp-results-wrap__proposal-meta">
			<?php if ( ! empty( $vote->proposed_by ) ) : ?>
				<span><?php printf( esc_html__( 'Proposed by: %s', 'wp-voting-plugin' ), esc_html( $vote->proposed_by ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $vote->seconded_by ) ) : ?>
				<span><?php printf( esc_html__( 'Seconded by: %s', 'wp-voting-plugin' ), esc_html( $vote->seconded_by ) ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $vote->proposal_description ) ) : ?>
		<div class="wpvp-results-wrap__description">
			<?php echo wp_kses_post( do_shortcode( wpautop( $vote->proposal_description ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $vote->admin_note ) && ! empty( $vote->note_public ) ) : ?>
		<div class="wpvp-results-wrap__admin-note">
			<?php echo wp_kses_post( wpautop( $vote->admin_note ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $results->is_live ) ) : ?>
		<div class="wpvp-notice wpvp-notice--info" style="margin-bottom: 16px;">
			<p><?php esc_html_e( 'These results are calculated in real-time and will update as more votes are cast. Final results will be available after voting closes.', 'wp-voting-plugin' ); ?></p>
		</div>
	<?php endif; ?>

	<?php WPVP_Results_Display::render( $vote, $results ); ?>
</div>
