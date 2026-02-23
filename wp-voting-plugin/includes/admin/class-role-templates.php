<?php
/**
 * Role Templates admin page — create, edit, delete reusable AccessSchema role sets.
 */

defined( 'ABSPATH' ) || exit;

class WPVP_Role_Templates {

	/** @var array */
	private $errors = array();

	/** @var string */
	private $success = '';

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-voting-plugin' ) );
		}

		$section = isset( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'roles';

		// Process form submissions.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			if ( 'classifications' === $section ) {
				if ( isset( $_POST['wpvp_delete_classification'] ) ) {
					$this->process_delete_classification();
				} else {
					$this->process_classification_form();
				}
			} else {
				if ( isset( $_POST['wpvp_delete_template'] ) ) {
					$this->process_delete();
				} else {
					$this->process_form();
				}
			}
		}

		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add', 'edit' ), true ) ) {
			if ( 'classifications' === $section ) {
				$this->render_classification_form();
			} else {
				$this->render_form();
			}
		} else {
			if ( 'classifications' === $section ) {
				$this->render_classifications_list();
			} else {
				$this->render_list();
			}
		}
	}

	/**
	 * List all role templates.
	 */
	private function render_list(): void {
		$templates = WPVP_Database::get_role_templates();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Role & Classification Templates', 'wp-voting-plugin' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-role-templates&section=roles&action=add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Role Template', 'wp-voting-plugin' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_tabs( 'roles' ); ?>

			<?php $this->render_notices(); ?>

			<?php if ( empty( $templates ) ) : ?>
				<p><?php esc_html_e( 'No role templates yet. Create one to reuse common role sets across votes.', 'wp-voting-plugin' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'wp-voting-plugin' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Description', 'wp-voting-plugin' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Roles', 'wp-voting-plugin' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Created', 'wp-voting-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $templates as $template ) :
							$roles = json_decode( $template->roles, true );
							$roles = is_array( $roles ) ? $roles : array();
							$edit_url = admin_url( 'admin.php?page=wpvp-role-templates&action=edit&id=' . intval( $template->id ) );
						?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( $edit_url ); ?>">
											<?php echo esc_html( $template->template_name ); ?>
										</a>
									</strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wp-voting-plugin' ); ?></a>
											|
										</span>
										<span class="delete">
											<form method="post" style="display:inline;">
												<?php wp_nonce_field( 'wpvp_delete_template_' . $template->id ); ?>
												<input type="hidden" name="wpvp_delete_template" value="<?php echo intval( $template->id ); ?>">
												<button type="submit" class="button-link wpvp-delete-link" onclick="return confirm('<?php echo esc_js( __( 'Delete this template?', 'wp-voting-plugin' ) ); ?>');">
													<?php esc_html_e( 'Delete', 'wp-voting-plugin' ); ?>
												</button>
											</form>
										</span>
									</div>
								</td>
								<td><?php echo esc_html( $template->template_description ); ?></td>
								<td>
									<?php
									if ( ! empty( $roles ) ) {
										echo '<code>' . esc_html( implode( '</code>, <code>', $roles ) ) . '</code>';
									} else {
										esc_html_e( 'None', 'wp-voting-plugin' );
									}
									?>
								</td>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $template->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add/Edit form for a role template.
	 */
	private function render_form(): void {
		$template_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$template    = $template_id ? WPVP_Database::get_role_template( $template_id ) : null;
		$is_edit     = ! empty( $template );

		$name        = $is_edit ? $template->template_name : '';
		$description = $is_edit ? $template->template_description : '';
		$roles       = array();
		if ( $is_edit ) {
			$decoded = json_decode( $template->roles, true );
			$roles   = is_array( $decoded ) ? $decoded : array();
		}

		// Fetch cached AccessSchema roles for Select2 suggestions.
		$cached_roles = get_transient( 'wpvp_accessschema_roles' );
		$suggestions  = array();
		if ( ! empty( $cached_roles ) && is_array( $cached_roles ) ) {
			$suggestions = WPVP_Permissions::extract_role_paths( $cached_roles );
		}

		$page_title = $is_edit
			? __( 'Edit Role Template', 'wp-voting-plugin' )
			: __( 'Add Role Template', 'wp-voting-plugin' );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $page_title ); ?></h1>

			<?php $this->render_notices(); ?>

			<form method="post">
				<?php wp_nonce_field( 'wpvp_save_template' ); ?>
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="template_id" value="<?php echo intval( $template->id ); ?>">
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="template_name"><?php esc_html_e( 'Template Name', 'wp-voting-plugin' ); ?></label>
						</th>
						<td>
							<input type="text" id="template_name" name="template_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'A descriptive name like "All Chronicle CMs" or "Executive Council".', 'wp-voting-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_description"><?php esc_html_e( 'Description', 'wp-voting-plugin' ); ?></label>
						</th>
						<td>
							<textarea id="template_description" name="template_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="template_roles"><?php esc_html_e( 'Roles / Groups', 'wp-voting-plugin' ); ?></label>
						</th>
						<td>
							<select id="template_roles" name="template_roles[]" class="wpvp-select2-roles" multiple="multiple" style="width: 100%;">
								<?php foreach ( $roles as $role ) : ?>
									<option value="<?php echo esc_attr( $role ); ?>" selected="selected"><?php echo esc_html( $role ); ?></option>
								<?php endforeach; ?>
								<?php foreach ( $suggestions as $path ) :
									if ( ! in_array( $path, $roles, true ) ) : ?>
										<option value="<?php echo esc_attr( $path ); ?>"><?php echo esc_html( $path ); ?></option>
									<?php endif;
								endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Enter role paths, WordPress roles, or wildcards. Examples: Chronicle/*/CM, Players/**, editor', 'wp-voting-plugin' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( $is_edit ? __( 'Update Template', 'wp-voting-plugin' ) : __( 'Create Template', 'wp-voting-plugin' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle form submission for create/update.
	 */
	private function process_form(): void {
		if ( ! check_admin_referer( 'wpvp_save_template' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->errors[] = __( 'Permission denied.', 'wp-voting-plugin' );
			return;
		}

		$template_name = sanitize_text_field( wp_unslash( $_POST['template_name'] ?? '' ) );
		if ( empty( $template_name ) ) {
			$this->errors[] = __( 'Template name is required.', 'wp-voting-plugin' );
			return;
		}

		$raw_roles = isset( $_POST['template_roles'] ) ? (array) $_POST['template_roles'] : array();
		$roles     = array_values( array_filter( array_map( 'sanitize_text_field', $raw_roles ) ) );

		if ( empty( $roles ) ) {
			$this->errors[] = __( 'At least one role is required.', 'wp-voting-plugin' );
			return;
		}

		$data = array(
			'template_name'        => $template_name,
			'template_description' => sanitize_textarea_field( wp_unslash( $_POST['template_description'] ?? '' ) ),
			'roles'                => $roles,
		);

		$template_id = absint( $_POST['template_id'] ?? 0 );

		if ( $template_id ) {
			$result = WPVP_Database::update_role_template( $template_id, $data );
			if ( $result ) {
				$this->success = __( 'Template updated.', 'wp-voting-plugin' );
			} else {
				$this->errors[] = __( 'Failed to update template.', 'wp-voting-plugin' );
			}
		} else {
			$new_id = WPVP_Database::save_role_template( $data );
			if ( $new_id ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wpvp-role-templates&action=edit&id=' . $new_id . '&wpvp_saved=1' ) );
				exit;
			} else {
				$this->errors[] = __( 'Failed to create template. The name may already be in use.', 'wp-voting-plugin' );
			}
		}
	}

	/**
	 * Handle template deletion.
	 */
	private function process_delete(): void {
		$template_id = absint( $_POST['wpvp_delete_template'] ?? 0 );
		if ( ! $template_id ) {
			return;
		}

		if ( ! check_admin_referer( 'wpvp_delete_template_' . $template_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->errors[] = __( 'Permission denied.', 'wp-voting-plugin' );
			return;
		}

		if ( WPVP_Database::delete_role_template( $template_id ) ) {
			$this->success = __( 'Template deleted.', 'wp-voting-plugin' );
		} else {
			$this->errors[] = __( 'Failed to delete template.', 'wp-voting-plugin' );
		}
	}

	/**
	 * Render success/error notices.
	 */
	private function render_notices(): void {
		if ( isset( $_GET['wpvp_saved'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Template saved.', 'wp-voting-plugin' )
			);
		}

		if ( $this->success ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $this->success )
			);
		}

		foreach ( $this->errors as $error ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $error )
			);
		}
	}

	/**
	 * Render tabs for switching between role templates and classifications.
	 */
	private function render_tabs( string $current ): void {
		$tabs = array(
			'roles'           => __( 'Role Templates', 'wp-voting-plugin' ),
			'classifications' => __( 'Classifications', 'wp-voting-plugin' ),
		);

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $label ) {
			$class = ( $tab === $current ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=wpvp-role-templates&section=' . $tab ) ),
				esc_attr( $class ),
				esc_html( $label )
			);
		}
		echo '</h2>';
	}

	/*
	------------------------------------------------------------------
	 *  Classifications management.
	 * ----------------------------------------------------------------*/

	/**
	 * List all classifications.
	 */
	private function render_classifications_list(): void {
		$classifications = WPVP_Database::get_classifications();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Role & Classification Templates', 'wp-voting-plugin' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-role-templates&section=classifications&action=add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Classification', 'wp-voting-plugin' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_tabs( 'classifications' ); ?>

			<?php $this->render_notices(); ?>

			<?php if ( empty( $classifications ) ) : ?>
				<p><?php esc_html_e( 'No classifications yet.', 'wp-voting-plugin' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Classification Name', 'wp-voting-plugin' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Display Order', 'wp-voting-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $classifications as $classification ) :
							$edit_url = admin_url( 'admin.php?page=wpvp-role-templates&section=classifications&action=edit&id=' . intval( $classification->id ) );
						?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( $edit_url ); ?>">
											<?php echo esc_html( $classification->classification_name ); ?>
										</a>
									</strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wp-voting-plugin' ); ?></a>
											|
										</span>
										<span class="delete">
											<form method="post" style="display:inline;">
												<?php wp_nonce_field( 'wpvp_delete_classification_' . $classification->id ); ?>
												<input type="hidden" name="wpvp_delete_classification" value="<?php echo intval( $classification->id ); ?>">
												<button type="submit" class="button-link wpvp-delete-link" onclick="return confirm('<?php echo esc_js( __( 'Delete this classification?', 'wp-voting-plugin' ) ); ?>');">
													<?php esc_html_e( 'Delete', 'wp-voting-plugin' ); ?>
												</button>
											</form>
										</span>
									</div>
								</td>
								<td><?php echo intval( $classification->display_order ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render add/edit classification form.
	 */
	private function render_classification_form(): void {
		$classification_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$classification    = $classification_id ? WPVP_Database::get_classification( $classification_id ) : null;

		$is_edit = (bool) $classification;
		?>
		<div class="wrap">
			<h1>
				<?php
				if ( $is_edit ) {
					esc_html_e( 'Edit Classification', 'wp-voting-plugin' );
				} else {
					esc_html_e( 'Add New Classification', 'wp-voting-plugin' );
				}
				?>
			</h1>

			<?php $this->render_notices(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-role-templates&section=classifications' ) ); ?>">
				<?php wp_nonce_field( 'wpvp_save_classification' ); ?>
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="classification_id" value="<?php echo intval( $classification->id ); ?>">
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="classification_name"><?php esc_html_e( 'Classification Name', 'wp-voting-plugin' ); ?></label>
						</th>
						<td>
							<input type="text" name="classification_name" id="classification_name" class="regular-text" value="<?php echo esc_attr( $classification->classification_name ?? '' ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="display_order"><?php esc_html_e( 'Display Order', 'wp-voting-plugin' ); ?></label>
						</th>
						<td>
							<input type="number" name="display_order" id="display_order" value="<?php echo intval( $classification->display_order ?? 0 ); ?>" min="0" step="1">
							<p class="description"><?php esc_html_e( 'Lower numbers appear first in dropdown lists.', 'wp-voting-plugin' ); ?></p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Classification', 'wp-voting-plugin' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpvp-role-templates&section=classifications' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'wp-voting-plugin' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Process classification form submission.
	 */
	private function process_classification_form(): void {
		if ( ! check_admin_referer( 'wpvp_save_classification' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->errors[] = __( 'Permission denied.', 'wp-voting-plugin' );
			return;
		}

		$classification_id = isset( $_POST['classification_id'] ) ? absint( $_POST['classification_id'] ) : 0;

		$data = array(
			'classification_name' => sanitize_text_field( $_POST['classification_name'] ?? '' ),
			'display_order'       => intval( $_POST['display_order'] ?? 0 ),
		);

		if ( empty( $data['classification_name'] ) ) {
			$this->errors[] = __( 'Classification name is required.', 'wp-voting-plugin' );
			return;
		}

		if ( $classification_id ) {
			// Update existing.
			if ( WPVP_Database::update_classification( $classification_id, $data ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wpvp-role-templates&section=classifications&wpvp_saved=1' ) );
				exit;
			} else {
				$this->errors[] = __( 'Failed to update classification.', 'wp-voting-plugin' );
			}
		} else {
			// Create new.
			$new_id = WPVP_Database::save_classification( $data );
			if ( $new_id ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wpvp-role-templates&section=classifications&wpvp_saved=1' ) );
				exit;
			} else {
				$this->errors[] = __( 'Failed to create classification.', 'wp-voting-plugin' );
			}
		}
	}

	/**
	 * Handle classification deletion.
	 */
	private function process_delete_classification(): void {
		$classification_id = absint( $_POST['wpvp_delete_classification'] ?? 0 );
		if ( ! $classification_id ) {
			return;
		}

		if ( ! check_admin_referer( 'wpvp_delete_classification_' . $classification_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->errors[] = __( 'Permission denied.', 'wp-voting-plugin' );
			return;
		}

		if ( WPVP_Database::delete_classification( $classification_id ) ) {
			$this->success = __( 'Classification deleted.', 'wp-voting-plugin' );
		} else {
			$this->errors[] = __( 'Failed to delete classification.', 'wp-voting-plugin' );
		}
	}
}
