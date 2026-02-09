<?php
/**
 * Template: Ballot casting form.
 *
 * Renders different form layouts based on voting type.
 *
 * Variables available:
 *   $vote        object  Vote row from database.
 *   $options     array   Decoded voting_options (each has 'text' and 'description').
 *   $settings    array   Decoded vote settings.
 *   $can_vote    bool    Whether the user can currently cast/update a vote.
 *   $has_voted   bool    Whether the user has already voted.
 *   $allow_revote bool   Whether revoting is enabled.
 *   $show_form   bool    Whether to show the ballot form.
 *   $user_id     int     Current user ID.
 *
 * @package WP_Voting_Plugin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpvp-ballot" data-vote-id="<?php echo esc_attr( $vote->id ); ?>" data-vote-type="<?php echo esc_attr( $vote->voting_type ); ?>">

	<?php if ( $has_voted && ! $allow_revote ) : ?>
		<div class="wpvp-notice wpvp-notice--success">
			<p><?php esc_html_e( 'You have already voted on this proposal.', 'wp-voting-plugin' ); ?></p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $has_voted && $allow_revote ) : ?>
		<div class="wpvp-notice wpvp-notice--info">
			<p><?php esc_html_e( 'You have already voted. You may update your vote below.', 'wp-voting-plugin' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $show_form ) : ?>
		<div class="wpvp-notice wpvp-notice--warning">
			<p><?php esc_html_e( 'You do not have permission to vote on this proposal.', 'wp-voting-plugin' ); ?></p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<form class="wpvp-ballot__form" id="wpvp-ballot-form-<?php echo esc_attr( $vote->id ); ?>">
		<input type="hidden" name="vote_id" value="<?php echo esc_attr( $vote->id ); ?>">
		<input type="hidden" name="action" value="wpvp_cast_ballot">
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpvp_public' ) ); ?>">

		<?php if ( 'singleton' === $vote->voting_type || 'disciplinary' === $vote->voting_type ) : ?>
			<?php /* --- Radio button form (singleton / disciplinary) --- */ ?>
			<fieldset class="wpvp-ballot__fieldset">
				<?php if ( 'disciplinary' === $vote->voting_type ) : ?>
					<legend class="wpvp-ballot__legend">
						<?php esc_html_e( 'Select the punishment level you believe is appropriate (most severe listed first):', 'wp-voting-plugin' ); ?>
					</legend>
				<?php else : ?>
					<legend class="screen-reader-text"><?php esc_html_e( 'Select your choice', 'wp-voting-plugin' ); ?></legend>
				<?php endif; ?>

				<?php foreach ( $options as $i => $opt ) : ?>
					<label class="wpvp-ballot__option wpvp-ballot__option--radio <?php echo 'disciplinary' === $vote->voting_type ? 'wpvp-ballot__option--disciplinary' : ''; ?>">
						<input type="radio" name="ballot_choice" value="<?php echo esc_attr( $opt['text'] ); ?>"
								class="wpvp-ballot__radio" required>
						<span class="wpvp-ballot__option-text"><?php echo esc_html( $opt['text'] ); ?></span>
						<?php if ( ! empty( $opt['description'] ) ) : ?>
							<span class="wpvp-ballot__option-desc"><?php echo esc_html( $opt['description'] ); ?></span>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</fieldset>

		<?php elseif ( 'consent' === $vote->voting_type ) : ?>
			<?php /* --- Consent agenda: object button --- */ ?>
			<fieldset class="wpvp-ballot__fieldset wpvp-ballot__fieldset--consent">
				<legend class="wpvp-ballot__legend">
					<?php esc_html_e( 'This proposal passes automatically by consent unless you object.', 'wp-voting-plugin' ); ?>
				</legend>
				<p class="wpvp-ballot__instructions">
					<?php esc_html_e( 'If you have no objections, no action is needed — the proposal will pass when the review period ends. Only submit this form if you wish to object.', 'wp-voting-plugin' ); ?>
				</p>
				<input type="hidden" name="ballot_choice" value="Object">
			</fieldset>

		<?php elseif ( in_array( $vote->voting_type, array( 'rcv', 'stv', 'condorcet' ), true ) ) : ?>
			<?php /* --- Ranked / sortable form (RCV, STV, Condorcet) --- */ ?>
			<?php
			$ranked_instructions = array(
				'rcv'       => __( 'Drag to rank options from most preferred (top) to least preferred (bottom). You may leave options unranked.', 'wp-voting-plugin' ),
				'stv'       => __( 'Drag to rank candidates in order of preference. You may leave candidates unranked.', 'wp-voting-plugin' ),
				'condorcet' => __( 'Drag to rank options from most preferred (top) to least preferred (bottom). Unranked options are treated as less preferred than all ranked options.', 'wp-voting-plugin' ),
			);
			?>
			<div class="wpvp-ballot__ranked">
				<p class="wpvp-ballot__instructions">
					<?php echo esc_html( $ranked_instructions[ $vote->voting_type ] ?? $ranked_instructions['rcv'] ); ?>
				</p>

				<div class="wpvp-ballot__ranked-header">
					<span class="wpvp-ballot__ranked-label"><?php esc_html_e( 'Your Ranking', 'wp-voting-plugin' ); ?></span>
				</div>

				<ul class="wpvp-ballot__sortable" id="wpvp-sortable-<?php echo esc_attr( $vote->voting_type ); ?>"
					role="listbox" aria-label="<?php esc_attr_e( 'Rank options by dragging', 'wp-voting-plugin' ); ?>">
					<?php foreach ( $options as $i => $opt ) : ?>
						<li class="wpvp-ballot__sortable-item" data-value="<?php echo esc_attr( $opt['text'] ); ?>"
							draggable="true" role="option" tabindex="0"
							aria-label="<?php echo esc_attr( sprintf( __( 'Rank %1$d: %2$s', 'wp-voting-plugin' ), $i + 1, $opt['text'] ) ); ?>">
							<span class="wpvp-ballot__rank-number"><?php echo esc_html( $i + 1 ); ?></span>
							<span class="wpvp-ballot__drag-handle" aria-hidden="true">&#9776;</span>
							<span class="wpvp-ballot__sortable-text"><?php echo esc_html( $opt['text'] ); ?></span>
							<?php if ( ! empty( $opt['description'] ) ) : ?>
								<span class="wpvp-ballot__sortable-desc"><?php echo esc_html( $opt['description'] ); ?></span>
							<?php endif; ?>
							<button type="button" class="wpvp-ballot__rank-up" aria-label="<?php esc_attr_e( 'Move up', 'wp-voting-plugin' ); ?>">&uarr;</button>
							<button type="button" class="wpvp-ballot__rank-down" aria-label="<?php esc_attr_e( 'Move down', 'wp-voting-plugin' ); ?>">&darr;</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="wpvp-ballot__submit">
			<button type="submit" class="wpvp-btn wpvp-btn--primary wpvp-ballot__submit-btn <?php echo 'consent' === $vote->voting_type ? 'wpvp-btn--danger' : ''; ?>">
				<?php
				if ( 'consent' === $vote->voting_type ) {
					echo $has_voted
						? esc_html__( 'Update Objection', 'wp-voting-plugin' )
						: esc_html__( 'File Objection', 'wp-voting-plugin' );
				} else {
					echo $has_voted
						? esc_html__( 'Update Vote', 'wp-voting-plugin' )
						: esc_html__( 'Submit Vote', 'wp-voting-plugin' );
				}
				?>
			</button>
			<span class="wpvp-ballot__status" aria-live="polite"></span>
		</div>
	</form>
</div>
