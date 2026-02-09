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
</div>
