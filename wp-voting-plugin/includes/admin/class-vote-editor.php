<?php
/**
 * Create / Edit vote admin page.
 *
 * Handles form rendering and POST processing with full sanitisation,
 * nonce verification, and capability checks.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Vote_Editor {

	/** @var object|null */
	private $vote = null;

	/** @var int */
	private $vote_id = 0;

	/** @var array */
	private $errors = array();

	/** @var string */
	private $success = '';

	public function render(): void {
		if ( ! WPVP_Permissions::can_create_vote() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-voting-plugin' ) );
		}

		$this->vote_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		// Process form submission first.
		if ( isset( $_POST['wpvp_save_vote'] ) ) {
			$this->process_form();
		}

		// Load vote data (or reload after save).
		if ( $this->vote_id ) {
			$this->vote = WPVP_Database::get_vote( $this->vote_id );
			if ( ! $this->vote ) {
				wp_die( esc_html__( 'Vote not found.', 'wp-voting-plugin' ) );
			}
		}

		$this->render_page();
	}

	/*
	------------------------------------------------------------------
	 *  Form processing.
	 * ----------------------------------------------------------------*/

	private function process_form(): void {
		// Nonce check.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpvp_vote_nonce'] ?? '' ) ), 'wpvp_save_vote' ) ) {
			$this->errors[] = __( 'Security check failed. Please try again.', 'wp-voting-plugin' );
			return;
		}

		// Capability check.
		if ( ! WPVP_Permissions::can_create_vote() ) {
			$this->errors[] = __( 'Permission denied.', 'wp-voting-plugin' );
			return;
		}

		// Collect and sanitise data.
		$data = $this->sanitize_form_data();

		// Validate.
		$validation = $this->validate( $data );
		if ( ! $validation['valid'] ) {
			$this->errors = $validation['errors'];
			return;
		}

		// Save.
		if ( $this->vote_id ) {
			$old_vote  = WPVP_Database::get_vote( $this->vote_id );
			$old_stage = $old_vote ? $old_vote->voting_stage : '';

			$result = WPVP_Database::update_vote( $this->vote_id, $data );
			if ( $result ) {
				$this->success = __( 'Vote updated.', 'wp-voting-plugin' );

				// Fire stage change action if stage changed.
				if ( $old_stage && $old_stage !== $data['voting_stage'] ) {
					do_action( 'wpvp_vote_stage_changed', $this->vote_id, $data['voting_stage'], $old_stage );
				}
			} else {
				$this->errors[] = __( 'Failed to update vote.', 'wp-voting-plugin' );
			}
		} else {
			$new_id = WPVP_Database::save_vote( $data );
			if ( $new_id ) {
				// PRG: redirect to edit page with the new ID.
				wp_safe_redirect( admin_url( 'admin.php?page=wpvp-vote-edit&id=' . $new_id . '&wpvp_saved=1' ) );
				exit;
			} else {
				$this->errors[] = __( 'Failed to create vote.', 'wp-voting-plugin' );
			}
		}
	}

	/**
	 * Collect and sanitise all form fields.
	 */
	private function sanitize_form_data(): array {
		// Sanitise voting options array.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below per-field.
		$raw_options = isset( $_POST['voting_options'] ) ? (array) wp_unslash( $_POST['voting_options'] ) : array();
		$options     = array();
		foreach ( $raw_options as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			$text = sanitize_text_field( $opt['text'] ?? '' );
			if ( '' === $text ) {
				continue;
			}
			$options[] = array(
				'text'        => $text,
				'description' => sanitize_textarea_field( $opt['description'] ?? '' ),
			);
		}

		// Sanitise settings.
		$allowed_settings = array( 'allow_revote', 'show_results_before_closing', 'anonymous_voting', 'allow_voter_comments' );
		$settings         = array();
		$raw_settings     = isset( $_POST['settings'] ) ? (array) wp_unslash( $_POST['settings'] ) : array();
		foreach ( $allowed_settings as $key ) {
			$settings[ $key ] = ! empty( $raw_settings[ $key ] );
		}

		// Sanitise allowed roles.
		$raw_roles     = isset( $_POST['allowed_roles'] ) ? (array) wp_unslash( $_POST['allowed_roles'] ) : array();
		$allowed_roles = array_values( array_filter( array_map( 'sanitize_text_field', $raw_roles ) ) );

		// Sanitise voting roles.
		$raw_voting_roles = isset( $_POST['voting_roles'] ) ? (array) wp_unslash( $_POST['voting_roles'] ) : array();
		$voting_roles     = array_values( array_filter( array_map( 'sanitize_text_field', $raw_voting_roles ) ) );

		return array(
			'proposal_name'        => sanitize_text_field( wp_unslash( $_POST['proposal_name'] ?? '' ) ),
			'proposal_description' => wp_kses_post( wp_unslash( $_POST['proposal_description'] ?? '' ) ),
			'voting_type'          => sanitize_key( wp_unslash( $_POST['voting_type'] ?? 'singleton' ) ),
			'voting_options'       => $options,
			'number_of_winners'    => max( 1, absint( $_POST['number_of_winners'] ?? 1 ) ),
			'allowed_roles'        => $allowed_roles,
			'visibility'           => sanitize_key( wp_unslash( $_POST['visibility'] ?? 'private' ) ),
			'voting_roles'         => $voting_roles,
			'voting_eligibility'   => sanitize_key( wp_unslash( $_POST['voting_eligibility'] ?? 'private' ) ),
			'voting_stage'         => sanitize_key( wp_unslash( $_POST['voting_stage'] ?? 'draft' ) ),
			'opening_date'         => sanitize_text_field( wp_unslash( $_POST['opening_date'] ?? '' ) ),
			'closing_date'         => sanitize_text_field( wp_unslash( $_POST['closing_date'] ?? '' ) ),
			'settings'             => $settings,
			'classification'       => sanitize_text_field( wp_unslash( $_POST['classification'] ?? '' ) ),
			'proposed_by'          => sanitize_text_field( wp_unslash( $_POST['proposed_by'] ?? '' ) ),
			'seconded_by'          => sanitize_text_field( wp_unslash( $_POST['seconded_by'] ?? '' ) ),
			'objection_by'         => sanitize_text_field( wp_unslash( $_POST['objection_by'] ?? '' ) ),
			'majority_threshold'   => sanitize_key( wp_unslash( $_POST['majority_threshold'] ?? 'simple' ) ),
		);
	}

	/**
	 * Validate form data.
	 *
	 * @return array { valid: bool, errors: string[] }
	 */
	private function validate( array $data ): array {
		$errors = array();

		if ( empty( $data['proposal_name'] ) ) {
			$errors[] = __( 'Title is required.', 'wp-voting-plugin' );
		}

		$valid_types = array_keys( WPVP_Database::get_vote_types() );
		if ( ! in_array( $data['voting_type'], $valid_types, true ) ) {
			$errors[] = __( 'Invalid voting type.', 'wp-voting-plugin' );
		}

		$valid_stages = array_keys( WPVP_Database::get_vote_stages() );
		if ( ! in_array( $data['voting_stage'], $valid_stages, true ) ) {
			$errors[] = __( 'Invalid voting stage.', 'wp-voting-plugin' );
		}

		$valid_vis = array_keys( WPVP_Database::get_visibility_options() );
		if ( ! in_array( $data['visibility'], $valid_vis, true ) ) {
			$errors[] = __( 'Invalid visibility.', 'wp-voting-plugin' );
		}

		if ( count( $data['voting_options'] ) < 2 && ! in_array( $data['voting_type'], array( 'disciplinary', 'consent' ), true ) ) {
			$errors[] = __( 'At least two options are required.', 'wp-voting-plugin' );
		}

		if ( ! empty( $data['opening_date'] ) && ! empty( $data['closing_date'] ) ) {
			$opening_time = strtotime( $data['opening_date'] );
			$closing_time = strtotime( $data['closing_date'] );

			// For consent votes, allow opening and closing times to be the same (instant pass).
			if ( 'consent' === $data['voting_type'] ) {
				if ( $closing_time < $opening_time ) {
					$errors[] = __( 'Closing date cannot be before opening date.', 'wp-voting-plugin' );
				}
			} else {
				if ( $closing_time <= $opening_time ) {
					$errors[] = __( 'Closing date must be after opening date.', 'wp-voting-plugin' );
				}
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/*
	------------------------------------------------------------------
	 *  Page renderer.
	 * ----------------------------------------------------------------*/

	private function render_page(): void {
		$is_edit    = (bool) $this->vote;
		$vote_types = WPVP_Database::get_vote_types();
		$stages     = WPVP_Database::get_vote_stages();
		$vis_opts   = WPVP_Database::get_visibility_options();
		$voting_opts = WPVP_Database::get_voting_eligibility_options();

		// Calculate default dates for new votes: 7 days from now (opening), 14 days from now (closing), both at midnight.
		$default_opening = '';
		$default_closing = '';
		if ( ! $is_edit ) {
			$opening_timestamp = strtotime( '+7 days midnight' );
			$closing_timestamp = strtotime( '+14 days midnight' );
			$default_opening   = date( 'Y-m-d\TH:i', $opening_timestamp );
			$default_closing   = date( 'Y-m-d\TH:i', $closing_timestamp );
		}

		// Decode existing data.
		if ( $is_edit ) {
			$decoded_options  = json_decode( $this->vote->voting_options, true );
			$options          = $decoded_options ? $decoded_options : array();
			$decoded_settings = json_decode( $this->vote->settings, true );
			$settings         = $decoded_settings ? $decoded_settings : array();
			$decoded_roles    = json_decode( $this->vote->allowed_roles, true );
			$roles            = $decoded_roles ? $decoded_roles : array();
			$decoded_voting_roles = json_decode( $this->vote->voting_roles, true );
			$voting_roles     = $decoded_voting_roles ? $decoded_voting_roles : array();
		} else {
			$options      = array();
			$settings     = array();
			$roles        = array();
			$voting_roles = array();
		}

		// Check for redirect notice.
		if ( isset( $_GET['wpvp_saved'] ) ) {
			$this->success = __( 'Vote created.', 'wp-voting-plugin' );
		}

		?>
		<div class="wrap">
			<h1><?php echo $is_edit ? esc_html__( 'Edit Vote', 'wp-voting-plugin' ) : esc_html__( 'Add New Vote', 'wp-voting-plugin' ); ?></h1>

			<?php $this->render_notices_html(); ?>

			<form method="post" id="wpvp-vote-form">
				<?php wp_nonce_field( 'wpvp_save_vote', 'wpvp_vote_nonce' ); ?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-1">

						<!-- Main content -->
						<div id="post-body-content" style="max-width:900px;">
							<!-- Title -->
							<div id="titlediv">
								<input type="text" name="proposal_name" id="title" size="30"
										value="<?php echo esc_attr( $is_edit ? $this->vote->proposal_name : '' ); ?>"
										placeholder="<?php esc_attr_e( 'Enter vote title', 'wp-voting-plugin' ); ?>"
										autocomplete="off" required>
							</div>

							<!-- Description -->
							<div class="wpvp-editor-wrap" style="margin-top:20px;">
								<h3><?php esc_html_e( 'Description', 'wp-voting-plugin' ); ?></h3>
								<?php
								wp_editor(
									$is_edit ? $this->vote->proposal_description : '',
									'proposal_description',
									array(
										'textarea_rows' => 10,
										'media_buttons' => true,
										'teeny'         => false,
									)
								);
								?>
							</div>

							<!-- Proposal Metadata Box -->
							<div class="postbox" style="margin-top:20px;">
								<div class="postbox-header"><h2><?php esc_html_e( 'Proposal Metadata', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<?php $classifications = WPVP_Database::get_classifications(); ?>
									<p>
										<label for="classification"><?php esc_html_e( 'Classification:', 'wp-voting-plugin' ); ?></label>
										<select name="classification" id="classification" style="width:100%;">
											<option value=""><?php esc_html_e( '-- None --', 'wp-voting-plugin' ); ?></option>
											<?php foreach ( $classifications as $class ) : ?>
												<option value="<?php echo esc_attr( $class->classification_name ); ?>"
													<?php selected( $is_edit ? $this->vote->classification : '', $class->classification_name ); ?>>
													<?php echo esc_html( $class->classification_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</p>
									<p>
										<label for="proposed_by"><?php esc_html_e( 'Proposed By:', 'wp-voting-plugin' ); ?></label>
										<input type="text" name="proposed_by" id="proposed_by" class="regular-text"
												value="<?php echo esc_attr( $is_edit && isset( $this->vote->proposed_by ) ? $this->vote->proposed_by : '' ); ?>"
												placeholder="<?php esc_attr_e( 'Person who proposed this vote', 'wp-voting-plugin' ); ?>">
									</p>
									<p>
										<label for="seconded_by"><?php esc_html_e( 'Seconded By:', 'wp-voting-plugin' ); ?></label>
										<input type="text" name="seconded_by" id="seconded_by" class="regular-text"
												value="<?php echo esc_attr( $is_edit && isset( $this->vote->seconded_by ) ? $this->vote->seconded_by : '' ); ?>"
												placeholder="<?php esc_attr_e( 'Person who seconded this vote', 'wp-voting-plugin' ); ?>">
									</p>
									<p>
										<label for="objection_by"><?php esc_html_e( 'Objection By:', 'wp-voting-plugin' ); ?></label>
										<input type="text" name="objection_by" id="objection_by" class="regular-text"
												value="<?php echo esc_attr( $is_edit && isset( $this->vote->objection_by ) ? $this->vote->objection_by : '' ); ?>"
												placeholder="<?php esc_attr_e( 'Person who objected (consent agenda)', 'wp-voting-plugin' ); ?>">
										<span class="description"><?php esc_html_e( 'For consent agenda votes that were objected to', 'wp-voting-plugin' ); ?></span>
									</p>
								</div>
							</div>

							<!-- Voting Type -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Voting Type', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<select name="voting_type" id="wpvp-voting-type">
										<?php foreach ( $vote_types as $key => $type ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"
												<?php selected( $is_edit ? $this->vote->voting_type : 'singleton', $key ); ?>
												data-description="<?php echo esc_attr( $type['description'] ); ?>">
												<?php echo esc_html( $type['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description" id="wpvp-type-description"></p>
								</div>
							</div>

							<!-- Voting Options -->
							<div class="postbox" id="wpvp-options-box">
								<div class="postbox-header"><h2><?php esc_html_e( 'Voting Options', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<div id="wpvp-options-list">
										<?php
										if ( ! empty( $options ) ) {
											foreach ( $options as $i => $opt ) {
												$this->render_option_row( $i, $opt );
											}
										} else {
											$this->render_option_row( 0 );
											$this->render_option_row( 1 );
										}
										?>
									</div>
									<button type="button" class="button" id="wpvp-add-option">
										<?php esc_html_e( '+ Add Option', 'wp-voting-plugin' ); ?>
									</button>

									<div id="wpvp-num-winners" style="margin-top:12px; <?php echo ( $is_edit && 'stv' !== $this->vote->voting_type ) ? 'display:none;' : ''; ?>">
										<label for="number_of_winners"><?php esc_html_e( 'Number of Winners', 'wp-voting-plugin' ); ?></label>
										<input type="number" name="number_of_winners" id="number_of_winners" min="1" value="<?php echo esc_attr( $is_edit ? $this->vote->number_of_winners : 1 ); ?>">
									</div>
								</div>
							</div>

							<!-- Status & Schedule Box -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Status & Schedule', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<p>
										<label for="voting_stage"><?php esc_html_e( 'Status:', 'wp-voting-plugin' ); ?></label>
										<select name="voting_stage" id="voting_stage" style="width:100%;">
											<?php foreach ( $stages as $key => $label ) : ?>
												<?php
												if ( 'completed' === $key ) {
													continue;} // Completed is set by processing, not manually.
												?>
												<option value="<?php echo esc_attr( $key ); ?>"
													<?php selected( $is_edit ? $this->vote->voting_stage : 'draft', $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</p>
									<p>
										<label for="opening_date"><?php esc_html_e( 'Opens:', 'wp-voting-plugin' ); ?></label><br>
										<input type="datetime-local" name="opening_date" id="opening_date"
												value="<?php echo esc_attr( $is_edit && $this->vote->opening_date ? date( 'Y-m-d\TH:i', strtotime( $this->vote->opening_date ) ) : $default_opening ); ?>">
									</p>
									<p>
										<label for="closing_date"><?php esc_html_e( 'Closes:', 'wp-voting-plugin' ); ?></label><br>
										<input type="datetime-local" name="closing_date" id="closing_date"
												value="<?php echo esc_attr( $is_edit && $this->vote->closing_date ? date( 'Y-m-d\TH:i', strtotime( $this->vote->closing_date ) ) : $default_closing ); ?>">
									</p>
								</div>
							</div>

							<!-- Access Control Box -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Access Control', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<p>
										<label for="visibility"><?php esc_html_e( 'Visibility:', 'wp-voting-plugin' ); ?></label>
										<select name="visibility" id="visibility" style="width:100%;">
											<?php foreach ( $vis_opts as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"
													<?php selected( $is_edit ? $this->vote->visibility : 'private', $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</p>
									<div id="wpvp-roles-section" style="<?php echo ( $is_edit && 'restricted' !== $this->vote->visibility ) ? 'display:none;' : ''; ?>">
										<?php $role_templates = WPVP_Database::get_role_templates(); ?>
										<?php if ( ! empty( $role_templates ) ) : ?>
											<div class="wpvp-template-loader" style="margin-bottom: 8px;">
												<label><?php esc_html_e( 'Load from template:', 'wp-voting-plugin' ); ?></label>
												<select class="wpvp-template-select" data-target="allowed_roles" style="min-width: 200px;">
													<option value=""><?php esc_html_e( '-- Select template --', 'wp-voting-plugin' ); ?></option>
													<?php foreach ( $role_templates as $tmpl ) : ?>
														<option value="<?php echo esc_attr( $tmpl->id ); ?>">
															<?php echo esc_html( $tmpl->template_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<button type="button" class="button wpvp-apply-template"><?php esc_html_e( 'Apply', 'wp-voting-plugin' ); ?></button>
											</div>
										<?php endif; ?>
										<label><?php esc_html_e( 'Allowed Roles / Groups:', 'wp-voting-plugin' ); ?></label>
										<select name="allowed_roles[]" multiple class="wpvp-select2-roles" style="width:100%;">
											<?php foreach ( $roles as $role ) : ?>
												<option value="<?php echo esc_attr( $role ); ?>" selected>
													<?php echo esc_html( $role ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Enter role paths, wildcards, or WP role slugs. Use * for one segment, ** for any depth.', 'wp-voting-plugin' ); ?>
											<br>
											<code>Chronicle/*/CM</code> &nbsp; <code>Players/**</code> &nbsp; <code>editor</code>
										</p>
									</div>
								</div>
							</div>

							<!-- Voting Eligibility Box -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Who Can Vote', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<p>
										<label for="voting_eligibility"><?php esc_html_e( 'Voting Eligibility:', 'wp-voting-plugin' ); ?></label>
										<select name="voting_eligibility" id="voting_eligibility" style="width:100%;">
											<?php foreach ( $voting_opts as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"
													<?php selected( $is_edit ? $this->vote->voting_eligibility : 'private', $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</p>
									<div id="wpvp-voting-roles-section" style="<?php echo ( $is_edit && 'restricted' !== $this->vote->voting_eligibility ) ? 'display:none;' : ''; ?>">
										<?php if ( ! empty( $role_templates ) ) : ?>
											<div class="wpvp-template-loader" style="margin-bottom: 8px;">
												<label><?php esc_html_e( 'Load from template:', 'wp-voting-plugin' ); ?></label>
												<select class="wpvp-template-select" data-target="voting_roles" style="min-width: 200px;">
													<option value=""><?php esc_html_e( '-- Select template --', 'wp-voting-plugin' ); ?></option>
													<?php foreach ( $role_templates as $tmpl ) : ?>
														<option value="<?php echo esc_attr( $tmpl->id ); ?>">
															<?php echo esc_html( $tmpl->template_name ); ?>
														</option>
													<?php endforeach; ?>
												</select>
												<button type="button" class="button wpvp-apply-template"><?php esc_html_e( 'Apply', 'wp-voting-plugin' ); ?></button>
											</div>
										<?php endif; ?>
										<label><?php esc_html_e( 'Who Can Vote (Roles / Groups):', 'wp-voting-plugin' ); ?></label>
										<select name="voting_roles[]" multiple class="wpvp-select2-voting-roles" style="width:100%;">
											<?php foreach ( $voting_roles as $role ) : ?>
												<option value="<?php echo esc_attr( $role ); ?>" selected>
													<?php echo esc_html( $role ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Enter role paths, wildcards, or WP role slugs. Use * for one segment, ** for any depth.', 'wp-voting-plugin' ); ?>
											<br>
											<code>Chronicle/*/CM</code> &nbsp; <code>Players/**</code> &nbsp; <code>administrator</code>
										</p>
									</div>
								</div>
							</div>

							<!-- Vote Settings -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Vote Settings', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<div id="wpvp-majority-threshold-section">
										<?php $threshold_opts = WPVP_Database::get_majority_threshold_options(); ?>
										<p>
											<label for="majority_threshold"><?php esc_html_e( 'Majority Threshold:', 'wp-voting-plugin' ); ?></label>
											<select name="majority_threshold" id="majority_threshold" style="width:100%;">
												<?php foreach ( $threshold_opts as $key => $label ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>"
														<?php selected( $is_edit && isset( $this->vote->majority_threshold ) ? $this->vote->majority_threshold : 'simple', $key ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<span class="description"><?php esc_html_e( 'Required threshold for passing votes (affects FPTP and other algorithms)', 'wp-voting-plugin' ); ?></span>
										</p>
										<hr style="margin: 15px 0;">
									</div>
									<label style="display:block; margin-bottom:8px;">
										<input type="checkbox" name="settings[allow_revote]" value="1"
											<?php checked( ! empty( $settings['allow_revote'] ) ); ?>>
										<?php esc_html_e( 'Allow voters to change their vote', 'wp-voting-plugin' ); ?>
									</label>
									<label style="display:block; margin-bottom:8px;">
										<input type="checkbox" name="settings[show_results_before_closing]" value="1"
											<?php checked( ! empty( $settings['show_results_before_closing'] ) ); ?>>
										<?php esc_html_e( 'Show results while voting is open', 'wp-voting-plugin' ); ?>
									</label>
									<label style="display:block; margin-bottom:8px;">
										<input type="checkbox" name="settings[anonymous_voting]" value="1"
											<?php checked( ! empty( $settings['anonymous_voting'] ) ); ?>>
										<?php esc_html_e( 'Anonymous voting (hide voter names in results)', 'wp-voting-plugin' ); ?>
									</label>
									<label style="display:block;">
										<input type="checkbox" name="settings[allow_voter_comments]" value="1"
											<?php checked( ! empty( $settings['allow_voter_comments'] ) ); ?>>
										<?php esc_html_e( 'Allow voters to add an optional comment with their vote', 'wp-voting-plugin' ); ?>
									</label>
								</div>
							</div>

							<!-- Publish Actions -->
							<div class="postbox">
								<div class="postbox-header"><h2><?php esc_html_e( 'Publish', 'wp-voting-plugin' ); ?></h2></div>
								<div class="inside">
									<div id="major-publishing-actions">
										<?php if ( $is_edit ) : ?>
											<div id="delete-action">
												<a class="submitdelete deletion"
													href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpvp-votes&action=delete&vote[]=' . $this->vote->id ), 'bulk-votes' ) ); ?>"
													onclick="return confirm(wpvp.i18n.confirm_delete);">
													<?php esc_html_e( 'Delete', 'wp-voting-plugin' ); ?>
												</a>
											</div>
										<?php endif; ?>
										<div id="publishing-action">
											<input type="submit" name="wpvp_save_vote"
													class="button button-primary button-large"
													value="<?php echo $is_edit ? esc_attr__( 'Update', 'wp-voting-plugin' ) : esc_attr__( 'Create Vote', 'wp-voting-plugin' ); ?>">
										</div>
										<div class="clear"></div>
									</div>
								</div>
							</div>
						</div>

						<!-- Sidebar (now empty, but keeping structure for compatibility) -->
						<div id="postbox-container-1" class="postbox-container" style="display:none;">
						</div>

					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a single option row.
	 */
	private function render_option_row( int $index, array $opt = array() ): void {
		$text = $opt['text'] ?? '';
		$desc = $opt['description'] ?? '';
		?>
		<div class="wpvp-option-row" data-index="<?php echo esc_attr( $index ); ?>">
			<input type="text" name="voting_options[<?php echo esc_attr( $index ); ?>][text]"
					value="<?php echo esc_attr( $text ); ?>"
					placeholder="<?php esc_attr_e( 'Option text', 'wp-voting-plugin' ); ?>"
					class="regular-text" required>
			<input type="text" name="voting_options[<?php echo esc_attr( $index ); ?>][description]"
					value="<?php echo esc_attr( $desc ); ?>"
					placeholder="<?php esc_attr_e( 'Description (optional)', 'wp-voting-plugin' ); ?>"
					class="regular-text">
			<button type="button" class="button wpvp-remove-option" <?php echo $index < 2 ? 'disabled' : ''; ?>>
				&times;
			</button>
		</div>
		<?php
	}

	/**
	 * Render error/success notices.
	 */
	private function render_notices_html(): void {
		foreach ( $this->errors as $error ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
		}
		if ( $this->success ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $this->success ) );
		}
	}
}
