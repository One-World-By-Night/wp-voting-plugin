<?php
/**
 * Template: Ballot casting form.
 *
 * Renders different form layouts based on voting type.
 *
 * Variables available:
 *   $vote            object  Vote row from database.
 *   $options         array   Decoded voting_options (each has 'text' and 'description').
 *   $settings        array   Decoded vote settings.
 *   $can_vote        bool    Whether the user can currently cast/update a vote.
 *   $has_voted       bool    Whether the user has already voted.
 *   $allow_revote    bool    Whether revoting is enabled.
 *   $show_form       bool    Whether to show the ballot form.
 *   $user_id         int     Current user ID.
 *   $previous_ballot mixed   User's previous ballot data (string for singleton, array for ranked, null if not voted).
 *
 * @package WP_Voting_Plugin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpvp-ballot" data-vote-id="<?php echo esc_attr( $vote->id ); ?>" data-vote-type="<?php echo esc_attr( $vote->voting_type ); ?>" data-allow-revote="<?php echo $allow_revote ? '1' : '0'; ?>">

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

		<!-- Role Selection (shown only if multiple eligible roles) -->
		<div class="wpvp-ballot__role-selection" style="display: none; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #0073aa;">
			<label for="wpvp-voting-role-<?php echo esc_attr( $vote->id ); ?>" class="wpvp-ballot__role-label" style="font-weight: 600; display: block; margin-bottom: 8px;">
				<?php esc_html_e( 'You have multiple eligible roles. Select which role you are voting as:', 'wp-voting-plugin' ); ?>
			</label>
			<select name="voting_role" id="wpvp-voting-role-<?php echo esc_attr( $vote->id ); ?>"
					class="wpvp-ballot__role-select" style="width: 100%; padding: 8px; font-size: 14px;">
				<option value=""><?php esc_html_e( '-- Select a role --', 'wp-voting-plugin' ); ?></option>
			</select>
		</div>

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
					<?php
					// Check if this option was previously selected.
					$is_checked = ( $previous_ballot && $previous_ballot === $opt['text'] );
					?>
					<label class="wpvp-ballot__option wpvp-ballot__option--radio <?php echo 'disciplinary' === $vote->voting_type ? 'wpvp-ballot__option--disciplinary' : ''; ?> <?php echo $is_checked ? 'wpvp-ballot__option--selected' : ''; ?>">
						<input type="radio" name="ballot_choice" value="<?php echo esc_attr( $opt['text'] ); ?>"
								class="wpvp-ballot__radio" required <?php checked( $is_checked ); ?>>
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

				<?php
				// Reorder options based on previous ballot if available.
				$ordered_options = array();
				if ( $previous_ballot && is_array( $previous_ballot ) ) {
					// First, add previously ranked items in their ranked order.
					foreach ( $previous_ballot as $ranked_text ) {
						foreach ( $options as $opt ) {
							if ( $opt['text'] === $ranked_text ) {
								$ordered_options[] = $opt;
								break;
							}
						}
					}
					// Then, add any unranked items at the end.
					foreach ( $options as $opt ) {
						if ( ! in_array( $opt['text'], $previous_ballot, true ) ) {
							$ordered_options[] = $opt;
						}
					}
				} else {
					// No previous ballot, use default order.
					$ordered_options = $options;
				}

				foreach ( $ordered_options as $i => $opt ) :
					?>
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

		<?php if ( get_option( 'wpvp_enable_email_notifications', false ) && ( ! isset( $settings['notify_voter_confirmation'] ) || ! empty( $settings['notify_voter_confirmation'] ) ) ) : ?>
			<?php
			// Check if user has already opted in for this vote.
			$previously_opted_in = get_user_meta( $user_id, 'wpvp_vote_' . $vote->id . '_notify', true );
			$current_user_obj    = wp_get_current_user();
			$default_email       = $current_user_obj->user_email;
			$saved_emails        = get_user_meta( $user_id, 'wpvp_vote_' . $vote->id . '_notify_emails', true );
			$email_value         = $saved_emails ? $saved_emails : $default_email;
			?>
			<div class="wpvp-ballot__notification-opt-in" style="margin: 16px 0;">
				<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
					<input type="checkbox" name="send_confirmation" value="1"
						id="wpvp-send-confirmation-<?php echo esc_attr( $vote->id ); ?>"
						<?php checked( $previously_opted_in ); ?> style="margin: 0;">
					<span><?php esc_html_e( 'Email me a confirmation of my vote', 'wp-voting-plugin' ); ?></span>
				</label>
				<div class="wpvp-ballot__email-field" id="wpvp-email-field-<?php echo esc_attr( $vote->id ); ?>"
					style="margin-top: 8px; padding-left: 28px; <?php echo $previously_opted_in ? '' : 'display: none;'; ?>">
					<label for="wpvp-confirmation-emails-<?php echo esc_attr( $vote->id ); ?>" style="display: block; margin-bottom: 4px; font-size: 13px; color: #646970;">
						<?php esc_html_e( 'Send to (comma-separated for multiple):', 'wp-voting-plugin' ); ?>
					</label>
					<input type="text" name="confirmation_emails"
						id="wpvp-confirmation-emails-<?php echo esc_attr( $vote->id ); ?>"
						value="<?php echo esc_attr( $email_value ); ?>"
						style="width: 100%; padding: 6px 8px; font-size: 14px;"
						placeholder="<?php esc_attr_e( 'you@example.com, other@example.com', 'wp-voting-plugin' ); ?>">
				</div>
			</div>
			<script>
			(function() {
				var cb = document.getElementById('wpvp-send-confirmation-<?php echo esc_js( $vote->id ); ?>');
				var field = document.getElementById('wpvp-email-field-<?php echo esc_js( $vote->id ); ?>');
				if (cb && field) {
					cb.addEventListener('change', function() {
						field.style.display = this.checked ? '' : 'none';
					});
				}
			})();
			</script>
		<?php endif; ?>

		<?php if ( ! empty( $settings['allow_voter_comments'] ) ) : ?>
			<div class="wpvp-ballot__comment" style="margin: 16px 0;">
				<label for="wpvp-voter-comment" style="display: block; margin-bottom: 4px; font-weight: 600;">
					<?php esc_html_e( 'Comment (optional)', 'wp-voting-plugin' ); ?>
				</label>
				<textarea id="wpvp-voter-comment" name="voter_comment" maxlength="5000" rows="5"
					style="width: 100%; resize: vertical;"
					placeholder="<?php esc_attr_e( 'Add an optional comment or rationale for your vote...', 'wp-voting-plugin' ); ?>"
				><?php echo isset( $previous_comment ) ? esc_textarea( $previous_comment ) : ''; ?></textarea>
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

	<script>
	(function() {
		const voteId = <?php echo absint( $vote->id ); ?>;
		const form = document.getElementById('wpvp-ballot-form-' + voteId);
		if (!form) return;

		const roleSelection = form.querySelector('.wpvp-ballot__role-selection');
		const roleSelect = form.querySelector('.wpvp-ballot__role-select');

		// Fetch eligible roles on page load
		const formData = new FormData();
		formData.append('action', 'wpvp_get_eligible_roles');
		formData.append('vote_id', voteId);
		formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'wpvp_public' ) ); ?>');

		fetch(wpvp_public.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.requires_selection) {
				// User has multiple roles - show selection and make required
				roleSelection.style.display = 'block';
				roleSelect.setAttribute('required', 'required');

				// Populate dropdown
				data.data.eligible_roles.forEach(role => {
					const option = document.createElement('option');
					option.value = role;
					option.textContent = role;
					roleSelect.appendChild(option);
				});
			} else if (data.success && data.data.eligible_roles && data.data.eligible_roles.length === 1) {
				// Single role - add it as hidden input
				const hiddenRole = document.createElement('input');
				hiddenRole.type = 'hidden';
				hiddenRole.name = 'voting_role';
				hiddenRole.value = data.data.eligible_roles[0];
				form.appendChild(hiddenRole);
			}
		})
		.catch(err => console.error('Failed to fetch eligible roles:', err));
	})();
	</script>
</div>
