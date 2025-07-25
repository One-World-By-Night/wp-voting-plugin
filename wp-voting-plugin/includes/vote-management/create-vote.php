<?php

/**
 * File: includes/vote-management/create-vote.php
 * Create Vote Management Page
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render the create/edit vote page
 */
function wpvp_render_create_vote_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wp-voting-plugin'));
    }
    
    // Get vote ID if editing
    $vote_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $is_edit = $vote_id > 0;
    
    // Load existing vote data if editing
    $vote = null;
    if ($is_edit) {
        global $wpdb;
        $vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d",
            $vote_id
        ));
        
        if (!$vote) {
            wp_die(__('Vote not found.', 'wp-voting-plugin'));
        }
    }
    
    // Process form submission
    if (isset($_POST['wpvp_save_vote']) && wp_verify_nonce($_POST['wpvp_vote_nonce'], 'wpvp_save_vote')) {
        wpvp_process_vote_form($vote_id);
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo $is_edit ? __('Edit Vote', 'wp-voting-plugin') : __('Create New Vote', 'wp-voting-plugin'); ?></h1>
        
        <form method="post" id="wpvp-vote-form">
            <?php wp_nonce_field('wpvp_save_vote', 'wpvp_vote_nonce'); ?>
            
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- Main Content -->
                    <div id="post-body-content">
                        <?php wpvp_render_vote_basic_fields($vote); ?>
                        <?php wpvp_render_vote_options_fields($vote); ?>
                        <?php wpvp_render_voting_permissions_fields($vote); ?>
                        <?php wpvp_render_vote_settings_fields($vote); ?>
                    </div>
                    
                    <!-- Sidebar -->
                    <div id="postbox-container-1" class="postbox-container">
                        <?php wpvp_render_vote_publish_box($vote); ?>
                        <?php wpvp_render_vote_access_box($vote); ?>
                        <?php wpvp_render_vote_schedule_box($vote); ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Render basic vote fields
 */
function wpvp_render_vote_basic_fields($vote = null) {
    $proposal_name = $vote ? $vote->proposal_name : '';
    $proposal_description = $vote ? $vote->proposal_description : '';
    $voting_type = $vote ? $vote->voting_type : 'single';
    
    // Get available vote types from vote-processing
    $vote_types = wpvp_get_vote_types();
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Vote Details', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="proposal_name"><?php _e('Vote Title', 'wp-voting-plugin'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="proposal_name" name="proposal_name" class="large-text" 
                               value="<?php echo esc_attr($proposal_name); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="proposal_description"><?php _e('Description', 'wp-voting-plugin'); ?></label>
                    </th>
                    <td>
                        <?php 
                        wp_editor($proposal_description, 'proposal_description', array(
                            'textarea_name' => 'proposal_description',
                            'textarea_rows' => 10,
                            'media_buttons' => true
                        ));
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="voting_type"><?php _e('Voting Type', 'wp-voting-plugin'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="voting_type" name="voting_type" class="regular-text" required>
                            <?php foreach ($vote_types as $type => $label) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($voting_type, $type); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" id="voting-type-description"></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render vote options fields
 */
function wpvp_render_vote_options_fields($vote = null) {
    $voting_options = $vote ? json_decode($vote->voting_options, true) : array();
    $number_of_winners = $vote ? $vote->number_of_winners : 1;
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Vote Options', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Options', 'wp-voting-plugin'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <div id="wpvp-options-container">
                            <?php 
                            if (!empty($voting_options)) {
                                foreach ($voting_options as $index => $option) {
                                    wpvp_render_option_row($index, $option);
                                }
                            } else {
                                // Default empty options
                                wpvp_render_option_row(0);
                                wpvp_render_option_row(1);
                            }
                            ?>
                        </div>
                        <button type="button" class="button" id="wpvp-add-option">
                            <?php _e('Add Option', 'wp-voting-plugin'); ?>
                        </button>
                    </td>
                </tr>
                <tr id="number-of-winners-row" style="display: none;">
                    <th scope="row">
                        <label for="number_of_winners"><?php _e('Number of Winners', 'wp-voting-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="number_of_winners" name="number_of_winners" 
                               value="<?php echo esc_attr($number_of_winners); ?>" min="1" class="small-text">
                        <p class="description"><?php _e('For multiple choice and ranked voting types.', 'wp-voting-plugin'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render single option row
 */
function wpvp_render_option_row($index = 0, $option = array()) {
    $text = isset($option['text']) ? $option['text'] : '';
    $description = isset($option['description']) ? $option['description'] : '';
    ?>
    <div class="wpvp-option-row" data-index="<?php echo $index; ?>">
        <div class="wpvp-option-fields">
            <input type="text" name="voting_options[<?php echo $index; ?>][text]" 
                   placeholder="<?php _e('Option text', 'wp-voting-plugin'); ?>" 
                   value="<?php echo esc_attr($text); ?>" class="regular-text" required>
            <input type="text" name="voting_options[<?php echo $index; ?>][description]" 
                   placeholder="<?php _e('Optional description', 'wp-voting-plugin'); ?>" 
                   value="<?php echo esc_attr($description); ?>" class="regular-text">
            <button type="button" class="button wpvp-remove-option" <?php echo $index < 2 ? 'disabled' : ''; ?>>
                <?php _e('Remove', 'wp-voting-plugin'); ?>
            </button>
        </div>
    </div>
    <?php
}

/**
 * Render vote settings fields
 */
function wpvp_render_vote_settings_fields($vote = null) {
    $settings = $vote ? json_decode($vote->settings, true) : array();
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Additional Settings', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Voting Rules', 'wp-voting-plugin'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[allow_revote]" value="1" 
                                   <?php checked(isset($settings['allow_revote']) && $settings['allow_revote']); ?>>
                            <?php _e('Allow users to change their vote', 'wp-voting-plugin'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" name="settings[show_results_before_close]" value="1" 
                                   <?php checked(isset($settings['show_results_before_close']) && $settings['show_results_before_close']); ?>>
                            <?php _e('Show results before voting closes', 'wp-voting-plugin'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" name="settings[anonymous_voting]" value="1" 
                                   <?php checked(isset($settings['anonymous_voting']) && $settings['anonymous_voting']); ?>>
                            <?php _e('Anonymous voting (hide voter identities)', 'wp-voting-plugin'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render voting permissions fields
 */
/**
 * Render voting permissions fields
 */
function wpvp_render_voting_permissions_fields($vote = null) {
    // Get the permission system from settings
    $permission_system = get_option('wpvp_permission_mode', 'wordpress');
    
    $allowed_voters = $vote ? json_decode($vote->allowed_roles, true) : array();
    
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Voting Permissions', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <p class="description">
                <?php 
                printf(
                    __('Currently using: %s permission system', 'wp-voting-plugin'), 
                    '<strong>' . esc_html($permission_system) . '</strong>'
                ); 
                ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="allowed_voters"><?php _e('Who Can Vote', 'wp-voting-plugin'); ?></label>
                    </th>
                    <td>
                        <?php 
                        switch ($permission_system) {
                            case 'wordpress':
                                wpvp_render_wordpress_permissions($allowed_voters);
                                break;
                            case 'accessschema':
                                wpvp_render_accessschema_permissions($allowed_voters);
                                break;
                            case 'custom':
                                wpvp_render_custom_user_permissions($allowed_voters);
                                break;
                            default:
                                echo '<p class="error">' . sprintf(__('Unknown permission system: %s', 'wp-voting-plugin'), esc_html($permission_system)) . '</p>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render WordPress roles permissions
 */
function wpvp_render_wordpress_permissions($selected = array()) {
    // Get pre-selected roles from settings
    $default_roles = get_option('wpvp_wordpress_cast_vote_roles', array());
    
    // Get all WordPress roles
    global $wp_roles;
    $all_roles = $wp_roles->roles;
    
    ?>
    <select id="wpvp-allowed-voters" name="allowed_roles[]" class="wpvp-select2" multiple="multiple" style="width: 100%;">
        <?php foreach ($all_roles as $role_key => $role) : ?>
            <option value="<?php echo esc_attr($role_key); ?>" 
                    <?php selected(in_array($role_key, $selected) || (empty($selected) && in_array($role_key, $default_roles))); ?>>
                <?php echo esc_html(translate_user_role($role['name'])); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php _e('Select which WordPress roles can vote. Default roles are pre-selected based on settings.', 'wp-voting-plugin'); ?>
    </p>
    <?php
}

/**
 * Render AccessSchema permissions
 */
function wpvp_render_accessschema_permissions($selected = array()) {
    // Check if AccessSchema is available
    if (!function_exists('as_get_groups')) {
        echo '<p class="error">' . __('AccessSchema plugin is not active.', 'wp-voting-plugin') . '</p>';
        return;
    }
    
    // Get default groups from settings
    $default_groups = get_option('wpvp_accessschema_cast_vote_groups', array());
    
    // Get all AccessSchema groups
    $groups = as_get_groups(); // This assumes AccessSchema provides this function
    
    ?>
    <select id="wpvp-allowed-voters" name="allowed_roles[]" class="wpvp-select2-tags" multiple="multiple" style="width: 100%;">
        <?php foreach ($groups as $group) : ?>
            <option value="<?php echo esc_attr($group->id); ?>" 
                    <?php selected(in_array($group->id, $selected) || (empty($selected) && in_array($group->id, $default_groups))); ?>>
                <?php echo esc_html($group->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php _e('Select AccessSchema groups or add new ones. Default groups are pre-selected based on settings.', 'wp-voting-plugin'); ?>
    </p>
    <?php
}

/**
 * Render custom user permissions
 */
function wpvp_render_custom_user_permissions($selected = array()) {
    // Get default custom users from settings
    $default_user_ids = get_option('wpvp_can_vote_users', array());
    
    // If editing, use selected users; if new, use defaults
    $selected_users = !empty($selected) ? $selected : $default_user_ids;
    
    ?>
    <select id="wpvp-allowed-voters" name="allowed_roles[]" class="wpvp-select2-users" multiple="multiple" style="width: 100%;">
        <?php 
        // Pre-populate with already selected users
        if (!empty($selected_users)) {
            foreach ($selected_users as $user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    echo '<option value="' . esc_attr($user_id) . '" selected="selected">' . 
                         esc_html($user->display_name . ' (' . $user->user_email . ')') . 
                         '</option>';
                }
            }
        }
        ?>
    </select>
    <p class="description">
        <?php _e('Search and select users who can vote. Start typing to search all users.', 'wp-voting-plugin'); ?>
    </p>
    <?php
}

/**
 * Render publish box (sidebar)
 */
function wpvp_render_vote_publish_box($vote = null) {
    $voting_stage = $vote ? $vote->voting_stage : 'draft';
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Publish', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <div class="submitbox">
                <div id="minor-publishing">
                    <div class="misc-pub-section">
                        <label for="voting_stage"><?php _e('Status:', 'wp-voting-plugin'); ?></label>
                        <select id="voting_stage" name="voting_stage" style="margin-left: 10px;">
                            <option value="draft" <?php selected($voting_stage, 'draft'); ?>><?php _e('Draft', 'wp-voting-plugin'); ?></option>
                            <option value="open" <?php selected($voting_stage, 'open'); ?>><?php _e('Open', 'wp-voting-plugin'); ?></option>
                            <option value="closed" <?php selected($voting_stage, 'closed'); ?>><?php _e('Closed', 'wp-voting-plugin'); ?></option>
                            <option value="archived" <?php selected($voting_stage, 'archived'); ?>><?php _e('Archived', 'wp-voting-plugin'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div id="major-publishing-actions">
                    <?php if ($vote) : ?>
                        <div id="delete-action">
                            <a class="submitdelete deletion" href="<?php echo wp_nonce_url(admin_url('admin.php?page=wpvp-all-votes&action=delete&id=' . $vote->id), 'delete-vote'); ?>">
                                <?php _e('Delete', 'wp-voting-plugin'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div id="publishing-action">
                        <input type="submit" name="wpvp_save_vote" id="publish" class="button button-primary button-large" 
                               value="<?php echo $vote ? __('Update Vote', 'wp-voting-plugin') : __('Create Vote', 'wp-voting-plugin'); ?>">
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render access control box (sidebar)
 */
function wpvp_render_vote_access_box($vote = null) {
    $allowed_roles = $vote ? json_decode($vote->allowed_roles, true) : array();
    $visibility = $vote ? $vote->visibility : 'private';
    
    // Get all WordPress roles
    global $wp_roles;
    $all_roles = $wp_roles->roles;
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Access Control', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <p>
                <label for="visibility"><?php _e('Visibility:', 'wp-voting-plugin'); ?></label><br>
                <select id="visibility" name="visibility" class="widefat">
                    <option value="public" <?php selected($visibility, 'public'); ?>><?php _e('Public', 'wp-voting-plugin'); ?></option>
                    <option value="private" <?php selected($visibility, 'private'); ?>><?php _e('Private (logged-in users)', 'wp-voting-plugin'); ?></option>
                    <option value="restricted" <?php selected($visibility, 'restricted'); ?>><?php _e('Restricted (specific roles)', 'wp-voting-plugin'); ?></option>
                </select>
            </p>
            
            <div id="allowed-roles-section" style="<?php echo $visibility === 'restricted' ? '' : 'display:none;'; ?>">
                <p><strong><?php _e('Allowed Roles:', 'wp-voting-plugin'); ?></strong></p>
                <?php foreach ($all_roles as $role_key => $role) : ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="allowed_roles[]" value="<?php echo esc_attr($role_key); ?>" 
                               <?php checked(in_array($role_key, (array)$allowed_roles)); ?>>
                        <?php echo esc_html(translate_user_role($role['name'])); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render schedule box (sidebar)
 */
function wpvp_render_vote_schedule_box($vote = null) {
    $opening_date = $vote && $vote->opening_date ? $vote->opening_date : '';
    $closing_date = $vote && $vote->closing_date ? $vote->closing_date : '';
    ?>
    <div class="postbox">
        <h2 class="hndle"><?php _e('Schedule', 'wp-voting-plugin'); ?></h2>
        <div class="inside">
            <p>
                <label for="opening_date"><?php _e('Opening Date:', 'wp-voting-plugin'); ?></label><br>
                <input type="datetime-local" id="opening_date" name="opening_date" class="widefat" 
                       value="<?php echo $opening_date ? date('Y-m-d\TH:i', strtotime($opening_date)) : ''; ?>">
            </p>
            <p>
                <label for="closing_date"><?php _e('Closing Date:', 'wp-voting-plugin'); ?></label><br>
                <input type="datetime-local" id="closing_date" name="closing_date" class="widefat" 
                       value="<?php echo $closing_date ? date('Y-m-d\TH:i', strtotime($closing_date)) : ''; ?>">
            </p>
            <p class="description"><?php _e('Leave empty for no time restrictions.', 'wp-voting-plugin'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Process vote form submission
 */
function wpvp_process_vote_form($vote_id = 0) {
    // Include validation functions
    require_once(WPVP_PLUGIN_DIR . 'includes/vote-management/vote-validation.php');
    
    // Validate the vote data
    $validation = wpvp_validate_vote_data($_POST);
    
    if (!$validation['valid']) {
        // Show errors
        foreach ($validation['errors'] as $error) {
            wpvp_add_admin_notice($error, 'error');
        }
        return false;
    }
    
    // Prepare data for saving
    $vote_data = array(
        'proposal_name' => sanitize_text_field($_POST['proposal_name']),
        'proposal_description' => wp_kses_post($_POST['proposal_description']),
        'voting_type' => sanitize_text_field($_POST['voting_type']),
        'voting_options' => $_POST['voting_options'], // Will be JSON encoded in save function
        'number_of_winners' => intval($_POST['number_of_winners']),
        'allowed_roles' => isset($_POST['allowed_roles']) ? $_POST['allowed_roles'] : array(),
        'visibility' => sanitize_text_field($_POST['visibility']),
        'voting_stage' => sanitize_text_field($_POST['voting_stage']),
        'opening_date' => sanitize_text_field($_POST['opening_date']),
        'closing_date' => sanitize_text_field($_POST['closing_date']),
        'settings' => isset($_POST['settings']) ? $_POST['settings'] : array()
    );
    
    // Save or update the vote
    if ($vote_id > 0) {
        $result = wpvp_update_vote($vote_id, $vote_data);
        $message = __('Vote updated successfully.', 'wp-voting-plugin');
    } else {
        $result = wpvp_save_vote($vote_data);
        $message = __('Vote created successfully.', 'wp-voting-plugin');
        if ($result) {
            $vote_id = $result;
        }
    }
    
    if ($result) {
        wpvp_add_admin_notice($message, 'success');
        
        // Redirect to edit page if creating new
        if (!isset($_GET['id']) && $vote_id) {
            wp_redirect(admin_url('admin.php?page=wpvp-edit-vote&id=' . $vote_id . '&message=created'));
            exit;
        }
    } else {
        wpvp_add_admin_notice(__('Error saving vote. Please try again.', 'wp-voting-plugin'), 'error');
    }
}