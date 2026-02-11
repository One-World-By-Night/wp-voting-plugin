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
 * @package WP_Voting_Plugin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpvp-results-wrap">
	<h2 class="wpvp-results-wrap__title">
		<?php echo esc_html( $vote->proposal_name ); ?>
		<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $vote->voting_stage ); ?>">
			<?php
			$stages = WPVP_Database::get_vote_stages();
			echo esc_html( $stages[ $vote->voting_stage ] ?? $vote->voting_stage );
			?>
		</span>
		<?php if ( ! empty( $results->is_live ) ) : ?>
			<span class="wpvp-badge wpvp-badge--live"><?php esc_html_e( 'Live Results', 'wp-voting-plugin' ); ?></span>
		<?php endif; ?>
	</h2>

	<?php if ( ! empty( $results->is_live ) ) : ?>
		<div class="wpvp-notice wpvp-notice--info" style="margin-bottom: 16px;">
			<p><?php esc_html_e( 'These results are calculated in real-time and will update as more votes are cast. Final results will be available after voting closes.', 'wp-voting-plugin' ); ?></p>
		</div>
	<?php endif; ?>

	<?php WPVP_Results_Display::render( $vote, $results ); ?>
</div>
