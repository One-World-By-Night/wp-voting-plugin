<?php
/**
 * Admin Guide page — comprehensive documentation for vote management.
 *
 * Dynamically detects AccessSchema configuration and shows relevant
 * examples and instructions.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Guide {

	/** @var array Collected dynamic data. */
	private $data = array();

	/**
	 * Render the guide page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'wpvp_manage_votes' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-voting-plugin' ) );
		}

		$this->load_dynamic_data();

		?>
		<div class="wrap wpvp-guide-wrap">
			<h1><?php esc_html_e( 'WP Voting Plugin Guide', 'wp-voting-plugin' ); ?></h1>

			<div class="wpvp-guide-layout">
				<?php $this->render_toc(); ?>

				<div class="wpvp-guide-content">
					<?php
					$this->render_overview();
					$this->render_creating_a_vote();
					$this->render_voting_types();
					$this->render_managing_votes();
					$this->render_processing_results();
					$this->render_permissions();
					$this->render_who_can_vote();
					$this->render_notifications();
					$this->render_form_styles();
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Load all dynamic data used across sections.
	 */
	private function load_dynamic_data(): void {
		$this->data = array(
			'asc_mode'      => get_option( 'wpvp_accessschema_mode', 'none' ),
			'asc_url'       => get_option( 'wpvp_accessschema_client_url', '' ),
			'asc_roles'     => get_transient( 'wpvp_accessschema_roles' ),
			'wp_caps'       => get_option(
				'wpvp_wp_capabilities',
				array(
					'create_votes' => 'manage_options',
					'manage_votes' => 'manage_options',
					'cast_votes'   => 'read',
					'view_results' => 'read',
				)
			),
			'vote_types'     => WPVP_Database::get_vote_types(),
			'vote_stages'    => WPVP_Database::get_vote_stages(),
			'visibility'     => WPVP_Database::get_visibility_options(),
			'wp_roles'       => wp_roles()->get_names(),
			'role_templates' => WPVP_Database::get_role_templates(),
			'notifications'  => get_option( 'wpvp_enable_email_notifications', false ),
			'client_id'      => defined( 'ASC_PREFIX' ) ? strtolower( ASC_PREFIX ) : 'wpvp',
		);
	}

	/**
	 * Is AccessSchema configured (not "none")?
	 */
	private function asc_configured(): bool {
		return 'none' !== $this->data['asc_mode'];
	}

	/*
	------------------------------------------------------------------
	 *  Table of Contents sidebar.
	 * ----------------------------------------------------------------*/

	private function render_toc(): void {
		$sections = array(
			'overview'      => __( 'Overview', 'wp-voting-plugin' ),
			'creating'      => __( 'Interactive Vote Builder', 'wp-voting-plugin' ),
			'types'         => __( 'Voting Types', 'wp-voting-plugin' ),
			'managing'      => __( 'Managing Votes', 'wp-voting-plugin' ),
			'processing'    => __( 'Processing Results', 'wp-voting-plugin' ),
			'permissions'   => __( 'Permission System', 'wp-voting-plugin' ),
			'who-can-vote'  => __( 'Who Can Vote', 'wp-voting-plugin' ),
			'notifications' => __( 'Notifications', 'wp-voting-plugin' ),
		);
		?>
		<nav class="wpvp-guide-toc" aria-label="<?php esc_attr_e( 'Guide navigation', 'wp-voting-plugin' ); ?>">
			<h2 class="wpvp-guide-toc__title"><?php esc_html_e( 'Contents', 'wp-voting-plugin' ); ?></h2>
			<ul>
				<?php foreach ( $sections as $id => $label ) : ?>
					<li><a href="#wpvp-guide-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 1: Overview.
	 * ----------------------------------------------------------------*/

	private function render_overview(): void {
		$asc_mode = $this->data['asc_mode'];
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-overview">
			<h2><?php esc_html_e( 'Overview', 'wp-voting-plugin' ); ?></h2>

			<p>
				<?php esc_html_e( 'The WP Voting Plugin provides a complete voting system for your organization. It supports 6 voting types, scheduled open/close dates, role-based voter eligibility, and automated result processing.', 'wp-voting-plugin' ); ?>
			</p>

			<div class="wpvp-guide-callout wpvp-guide-callout--info">
				<strong><?php esc_html_e( 'Current Configuration', 'wp-voting-plugin' ); ?></strong>
				<ul>
					<li>
						<?php esc_html_e( 'Permission Mode:', 'wp-voting-plugin' ); ?>
						<?php if ( $this->asc_configured() ) : ?>
							<span class="wpvp-badge wpvp-badge--open"><?php echo esc_html( ucfirst( $asc_mode ) ); ?> AccessSchema</span>
						<?php else : ?>
							<span class="wpvp-badge wpvp-badge--draft"><?php esc_html_e( 'WordPress Only', 'wp-voting-plugin' ); ?></span>
						<?php endif; ?>
					</li>
					<li>
						<?php esc_html_e( 'Email Notifications:', 'wp-voting-plugin' ); ?>
						<?php if ( $this->data['notifications'] ) : ?>
							<span class="wpvp-badge wpvp-badge--open"><?php esc_html_e( 'Enabled', 'wp-voting-plugin' ); ?></span>
						<?php else : ?>
							<span class="wpvp-badge wpvp-badge--draft"><?php esc_html_e( 'Disabled', 'wp-voting-plugin' ); ?></span>
						<?php endif; ?>
					</li>
					<li>
						<?php
						printf(
							/* translators: %d: number of voting types */
							esc_html__( 'Voting Types Available: %d', 'wp-voting-plugin' ),
							count( $this->data['vote_types'] )
						);
						?>
					</li>
				</ul>
			</div>

			<p>
				<?php
				printf(
					/* translators: 1: Settings page link, 2: Add New link */
					wp_kses(
						__( 'Quick links: <a href="%1$s">Settings</a> | <a href="%2$s">Create a New Vote</a>', 'wp-voting-plugin' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=wpvp-settings' ) ),
					esc_url( admin_url( 'admin.php?page=wpvp-vote-edit' ) )
				);
				?>
			</p>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 2: Creating a Vote.
	 * ----------------------------------------------------------------*/

	private function render_creating_a_vote(): void {
		$form_url = esc_url( admin_url( 'admin.php?page=wpvp-vote-edit' ) );

		// Calculate default dates for wizard: 7 days from now (opening), 14 days from now (closing), both at midnight.
		$opening_timestamp = strtotime( '+7 days midnight' );
		$closing_timestamp = strtotime( '+14 days midnight' );
		$default_opening   = date( 'Y-m-d\TH:i', $opening_timestamp );
		$default_closing   = date( 'Y-m-d\TH:i', $closing_timestamp );
		?>
		<section class="wpvp-guide-section wpvp-guide-builder-intro" id="wpvp-guide-creating">
			<div class="wpvp-guide-callout wpvp-guide-callout--primary">
				<h2><?php esc_html_e( 'Interactive Vote Builder', 'wp-voting-plugin' ); ?></h2>
				<p>
					<?php esc_html_e( 'Want to learn by doing? Fill in the form fields inside each step below as you go through the tutorial, then submit at the end to create a real vote.', 'wp-voting-plugin' ); ?>
				</p>
				<p>
					<?php
					printf(
						/* translators: %s: Link to vote editor */
						esc_html__( 'Prefer to skip the tutorial? %s', 'wp-voting-plugin' ),
						'<a href="' . esc_url( $form_url ) . '" class="button">' . esc_html__( 'Go to Vote Editor', 'wp-voting-plugin' ) . '</a>'
					);
					?>
				</p>
				<p style="font-size: 0.9em; color: #646970; margin-top: 8px;">
					<?php esc_html_e( 'Follow the step-by-step walkthrough below, or jump straight to the form if you already know what you\'re doing.', 'wp-voting-plugin' ); ?>
				</p>
			</div>

			<form id="wpvp-guide-builder-form">
				<?php wp_nonce_field( 'wpvp_guide_create_vote', 'wpvp_guide_nonce' ); ?>

			<div class="wpvp-wizard" id="wpvp-wizard">

				<!-- Step 1 -->
				<div class="wpvp-wizard-step is-open" data-step="1">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="true">
						<span class="wpvp-wizard-step__number">1</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Title & Description', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Enter a clear, descriptive title for the proposal. The description field supports rich text (bold, italic, links, lists) and will be displayed to voters on the ballot page.', 'wp-voting-plugin' ); ?></p>

						<div class="wpvp-guide-form-section">
							<p>
								<label for="wpvp_gb_title"><?php esc_html_e( 'Vote Title', 'wp-voting-plugin' ); ?> <span class="required">*</span></label><br>
								<input type="text" id="wpvp_gb_title" name="proposal_name" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g., Election for Chronicle Coordinator', 'wp-voting-plugin' ); ?>">
							</p>
							<div>
								<label for="wpvp_gb_description"><?php esc_html_e( 'Description', 'wp-voting-plugin' ); ?></label>
								<?php
								wp_editor(
									'',
									'wpvp_gb_description',
									array(
										'textarea_name' => 'proposal_description',
										'textarea_rows' => 6,
										'media_buttons' => false,
										'teeny'         => true,
										'quicktags'     => true,
									)
								);
								?>
							</div>
						</div>

						<div class="wpvp-wizard-step__nav">
							<span></span>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Proposal Metadata &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 2 -->
				<div class="wpvp-wizard-step" data-step="2">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">2</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Proposal Metadata', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Optionally categorize this vote and track who proposed and seconded it. These fields are all optional but help organize and document your voting history.', 'wp-voting-plugin' ); ?></p>

						<div class="wpvp-guide-form-section">
							<?php $classifications = WPVP_Database::get_classifications(); ?>
							<p>
								<label for="wpvp_gb_classification"><?php esc_html_e( 'Classification', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_classification" name="classification" class="regular-text">
									<option value=""><?php esc_html_e( '-- None --', 'wp-voting-plugin' ); ?></option>
									<?php foreach ( $classifications as $class ) : ?>
										<option value="<?php echo esc_attr( $class->classification_name ); ?>">
											<?php echo esc_html( $class->classification_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<span class="description"><?php esc_html_e( 'Type of vote (Chronicle Admission, Bylaw Revision, etc.)', 'wp-voting-plugin' ); ?></span>
							</p>
							<p>
								<label for="wpvp_gb_proposed_by"><?php esc_html_e( 'Proposed By', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" id="wpvp_gb_proposed_by" name="proposed_by" class="regular-text" placeholder="<?php esc_attr_e( 'Person who proposed this vote', 'wp-voting-plugin' ); ?>">
							</p>
							<p>
								<label for="wpvp_gb_seconded_by"><?php esc_html_e( 'Seconded By', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" id="wpvp_gb_seconded_by" name="seconded_by" class="regular-text" placeholder="<?php esc_attr_e( 'Person who seconded this vote', 'wp-voting-plugin' ); ?>">
							</p>
							<p>
								<label for="wpvp_gb_objection_by"><?php esc_html_e( 'Objection By', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" id="wpvp_gb_objection_by" name="objection_by" class="regular-text" placeholder="<?php esc_attr_e( 'Person who objected (for consent agenda)', 'wp-voting-plugin' ); ?>">
								<span class="description"><?php esc_html_e( 'For consent agenda votes that were objected to', 'wp-voting-plugin' ); ?></span>
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Voting Type &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 3 -->
				<div class="wpvp-wizard-step" data-step="3">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">3</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Voting Type', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Select the voting method from the dropdown. The form updates dynamically based on your choice:', 'wp-voting-plugin' ); ?></p>
						<table class="wpvp-guide-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Type', 'wp-voting-plugin' ); ?></th>
									<th><?php esc_html_e( 'Form Behavior', 'wp-voting-plugin' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?php esc_html_e( 'Single Choice / RCV / STV / Condorcet', 'wp-voting-plugin' ); ?></td>
									<td><?php esc_html_e( 'Shows the options editor — add at least 2 options.', 'wp-voting-plugin' ); ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'STV (Single Transferable Vote)', 'wp-voting-plugin' ); ?></td>
									<td><?php esc_html_e( 'Also shows a "Number of Winners" field for multi-seat elections.', 'wp-voting-plugin' ); ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Disciplinary', 'wp-voting-plugin' ); ?></td>
									<td><?php esc_html_e( 'Auto-populates 8 standard punishment levels (Permanent Ban through Condemnation). You can customize these.', 'wp-voting-plugin' ); ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Consent Agenda', 'wp-voting-plugin' ); ?></td>
									<td><?php esc_html_e( 'Hides the options section entirely. The proposal passes unless someone objects.', 'wp-voting-plugin' ); ?></td>
								</tr>
							</tbody>
						</table>

						<div class="wpvp-guide-form-section">
							<p>
								<label for="wpvp_gb_type"><?php esc_html_e( 'Select Voting Method', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_type" name="voting_type" class="regular-text">
									<?php foreach ( $this->data['vote_types'] as $key => $type ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $type['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Options &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 4 -->
				<div class="wpvp-wizard-step" data-step="4">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">4</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Voting Options', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Click "Add Option" to add choices for voters. Each option has a text label (required) and an optional description. A minimum of 2 options is required for most types.', 'wp-voting-plugin' ); ?></p>

						<div class="wpvp-guide-form-section" id="wpvp_gb_options_section">
							<div id="wpvp_gb_options_list">
								<p class="wpvp-gb-option-row">
									<input type="text" name="voting_options[0][text]" class="regular-text" placeholder="<?php esc_attr_e( 'Option 1', 'wp-voting-plugin' ); ?>" required>
									<input type="text" name="voting_options[0][description]" class="regular-text" placeholder="<?php esc_attr_e( 'Description (optional)', 'wp-voting-plugin' ); ?>">
								</p>
								<p class="wpvp-gb-option-row">
									<input type="text" name="voting_options[1][text]" class="regular-text" placeholder="<?php esc_attr_e( 'Option 2', 'wp-voting-plugin' ); ?>" required>
									<input type="text" name="voting_options[1][description]" class="regular-text" placeholder="<?php esc_attr_e( 'Description (optional)', 'wp-voting-plugin' ); ?>">
								</p>
							</div>
							<button type="button" id="wpvp_gb_add_option" class="button"><?php esc_html_e( '+ Add Option', 'wp-voting-plugin' ); ?></button>

							<p id="wpvp_gb_num_winners" style="margin-top: 15px; display: none;">
								<label for="wpvp_gb_winners"><?php esc_html_e( 'Number of Winners (for STV)', 'wp-voting-plugin' ); ?></label><br>
								<input type="number" id="wpvp_gb_winners" name="number_of_winners" min="1" value="1" class="small-text">
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Schedule &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 5 -->
				<div class="wpvp-wizard-step" data-step="5">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">5</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Status & Schedule', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Configure the voting status and optional schedule:', 'wp-voting-plugin' ); ?></p>
						<ul>
							<li>
								<strong><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'Set to Draft to prepare the vote before opening, or Open to make it live immediately.', 'wp-voting-plugin' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Opening Date', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'If set, a draft vote will automatically open at this date/time (checked hourly by cron).', 'wp-voting-plugin' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Closing Date', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'If set, an open vote will automatically close at this date/time, and results will be processed immediately.', 'wp-voting-plugin' ); ?>
							</li>
						</ul>

						<div class="wpvp-guide-form-section">
							<p>
								<label for="wpvp_gb_status"><?php esc_html_e( 'Status', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_status" name="voting_stage" class="regular-text">
									<?php foreach ( $this->data['vote_stages'] as $key => $label ) : ?>
										<?php if ( 'completed' !== $key ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>">
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							</p>
							<p>
								<label for="wpvp_gb_open"><?php esc_html_e( 'Opens (optional)', 'wp-voting-plugin' ); ?></label><br>
								<input type="datetime-local" id="wpvp_gb_open" name="opening_date" class="regular-text" value="<?php echo esc_attr( $default_opening ); ?>">
							</p>
							<p>
								<label for="wpvp_gb_close"><?php esc_html_e( 'Closes (optional)', 'wp-voting-plugin' ); ?></label><br>
								<input type="datetime-local" id="wpvp_gb_close" name="closing_date" class="regular-text" value="<?php echo esc_attr( $default_closing ); ?>">
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Visibility &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 6 -->
				<div class="wpvp-wizard-step" data-step="6">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">6</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Visibility & Eligible Voters', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'The Visibility dropdown controls who can see and vote on the proposal:', 'wp-voting-plugin' ); ?></p>
						<table class="wpvp-guide-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Visibility', 'wp-voting-plugin' ); ?></th>
									<th><?php esc_html_e( 'Who Can Vote', 'wp-voting-plugin' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $this->data['visibility'] as $key => $label ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $label ); ?></strong></td>
										<td>
											<?php
											switch ( $key ) {
												case 'public':
													esc_html_e( 'Any visitor to the site (login not required).', 'wp-voting-plugin' );
													break;
												case 'private':
													esc_html_e( 'Any logged-in WordPress user.', 'wp-voting-plugin' );
													break;
												case 'restricted':
													esc_html_e( 'Only users with specific roles (configured via the Allowed Roles field).', 'wp-voting-plugin' );
													break;
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( $this->asc_configured() ) : ?>
							<div class="wpvp-guide-callout wpvp-guide-callout--success">
								<strong><?php esc_html_e( 'AccessSchema is configured', 'wp-voting-plugin' ); ?></strong>
								<p>
									<?php esc_html_e( 'The Allowed Roles field accepts AccessSchema role paths and wildcard patterns:', 'wp-voting-plugin' ); ?>
								</p>
								<?php $this->render_wildcard_reference(); ?>
							</div>
						<?php else : ?>
							<div class="wpvp-guide-callout wpvp-guide-callout--info">
								<strong><?php esc_html_e( 'WordPress-Only Mode', 'wp-voting-plugin' ); ?></strong>
								<p>
									<?php esc_html_e( 'The Allowed Roles field accepts WordPress role slugs or capabilities. Available roles on this site:', 'wp-voting-plugin' ); ?>
								</p>
								<p class="wpvp-guide-code">
									<?php echo esc_html( implode( ', ', array_keys( $this->data['wp_roles'] ) ) ); ?>
								</p>
							</div>
						<?php endif; ?>

						<div class="wpvp-guide-form-section">
							<h4><?php esc_html_e( 'Who Can View This Vote', 'wp-voting-plugin' ); ?></h4>
							<p>
								<label for="wpvp_gb_visibility"><?php esc_html_e( 'Visibility', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_visibility" name="visibility" class="regular-text">
									<?php foreach ( $this->data['visibility'] as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
							<p id="wpvp_gb_roles_section" style="display: none;">
								<?php if ( ! empty( $this->data['role_templates'] ) ) : ?>
									<div class="wpvp-template-loader" style="margin-bottom: 8px;">
										<label><?php esc_html_e( 'Load from template:', 'wp-voting-plugin' ); ?></label>
										<select class="wpvp-template-select" data-target="allowed_roles" style="min-width: 200px;">
											<option value=""><?php esc_html_e( '-- Select template --', 'wp-voting-plugin' ); ?></option>
											<?php foreach ( $this->data['role_templates'] as $tmpl ) : ?>
												<option value="<?php echo esc_attr( $tmpl->id ); ?>">
													<?php echo esc_html( $tmpl->template_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="button wpvp-apply-template"><?php esc_html_e( 'Apply', 'wp-voting-plugin' ); ?></button>
									</div>
								<?php endif; ?>
								<label for="wpvp_gb_roles"><?php esc_html_e( 'Who Can View', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_roles" name="allowed_roles[]" multiple class="wpvp-select2-roles" style="width:100%;"></select>
								<span class="description"><?php esc_html_e( 'Enter role paths, wildcards, or WP roles', 'wp-voting-plugin' ); ?></span>
							</p>
						</div>

						<div class="wpvp-guide-form-section" style="margin-top: 20px;">
							<h4><?php esc_html_e( 'Who Can Vote', 'wp-voting-plugin' ); ?></h4>
							<p>
								<label for="wpvp_gb_voting_eligibility"><?php esc_html_e( 'Voting Eligibility', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_voting_eligibility" name="voting_eligibility" class="regular-text">
									<option value="public"><?php esc_html_e( 'Anyone (public voting)', 'wp-voting-plugin' ); ?></option>
									<option value="private" selected><?php esc_html_e( 'Logged-in users only', 'wp-voting-plugin' ); ?></option>
									<option value="restricted"><?php esc_html_e( 'Specific roles/groups', 'wp-voting-plugin' ); ?></option>
								</select>
							</p>
							<p id="wpvp_gb_voting_roles_section" style="display: none;">
								<?php if ( ! empty( $this->data['role_templates'] ) ) : ?>
									<div class="wpvp-template-loader" style="margin-bottom: 8px;">
										<label><?php esc_html_e( 'Load from template:', 'wp-voting-plugin' ); ?></label>
										<select class="wpvp-template-select" data-target="voting_roles" style="min-width: 200px;">
											<option value=""><?php esc_html_e( '-- Select template --', 'wp-voting-plugin' ); ?></option>
											<?php foreach ( $this->data['role_templates'] as $tmpl ) : ?>
												<option value="<?php echo esc_attr( $tmpl->id ); ?>">
													<?php echo esc_html( $tmpl->template_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="button wpvp-apply-template"><?php esc_html_e( 'Apply', 'wp-voting-plugin' ); ?></button>
									</div>
								<?php endif; ?>
								<label for="wpvp_gb_voting_roles"><?php esc_html_e( 'Who Can Vote', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_voting_roles" name="voting_roles[]" multiple class="wpvp-select2-voting-roles" style="width:100%;"></select>
								<span class="description"><?php esc_html_e( 'Enter role paths, wildcards, or WP roles', 'wp-voting-plugin' ); ?></span>
							</p>
						</div>

						<div class="wpvp-guide-form-section" style="margin-top: 20px;">
							<h4><?php esc_html_e( 'Additional Vote History Viewers', 'wp-voting-plugin' ); ?></h4>
							<p class="description" style="margin-bottom: 8px;">
								<?php esc_html_e( 'Allow related roles to see how votes were cast under other roles in the same group. The * wildcard binds to the matching slug from the voter\'s role.', 'wp-voting-plugin' ); ?>
							</p>
							<p>
								<?php if ( ! empty( $this->data['role_templates'] ) ) : ?>
									<div class="wpvp-template-loader" style="margin-bottom: 8px;">
										<label><?php esc_html_e( 'Load from template:', 'wp-voting-plugin' ); ?></label>
										<select class="wpvp-template-select" data-target="additional_viewers" style="min-width: 200px;">
											<option value=""><?php esc_html_e( '-- Select template --', 'wp-voting-plugin' ); ?></option>
											<?php foreach ( $this->data['role_templates'] as $tmpl ) : ?>
												<option value="<?php echo esc_attr( $tmpl->id ); ?>">
													<?php echo esc_html( $tmpl->template_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button type="button" class="button wpvp-apply-template"><?php esc_html_e( 'Apply', 'wp-voting-plugin' ); ?></button>
									</div>
								<?php endif; ?>
								<label for="wpvp_gb_additional_viewers"><?php esc_html_e( 'Additional Viewers', 'wp-voting-plugin' ); ?></label><br>
								<select id="wpvp_gb_additional_viewers" name="additional_viewers[]" multiple class="wpvp-select2-additional-viewers" style="width:100%;"></select>
								<span class="description"><?php esc_html_e( 'These roles can view vote history for related roles. * binds to the matching slug.', 'wp-voting-plugin' ); ?></span>
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Settings &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 7 -->
				<div class="wpvp-wizard-step" data-step="7">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">7</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Vote Settings', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Configure optional settings:', 'wp-voting-plugin' ); ?></p>
						<ul>
							<li>
								<strong><?php esc_html_e( 'Allow Revoting', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'If checked, voters can change their vote while the poll is open.', 'wp-voting-plugin' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Show Results Before Closing', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'If checked, eligible voters can see partial results while voting is still open.', 'wp-voting-plugin' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Anonymous Voting', 'wp-voting-plugin' ); ?></strong> &mdash;
								<?php esc_html_e( 'If checked, individual ballot choices are not shown in results (totals are still visible).', 'wp-voting-plugin' ); ?>
							</li>
						</ul>

						<div class="wpvp-guide-form-section">
							<p>
								<label>
									<input type="checkbox" name="settings[allow_revote]" value="1">
									<?php esc_html_e( 'Allow voters to change their vote', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p>
								<label>
									<input type="checkbox" name="settings[show_results_before_closing]" value="1">
									<?php esc_html_e( 'Show results while voting is open', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p>
								<label>
									<input type="checkbox" name="settings[anonymous_voting]" value="1">
									<?php esc_html_e( 'Anonymous voting (hide voter names)', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p>
								<label>
									<input type="checkbox" name="settings[allow_voter_comments]" value="1">
									<?php esc_html_e( 'Allow voters to add an optional comment with their vote', 'wp-voting-plugin' ); ?>
								</label>
							</p>
						</div>

						<hr style="margin: 20px 0;">
						<h4 style="margin-bottom: 8px;"><?php esc_html_e( 'Email Notifications', 'wp-voting-plugin' ); ?></h4>
						<p class="description" style="margin-bottom: 12px;">
							<?php esc_html_e( 'Configure which email notifications are sent for this vote. Leave recipient fields blank to use the default from system settings.', 'wp-voting-plugin' ); ?>
						</p>

						<div class="wpvp-guide-form-section">
							<p>
								<label>
									<input type="checkbox" name="settings[notify_on_open]" value="1">
									<?php esc_html_e( 'Send notification when vote opens', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p style="margin-left: 24px;">
								<label for="wpvp_notify_open_to"><?php esc_html_e( 'Recipients (comma-separated):', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" name="settings[notify_open_to]" id="wpvp_notify_open_to" class="regular-text"
									placeholder="<?php esc_attr_e( 'Blank = system default (Settings > General)', 'wp-voting-plugin' ); ?>" style="width: 100%;">
							</p>

							<p>
								<label>
									<input type="checkbox" name="settings[notify_before_close]" value="1">
									<?php esc_html_e( 'Send reminder 1 day before close', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p style="margin-left: 24px;">
								<label for="wpvp_notify_reminder_to"><?php esc_html_e( 'Recipients (comma-separated):', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" name="settings[notify_reminder_to]" id="wpvp_notify_reminder_to" class="regular-text"
									placeholder="<?php esc_attr_e( 'Blank = system default (Settings > General)', 'wp-voting-plugin' ); ?>" style="width: 100%;">
							</p>

							<p>
								<label>
									<input type="checkbox" name="settings[notify_on_close]" value="1">
									<?php esc_html_e( 'Send notification when vote closes (with results)', 'wp-voting-plugin' ); ?>
								</label>
							</p>
							<p style="margin-left: 24px;">
								<label for="wpvp_notify_close_to"><?php esc_html_e( 'Recipients (comma-separated):', 'wp-voting-plugin' ); ?></label><br>
								<input type="text" name="settings[notify_close_to]" id="wpvp_notify_close_to" class="regular-text"
									placeholder="<?php esc_attr_e( 'Blank = system default (Settings > General)', 'wp-voting-plugin' ); ?>" style="width: 100%;">
							</p>

							<p>
								<label>
									<input type="checkbox" name="settings[notify_voter_confirmation]" value="1" checked>
									<?php esc_html_e( 'Allow voters to opt-in to email confirmation of their vote', 'wp-voting-plugin' ); ?>
								</label>
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<button type="button" class="button wpvp-wizard-next"><?php esc_html_e( 'Next: Save &rarr;', 'wp-voting-plugin' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Step 8 -->
				<div class="wpvp-wizard-step" data-step="8">
					<button type="button" class="wpvp-wizard-step__header" aria-expanded="false">
						<span class="wpvp-wizard-step__number">8</span>
						<span class="wpvp-wizard-step__title"><?php esc_html_e( 'Save', 'wp-voting-plugin' ); ?></span>
						<span class="wpvp-wizard-step__toggle" aria-hidden="true"></span>
					</button>
					<div class="wpvp-wizard-step__body">
						<p><?php esc_html_e( 'Review your choices, then click "Create This Vote" to save. The vote will be created in the status you selected. You\'ll be redirected to the vote editor where you can make further adjustments if needed.', 'wp-voting-plugin' ); ?></p>

						<div class="wpvp-guide-form-section" style="border-top: 2px solid #ddd; padding-top: 20px; margin-top: 20px;">
							<p>
								<button type="submit" class="button button-primary button-large" id="wpvp_gb_submit">
									<?php esc_html_e( '🚀 Create This Vote', 'wp-voting-plugin' ); ?>
								</button>
								<span class="spinner" id="wpvp_gb_spinner" style="float: none; margin-left: 10px;"></span>
							</p>
							<p id="wpvp_gb_message" class="description" style="display: none;"></p>

							<p style="margin-top: 20px;">
								<?php
								printf(
									/* translators: %s: Link to vote editor */
									esc_html__( 'Or %s to use the full editor', 'wp-voting-plugin' ),
									'<a href="' . esc_url( $form_url ) . '">' . esc_html__( 'skip the tutorial', 'wp-voting-plugin' ) . '</a>'
								);
								?>
							</p>
						</div>

						<div class="wpvp-wizard-step__nav">
							<button type="button" class="button wpvp-wizard-prev"><?php esc_html_e( '&larr; Back', 'wp-voting-plugin' ); ?></button>
							<span></span>
						</div>
					</div>
				</div>

			</div><!-- .wpvp-wizard -->

			</form><!-- Close guide builder form -->

		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 3: Voting Types.
	 * ----------------------------------------------------------------*/

	private function render_voting_types(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-types">
			<h2><?php esc_html_e( 'Voting Types', 'wp-voting-plugin' ); ?></h2>

			<p><?php esc_html_e( 'The plugin supports the following voting methods. Each uses a different algorithm to determine the outcome.', 'wp-voting-plugin' ); ?></p>

			<?php foreach ( $this->data['vote_types'] as $slug => $type ) : ?>
				<div class="wpvp-guide-type-block">
					<h3><?php echo esc_html( $type['label'] ); ?>
						<code class="wpvp-guide-slug"><?php echo esc_html( $slug ); ?></code>
					</h3>
					<p><em><?php echo esc_html( $type['description'] ); ?></em></p>

					<?php
					switch ( $slug ) {
						case 'singleton':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Each voter picks exactly one option. The option with the most votes wins (First Past The Post). If two or more options are tied for the lead, a tie is declared.', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Simple yes/no questions, officer elections with few candidates, or any straightforward single-winner decision.', 'wp-voting-plugin' ); ?></p>
							<?php
							break;

						case 'rcv':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Voters drag to rank options from most to least preferred. If no option has a majority (>50%) of first-choice votes, the option with the fewest votes is eliminated and those ballots transfer to their next choice. This repeats until one option achieves a majority.', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Single-winner elections with 3+ candidates where you want to avoid vote splitting and ensure the winner has broad support.', 'wp-voting-plugin' ); ?></p>
							<?php
							break;

						case 'stv':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Multi-winner ranked voting. A quota is calculated using the Droop formula: floor(total_votes / (seats + 1)) + 1. Candidates reaching the quota are elected and their surplus votes transfer proportionally to next preferences. If no one reaches the quota, the candidate with the fewest votes is eliminated and their votes transfer.', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Electing multiple people to a committee or council. Set the "Number of Winners" field to the number of seats.', 'wp-voting-plugin' ); ?></p>
							<?php
							break;

						case 'condorcet':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Voters rank options. Every possible head-to-head matchup is computed. If one option beats all others in pairwise comparisons, it is the Condorcet winner. If no such option exists (a cycle), the Schulze method is used to break the cycle and determine the winner.', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Decisions where you want the most broadly preferred option to win, even if it is not the plurality favorite.', 'wp-voting-plugin' ); ?></p>
							<?php
							break;

						case 'disciplinary':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Voters choose a punishment level. Options are ordered from most to least severe. Starting with the most severe option, votes cascade downward: if an option does not reach a majority, its votes are added to the next less-severe option. The first level to accumulate a majority wins.', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Disciplinary proceedings where the organization needs to determine an appropriate punishment level, ensuring that a vote for a harsher punishment also counts toward milder ones.', 'wp-voting-plugin' ); ?></p>
							<div class="wpvp-guide-callout wpvp-guide-callout--warning">
								<p><?php esc_html_e( 'Tip: When you select Disciplinary as the type, the editor auto-populates the 8 standard OWBN punishment levels. You can customize the text and order if needed.', 'wp-voting-plugin' ); ?></p>
							</div>
							<?php
							break;

						case 'consent':
							?>
							<p><strong><?php esc_html_e( 'How it works:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'The proposal is posted for a review period. If nobody objects during this time, it passes automatically. Any voter who submits the form is filing an objection. If one or more objections are filed, the proposal is marked as "Objected".', 'wp-voting-plugin' ); ?></p>
							<p><strong><?php esc_html_e( 'Best for:', 'wp-voting-plugin' ); ?></strong>
							<?php esc_html_e( 'Routine approvals, procedural changes, or any proposal expected to pass without controversy. Silence is consent.', 'wp-voting-plugin' ); ?></p>
							<div class="wpvp-guide-callout wpvp-guide-callout--info">
								<p><?php esc_html_e( 'Important: Consent votes require a closing date. Set one so the cron system knows when to auto-close and process the results. If no objections are filed, the result will be "Passed".', 'wp-voting-plugin' ); ?></p>
							</div>
							<?php
							break;
					}
					?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 4: Managing Votes.
	 * ----------------------------------------------------------------*/

	private function render_managing_votes(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-managing">
			<h2><?php esc_html_e( 'Managing Votes', 'wp-voting-plugin' ); ?></h2>

			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: All Votes page URL */
						__( 'The <a href="%s">All Votes</a> page shows every vote in the system. Use the controls at the top to filter, search, and sort.', 'wp-voting-plugin' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=wpvp-votes' ) )
				);
				?>
			</p>

			<h3><?php esc_html_e( 'Filtering & Searching', 'wp-voting-plugin' ); ?></h3>
			<ul>
				<li><strong><?php esc_html_e( 'Status filter', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Filter by stage: Draft, Open, Closed, Completed, or Archived.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Type filter', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Filter by voting type (Single Choice, RCV, etc.).', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Search', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Search by proposal title or description text.', 'wp-voting-plugin' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Row Actions', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'Hover over a vote to see available actions:', 'wp-voting-plugin' ); ?></p>
			<ul>
				<li><strong><?php esc_html_e( 'Edit', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Open the vote editor to change any field (title, description, options, dates, etc.). Available for all stages.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Results', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'View calculated results. Only shown for Closed, Completed, and Archived votes.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Delete', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Permanently delete the vote and all its ballots and results. This cannot be undone.', 'wp-voting-plugin' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Bulk Actions', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'Select multiple votes using the checkboxes, then choose a bulk action:', 'wp-voting-plugin' ); ?></p>
			<ul>
				<li><strong><?php esc_html_e( 'Delete', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Delete all selected votes.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Set Open', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Change selected votes to Open status.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Set Closed', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Change selected votes to Closed status.', 'wp-voting-plugin' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Vote Lifecycle', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'Every vote moves through these stages:', 'wp-voting-plugin' ); ?></p>
			<div class="wpvp-guide-flow">
				<?php
				$stages     = $this->data['vote_stages'];
				$stage_keys = array_keys( $stages );
				foreach ( $stage_keys as $i => $key ) :
					?>
					<span class="wpvp-badge wpvp-badge--<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $stages[ $key ] ); ?></span>
					<?php if ( $i < count( $stage_keys ) - 1 ) : ?>
						<span class="wpvp-guide-flow__arrow" aria-hidden="true">&rarr;</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<table class="wpvp-guide-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Transition', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'How It Happens', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Draft to Open', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Manually in the editor, via bulk action, or automatically by cron when the opening date arrives.', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Open to Closed', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Manually in the editor, via bulk action, or automatically by cron when the closing date arrives.', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Closed to Completed', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Automatic — happens immediately when results are processed (either by cron or manually).', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Completed to Archived', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Manually in the editor only. Use this for votes you want to keep but de-emphasize.', 'wp-voting-plugin' ); ?></td>
					</tr>
				</tbody>
			</table>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 5: Processing Results.
	 * ----------------------------------------------------------------*/

	private function render_processing_results(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-processing">
			<h2><?php esc_html_e( 'Processing Results', 'wp-voting-plugin' ); ?></h2>

			<p><?php esc_html_e( 'When a vote is closed, its ballots are run through the appropriate voting algorithm to determine the outcome. This can happen in two ways:', 'wp-voting-plugin' ); ?></p>

			<h3><?php esc_html_e( 'Automatic Processing (Recommended)', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'When the hourly cron runs and detects that a vote\'s closing date has passed:', 'wp-voting-plugin' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'The vote is moved from Open to Closed.', 'wp-voting-plugin' ); ?></li>
				<li><?php esc_html_e( 'All ballots are loaded and passed to the voting algorithm.', 'wp-voting-plugin' ); ?></li>
				<li><?php esc_html_e( 'The algorithm calculates results (winner, rounds, statistics).', 'wp-voting-plugin' ); ?></li>
				<li><?php esc_html_e( 'Results are validated for correctness.', 'wp-voting-plugin' ); ?></li>
				<li><?php esc_html_e( 'Results are saved and the vote is moved to Completed.', 'wp-voting-plugin' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Manual Processing', 'wp-voting-plugin' ); ?></h3>
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: Advanced settings page URL */
						__( 'Go to <a href="%s">Settings &rarr; Advanced</a> and click "Process All Closed Votes". This finds all votes in the Closed stage that don\'t have results yet and processes them.', 'wp-voting-plugin' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=advanced' ) )
				);
				?>
			</p>

			<h3><?php esc_html_e( 'What the Algorithm Produces', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'Each algorithm returns a standardized results array that includes:', 'wp-voting-plugin' ); ?></p>
			<ul>
				<li><strong><?php esc_html_e( 'Winner(s)', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'The winning option(s), or indication of a tie.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Vote counts', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'How many votes each option received.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Round data', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'For ranked methods: details of each elimination/transfer round.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Validation', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Automatic checks for result integrity (e.g., winner actually has the most votes, no impossible totals).', 'wp-voting-plugin' ); ?></li>
			</ul>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 6: Permission System.
	 * ----------------------------------------------------------------*/

	private function render_permissions(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-permissions">
			<h2><?php esc_html_e( 'Permission System', 'wp-voting-plugin' ); ?></h2>

			<p><?php esc_html_e( 'The plugin uses a priority-chain permission system. There are two layers: admin capabilities (who can create and manage votes) and voter eligibility (who can cast ballots).', 'wp-voting-plugin' ); ?></p>

			<h3><?php esc_html_e( 'Admin Capabilities', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'Admin actions always use WordPress capabilities:', 'wp-voting-plugin' ); ?></p>
			<table class="wpvp-guide-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Action', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Required Capability', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Create Votes', 'wp-voting-plugin' ); ?></td>
						<td><code>manage_options</code> <?php esc_html_e( 'or', 'wp-voting-plugin' ); ?> <code>wpvp_create_votes</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Manage Votes (edit, delete, bulk)', 'wp-voting-plugin' ); ?></td>
						<td><code>manage_options</code> <?php esc_html_e( 'or', 'wp-voting-plugin' ); ?> <code>wpvp_manage_votes</code></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Access Settings', 'wp-voting-plugin' ); ?></td>
						<td><code>manage_options</code></td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Voter Eligibility — Priority Chain', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'For restricted votes, the system checks voter eligibility using a priority chain:', 'wp-voting-plugin' ); ?></p>

			<div class="wpvp-guide-callout wpvp-guide-callout--info">
				<ol>
					<li><?php esc_html_e( 'Is AccessSchema configured (mode is not "none")?', 'wp-voting-plugin' ); ?>
						<ul>
							<li><strong><?php esc_html_e( 'Yes:', 'wp-voting-plugin' ); ?></strong> <?php esc_html_e( 'Check the user\'s email against each allowed role path via AccessSchema API.', 'wp-voting-plugin' ); ?></li>
							<li><?php esc_html_e( 'If AccessSchema returns true/false, use that answer.', 'wp-voting-plugin' ); ?></li>
							<li><?php esc_html_e( 'If AccessSchema is unreachable or the function is missing, fall through to step 2.', 'wp-voting-plugin' ); ?></li>
						</ul>
					</li>
					<li><strong><?php esc_html_e( 'WordPress Fallback:', 'wp-voting-plugin' ); ?></strong> <?php esc_html_e( 'Check if the user has any of the allowed roles as a WordPress role slug or capability.', 'wp-voting-plugin' ); ?></li>
				</ol>
			</div>

			<?php if ( $this->asc_configured() ) : ?>
				<?php $this->render_permissions_accessschema(); ?>
			<?php else : ?>
				<?php $this->render_permissions_wp_only(); ?>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Permission section: AccessSchema is configured.
	 */
	private function render_permissions_accessschema(): void {
		$mode  = $this->data['asc_mode'];
		$url   = $this->data['asc_url'];
		$roles = $this->data['asc_roles'];
		?>
		<div class="wpvp-guide-callout wpvp-guide-callout--success">
			<strong><?php esc_html_e( 'AccessSchema is Active', 'wp-voting-plugin' ); ?></strong>
			<ul>
				<li><?php esc_html_e( 'Mode:', 'wp-voting-plugin' ); ?> <strong><?php echo esc_html( ucfirst( $mode ) ); ?></strong></li>
				<?php if ( 'remote' === $mode && $url ) : ?>
					<li><?php esc_html_e( 'Server:', 'wp-voting-plugin' ); ?> <code><?php echo esc_html( $url ); ?></code></li>
				<?php endif; ?>
				<li><?php esc_html_e( 'Client ID:', 'wp-voting-plugin' ); ?> <code><?php echo esc_html( $this->data['client_id'] ); ?></code></li>
			</ul>
		</div>

		<h4><?php esc_html_e( 'How AccessSchema Role Paths Work', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'AccessSchema uses hierarchical role paths to represent positions in the organization. When you set a vote\'s visibility to "Restricted" and add role paths to the Allowed Roles field, the system checks whether the voter\'s email has been granted any of those paths (or child paths beneath them).', 'wp-voting-plugin' ); ?></p>

		<h4><?php esc_html_e( 'Wildcard Patterns', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'You can use wildcard patterns in the Allowed Roles field to match multiple role paths at once:', 'wp-voting-plugin' ); ?></p>
		<?php $this->render_wildcard_reference(); ?>

		<?php if ( ! empty( $roles ) && is_array( $roles ) ) : ?>
			<h4><?php esc_html_e( 'Available Role Paths', 'wp-voting-plugin' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: %d: number of cached roles */
					esc_html__( 'The following role paths were retrieved from your AccessSchema server (%d total). Use these in the Allowed Roles field when creating restricted votes:', 'wp-voting-plugin' ),
					count( $roles )
				);
				?>
			</p>
			<div class="wpvp-guide-roles-list">
				<?php
				$display_roles = $this->extract_role_paths( $roles );
				$shown         = array_slice( $display_roles, 0, 30 );
				$remain        = count( $display_roles ) - 30;
				?>
				<ul>
					<?php foreach ( $shown as $path ) : ?>
						<li><code><?php echo esc_html( $path ); ?></code></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $remain > 0 ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %d: number of additional roles */
							esc_html__( '...and %d more. Use the Settings > Permissions > Test Connection button to refresh the full list.', 'wp-voting-plugin' ),
							intval( $remain )
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<h4><?php esc_html_e( 'Example: Restricting a Vote', 'wp-voting-plugin' ); ?></h4>
			<?php if ( ! empty( $shown ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: example role path */
						esc_html__( 'To restrict a vote to users with the role path "%s", set Visibility to "Restricted to Roles" and type or paste that path into the Allowed Roles field. You can add multiple paths — a voter matching any one of them will be eligible.', 'wp-voting-plugin' ),
						esc_html( $shown[0] )
					);
					?>
				</p>
			<?php endif; ?>

		<?php else : ?>
			<div class="wpvp-guide-callout wpvp-guide-callout--warning">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %s: Settings permissions URL */
							__( 'No cached role data found. Go to <a href="%s">Settings &rarr; Permissions</a> and click "Test Connection" to fetch available roles from the AccessSchema server.', 'wp-voting-plugin' ),
							array( 'a' => array( 'href' => array() ) )
						),
						esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=permissions' ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<h4><?php esc_html_e( 'WordPress Fallback', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'If AccessSchema is unreachable or the client module is missing, the system falls back to checking WordPress roles and capabilities. The current fallback capability map is:', 'wp-voting-plugin' ); ?></p>
		<?php $this->render_wp_capability_table(); ?>
		<?php
	}

	/**
	 * Permission section: WordPress-only mode.
	 */
	private function render_permissions_wp_only(): void {
		?>
		<div class="wpvp-guide-callout wpvp-guide-callout--info">
			<strong><?php esc_html_e( 'WordPress-Only Mode', 'wp-voting-plugin' ); ?></strong>
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: Settings permissions URL */
						__( 'AccessSchema is not configured. Voter eligibility is determined entirely by WordPress roles and capabilities. To enable AccessSchema, go to <a href="%s">Settings &rarr; Permissions</a>.', 'wp-voting-plugin' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=permissions' ) )
				);
				?>
			</p>
		</div>

		<h4><?php esc_html_e( 'Capability Map', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'The following WordPress capabilities control voter-facing actions:', 'wp-voting-plugin' ); ?></p>
		<?php $this->render_wp_capability_table(); ?>

		<h4><?php esc_html_e( 'Available WordPress Roles', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'When setting a vote\'s visibility to "Restricted to Roles", use these WordPress role slugs in the Allowed Roles field:', 'wp-voting-plugin' ); ?></p>
		<table class="wpvp-guide-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Role Slug', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Display Name', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $this->data['wp_roles'] as $slug => $name ) : ?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo esc_html( $name ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h4><?php esc_html_e( 'Example: Restricting a Vote to Editors', 'wp-voting-plugin' ); ?></h4>
		<p><?php esc_html_e( 'Set Visibility to "Restricted to Roles", then type "editor" in the Allowed Roles field. Only users with the Editor role will be able to see and vote on the proposal.', 'wp-voting-plugin' ); ?></p>
		<?php
	}

	/**
	 * Render the WP capability fallback table.
	 */
	private function render_wp_capability_table(): void {
		$caps   = $this->data['wp_caps'];
		$labels = array(
			'create_votes' => __( 'Create Votes', 'wp-voting-plugin' ),
			'manage_votes' => __( 'Manage Votes', 'wp-voting-plugin' ),
			'cast_votes'   => __( 'Cast Votes', 'wp-voting-plugin' ),
			'view_results' => __( 'View Results', 'wp-voting-plugin' ),
		);
		?>
		<table class="wpvp-guide-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin Action', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Required WP Capability', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $labels as $key => $label ) : ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><code><?php echo esc_html( $caps[ $key ] ?? 'read' ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 7: Who Can Vote.
	 * ----------------------------------------------------------------*/

	private function render_who_can_vote(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-who-can-vote">
			<h2><?php esc_html_e( 'Who Can Vote', 'wp-voting-plugin' ); ?></h2>

			<p><?php esc_html_e( 'When a user tries to cast a ballot, the system runs the following checks in order:', 'wp-voting-plugin' ); ?></p>

			<ol>
				<li><strong><?php esc_html_e( 'Is the user logged in?', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Anonymous voting is not supported.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Is the vote in "Open" stage?', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'Ballots can only be cast on open votes.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Is the current time within the date window?', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'If opening/closing dates are set, the current time must fall between them.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Has the user already voted?', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'If yes and revoting is not allowed, the user is blocked.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Does the user pass the access check?', 'wp-voting-plugin' ); ?></strong> &mdash; <?php esc_html_e( 'This depends on the vote\'s visibility setting (see below).', 'wp-voting-plugin' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Access Check by Visibility', 'wp-voting-plugin' ); ?></h3>

			<table class="wpvp-guide-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Visibility', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Access Logic', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Public', 'wp-voting-plugin' ); ?></strong></td>
						<td><?php esc_html_e( 'Everyone passes. No further checks.', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Logged-in Users', 'wp-voting-plugin' ); ?></strong></td>
						<td><?php esc_html_e( 'Any logged-in user passes. No role check needed.', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Restricted to Roles', 'wp-voting-plugin' ); ?></strong></td>
						<td>
							<?php if ( $this->asc_configured() ) : ?>
								<?php esc_html_e( 'First checks the user\'s email against each allowed role path via AccessSchema. If AccessSchema is unreachable, falls back to WordPress role/capability matching.', 'wp-voting-plugin' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Checks if the user has any of the allowed roles as a WordPress role slug or capability.', 'wp-voting-plugin' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Worked Example', 'wp-voting-plugin' ); ?></h3>
			<?php if ( $this->asc_configured() ) : ?>
				<div class="wpvp-guide-callout wpvp-guide-callout--info">
					<p><strong><?php esc_html_e( 'Scenario:', 'wp-voting-plugin' ); ?></strong>
					<?php esc_html_e( 'A vote has Visibility set to "Restricted to Roles" with allowed roles:', 'wp-voting-plugin' ); ?>
					<?php
					$example_roles = $this->get_example_role_paths();
					if ( ! empty( $example_roles ) ) {
						echo '<code>' . esc_html( implode( '</code>, <code>', $example_roles ) ) . '</code>';
					} else {
						echo '<code>Council/Member</code>';
					}
					?>
					</p>
					<p><strong><?php esc_html_e( 'What happens when a user tries to vote:', 'wp-voting-plugin' ); ?></strong></p>
					<ol>
						<li><?php esc_html_e( 'System looks up user\'s email address.', 'wp-voting-plugin' ); ?></li>
						<li>
							<?php
							printf(
								/* translators: %s: client ID */
								esc_html__( 'Calls AccessSchema API: "Does this email have the role path (or any child path) for client \'%s\'?"', 'wp-voting-plugin' ),
								esc_html( $this->data['client_id'] )
							);
							?>
						</li>
						<li><?php esc_html_e( 'If AccessSchema returns true for any of the allowed paths, the user can vote.', 'wp-voting-plugin' ); ?></li>
						<li><?php esc_html_e( 'If AccessSchema returns false for all paths, the user is denied.', 'wp-voting-plugin' ); ?></li>
						<li><?php esc_html_e( 'If AccessSchema is unreachable, the system checks WordPress roles as a fallback.', 'wp-voting-plugin' ); ?></li>
					</ol>
				</div>
			<?php else : ?>
				<div class="wpvp-guide-callout wpvp-guide-callout--info">
					<p><strong><?php esc_html_e( 'Scenario:', 'wp-voting-plugin' ); ?></strong>
					<?php esc_html_e( 'A vote has Visibility set to "Restricted to Roles" with allowed roles:', 'wp-voting-plugin' ); ?>
					<code>editor</code>, <code>administrator</code>
					</p>
					<p><strong><?php esc_html_e( 'What happens when a user tries to vote:', 'wp-voting-plugin' ); ?></strong></p>
					<ol>
						<li><?php esc_html_e( 'System checks if the user has the "editor" WordPress role. If yes, they can vote.', 'wp-voting-plugin' ); ?></li>
						<li><?php esc_html_e( 'If not, checks if the user has the "administrator" role. If yes, they can vote.', 'wp-voting-plugin' ); ?></li>
						<li><?php esc_html_e( 'If neither role matches, it also checks them as WordPress capabilities. This means "edit_posts" would match any user with that capability.', 'wp-voting-plugin' ); ?></li>
						<li><?php esc_html_e( 'If nothing matches, the user is denied.', 'wp-voting-plugin' ); ?></li>
					</ol>
				</div>
			<?php endif; ?>

			<div class="wpvp-guide-callout wpvp-guide-callout--warning">
				<p><strong><?php esc_html_e( 'Note:', 'wp-voting-plugin' ); ?></strong>
				<?php esc_html_e( 'Administrators (users with manage_options) always bypass the access check and can vote on any open vote.', 'wp-voting-plugin' ); ?></p>
			</div>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Section 8: Notifications & Automation.
	 * ----------------------------------------------------------------*/

	private function render_notifications(): void {
		?>
		<section class="wpvp-guide-section" id="wpvp-guide-notifications">
			<h2><?php esc_html_e( 'Notifications & Automation', 'wp-voting-plugin' ); ?></h2>

			<h3><?php esc_html_e( 'Hourly Cron Schedule', 'wp-voting-plugin' ); ?></h3>
			<p><?php esc_html_e( 'The plugin registers a WordPress cron event that runs every hour. It performs the following:', 'wp-voting-plugin' ); ?></p>
			<ol>
				<li><strong><?php esc_html_e( 'Auto-open:', 'wp-voting-plugin' ); ?></strong> <?php esc_html_e( 'Draft votes with an opening date in the past are moved to Open.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Auto-close:', 'wp-voting-plugin' ); ?></strong> <?php esc_html_e( 'Open votes with a closing date in the past are moved to Closed.', 'wp-voting-plugin' ); ?></li>
				<li><strong><?php esc_html_e( 'Auto-process:', 'wp-voting-plugin' ); ?></strong> <?php esc_html_e( 'Immediately after a vote is auto-closed, its results are calculated and the vote moves to Completed.', 'wp-voting-plugin' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Email Notifications', 'wp-voting-plugin' ); ?></h3>
			<?php if ( $this->data['notifications'] ) : ?>
				<div class="wpvp-guide-callout wpvp-guide-callout--success">
					<p>
						<strong><?php esc_html_e( 'Email notifications are currently enabled.', 'wp-voting-plugin' ); ?></strong>
						<?php
						printf(
							wp_kses(
								/* translators: %s: General settings URL */
								__( 'You can disable them in <a href="%s">Settings &rarr; General</a>.', 'wp-voting-plugin' ),
								array( 'a' => array( 'href' => array() ) )
							),
							esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=general' ) )
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="wpvp-guide-callout wpvp-guide-callout--warning">
					<p>
						<strong><?php esc_html_e( 'Email notifications are currently disabled.', 'wp-voting-plugin' ); ?></strong>
						<?php
						printf(
							wp_kses(
								/* translators: %s: General settings URL */
								__( 'Enable them in <a href="%s">Settings &rarr; General</a> to notify voters when votes open and close.', 'wp-voting-plugin' ),
								array( 'a' => array( 'href' => array() ) )
							),
							esc_url( admin_url( 'admin.php?page=wpvp-settings&tab=general' ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( 'When enabled, the following emails are sent:', 'wp-voting-plugin' ); ?></p>
			<table class="wpvp-guide-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Recipients', 'wp-voting-plugin' ); ?></th>
						<th><?php esc_html_e( 'Content', 'wp-voting-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Vote Opened', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Eligible voters', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Notification that a new vote is open with a link to the ballot.', 'wp-voting-plugin' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Vote Closed', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Users who voted', 'wp-voting-plugin' ); ?></td>
						<td><?php esc_html_e( 'Notification that voting has ended with a link to view results.', 'wp-voting-plugin' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h4><?php esc_html_e( 'Sending Details', 'wp-voting-plugin' ); ?></h4>
			<ul>
				<li><?php esc_html_e( 'Emails are sent individually via wp_mail() (not BCC) so each recipient gets a personal copy.', 'wp-voting-plugin' ); ?></li>
				<li><?php esc_html_e( 'To prevent server overload, emails are batched in groups of 50.', 'wp-voting-plugin' ); ?></li>
			</ul>

			<?php if ( $this->asc_configured() ) : ?>
				<div class="wpvp-guide-callout wpvp-guide-callout--warning">
					<strong><?php esc_html_e( 'AccessSchema Limitation', 'wp-voting-plugin' ); ?></strong>
					<p><?php esc_html_e( 'For restricted votes using AccessSchema role paths, the system cannot enumerate all eligible users (AccessSchema checks one user at a time). "Vote Opened" notifications for restricted votes will only go to WordPress administrators. "Vote Closed" notifications go to all users who actually voted, which works regardless of the permission system.', 'wp-voting-plugin' ); ?></p>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Helpers.
	 * ----------------------------------------------------------------*/

	/**
	 * Extract role path strings from the cached AccessSchema roles data.
	 *
	 * The transient may be a flat array of path strings, an array of objects
	 * with a 'path' key, or a nested structure with 'roles' key.
	 */
	private function extract_role_paths( $roles ): array {
		if ( ! is_array( $roles ) ) {
			return array();
		}

		// If the data has a 'roles' key, use that.
		if ( isset( $roles['roles'] ) && is_array( $roles['roles'] ) ) {
			$roles = $roles['roles'];
		}

		$paths = array();
		foreach ( $roles as $role ) {
			if ( is_string( $role ) ) {
				$paths[] = $role;
			} elseif ( is_array( $role ) && isset( $role['path'] ) ) {
				$paths[] = $role['path'];
			} elseif ( is_array( $role ) && isset( $role['name'] ) ) {
				$paths[] = $role['name'];
			}
		}

		return $paths;
	}

	/**
	 * Render a few AccessSchema role path examples from cached data.
	 */
	private function render_role_path_examples(): void {
		$roles = $this->data['asc_roles'];
		$paths = $this->extract_role_paths( $roles );

		if ( ! empty( $paths ) ) {
			$examples = array_slice( $paths, 0, 3 );
			echo '<p class="wpvp-guide-code">';
			echo esc_html( implode( "\n", $examples ) );
			echo '</p>';
		} else {
			echo '<p class="wpvp-guide-code">Organization/Council/Member<br>Organization/Staff/Coordinator</p>';
		}
	}

	/**
	 * Get 1-2 example role paths from cached data for the worked example.
	 */
	private function get_example_role_paths(): array {
		$roles = $this->data['asc_roles'];
		$paths = $this->extract_role_paths( $roles );

		if ( ! empty( $paths ) ) {
			return array_slice( $paths, 0, 2 );
		}

		return array();
	}

	/**
	 * Render the wildcard syntax reference table.
	 */
	private function render_wildcard_reference(): void {
		?>
		<table class="wpvp-guide-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pattern', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Meaning', 'wp-voting-plugin' ); ?></th>
					<th><?php esc_html_e( 'Example Match', 'wp-voting-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>Chronicle/Boston/CM</code></td>
					<td><?php esc_html_e( 'Exact path (and children with include_children)', 'wp-voting-plugin' ); ?></td>
					<td><code>Chronicle/Boston/CM</code></td>
				</tr>
				<tr>
					<td><code>Chronicle/*/CM</code></td>
					<td><?php esc_html_e( '* = any single segment', 'wp-voting-plugin' ); ?></td>
					<td><code>Chronicle/Boston/CM</code>, <code>Chronicle/NYC/CM</code></td>
				</tr>
				<tr>
					<td><code>Players/**</code></td>
					<td><?php esc_html_e( '** = any number of segments', 'wp-voting-plugin' ); ?></td>
					<td><code>Players/Active</code>, <code>Players/Boston/Active</code></td>
				</tr>
				<tr>
					<td><code>Coordinator/*/Coordinator</code></td>
					<td><?php esc_html_e( 'Wildcard in the middle', 'wp-voting-plugin' ); ?></td>
					<td><code>Coordinator/Camarilla/Coordinator</code></td>
				</tr>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'Wildcards are expanded against cached AccessSchema roles. Run Settings > Permissions > Test Connection to refresh the role cache.', 'wp-voting-plugin' ); ?>
		</p>
		<?php
	}

	/*
	------------------------------------------------------------------
	 *  Interactive Vote Builder.
	 * ----------------------------------------------------------------*/

	/**
	 * Introduction to the interactive vote builder.
	 */


	/**
	 * Add inline styles for the guide builder form.
	 */
	private function render_form_styles(): void {
		?>
		<style>
			.wpvp-guide-form-section {
				background: #f9f9f9;
				padding: 15px;
				margin: 15px 0;
				border-radius: 4px;
				border-left: 3px solid #2271b1;
			}
			.wpvp-gb-option-row {
				display: flex;
				gap: 10px;
				margin-bottom: 10px;
			}
			.wpvp-gb-option-row input[type="text"] {
				flex: 1;
			}
			.required {
				color: #d63638;
			}
			#wpvp_gb_message.success {
				color: #00a32a;
				font-weight: 600;
			}
			#wpvp_gb_message.error {
				color: #d63638;
				font-weight: 600;
			}
		</style>
		<?php
	}
}
