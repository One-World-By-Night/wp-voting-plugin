<?php

/** File: includes/admin/settings.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Admin settings page for voting plugin configuration
 */

defined('ABSPATH') || exit;

/**
 * Add admin menu pages
 */
add_action('admin_menu', function () {
    // Main menu - point to all votes page
    add_menu_page(
        __('WP Voting', 'wp-voting-plugin'),
        __('WP Voting', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-all-votes',  
        'wpvp_render_all_votes_page', 
        'dashicons-chart-pie',
        30
    );

    // All Votes submenu (same slug as parent to highlight)
    add_submenu_page(
        'wpvp-all-votes',  
        __('All Votes', 'wp-voting-plugin'),
        __('All Votes', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-all-votes',  // Same as parent
        'wpvp_render_all_votes_page'
    );

    // Settings submenu
    add_submenu_page(
        'wpvp-all-votes',  
        __('Settings', 'wp-voting-plugin'),
        __('Settings', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-settings',
        'wpvp_render_settings_page'
    );
});

/**
 * Register settings
 */
add_action('admin_init', function () {
    // General settings
    register_setting('wpvp_general', 'wpvp_settings');

    // Permission settings
    register_setting('wpvp_permissions', 'wpvp_capabilities');
    register_setting('wpvp_permissions', 'wpvp_permission_mode');
    register_setting('wpvp_permissions', 'wpvp_can_vote_users');

    // AccessSchema settings (if using AccessSchema)
    $prefix = 'wpvp';
    register_setting('wpvp_accessschema', "{$prefix}_accessschema_mode");
    register_setting('wpvp_accessschema', "{$prefix}_accessschema_client_url");
    register_setting('wpvp_accessschema', "{$prefix}_accessschema_client_key");
    register_setting('wpvp_accessschema', "{$prefix}_capability_map");

    // Add settings sections and fields
    add_settings_section(
        'wpvp_general_section',
        'General Settings',
        null,
        'wpvp_settings'
    );

    add_settings_field(
        'wpvp_keep_data_on_uninstall',
        'Keep data when deleting plugin',
        function() {
            $value = get_option('wpvp_keep_data_on_uninstall', false);
            echo '<input type="checkbox" name="wpvp_keep_data_on_uninstall" value="1" ' . checked($value, true, false) . '>';
            echo '<p class="description">Check to preserve votes and settings if plugin is deleted</p>';
        },
        'wpvp_settings',
        'wpvp_general_section'
    );

    // Register with sanitization callback for arrays
    register_setting('wpvp_accessschema', "{$prefix}_capability_map", [
        'sanitize_callback' => function($input) {
            if (!is_array($input)) return [];
            
            // Convert comma-separated strings to arrays
            foreach (['create_votes', 'cast_votes'] as $key) {
                if (isset($input[$key]) && is_string($input[$key])) {
                    // Split by comma and trim whitespace
                    $input[$key] = array_map('trim', explode(',', $input[$key]));
                    // Remove empty values
                    $input[$key] = array_filter($input[$key]);
                }
            }
            
            return $input;
        }
    ]);
});

/**
 * Render main settings page
 */
function wpvp_render_settings_page()
{
    $active_tab = $_GET['tab'] ?? 'general';
?>
    <div class="wrap">
        <h1><?php _e('WP Voting Settings', 'wp-voting-plugin'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=wpvp-settings&tab=general"
                class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                <?php _e('General', 'wp-voting-plugin'); ?>
            </a>
            <a href="?page=wpvp-settings&tab=permissions"
                class="nav-tab <?php echo $active_tab === 'permissions' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Permissions', 'wp-voting-plugin'); ?>
            </a>
            <a href="?page=wpvp-settings&tab=accessschema"
                class="nav-tab <?php echo $active_tab === 'accessschema' ? 'nav-tab-active' : ''; ?>">
                <?php _e('AccessSchema', 'wp-voting-plugin'); ?>
            </a>
        </h2>

        <form method="post" action="options.php">
            <?php
            switch ($active_tab) {
                case 'permissions':
                    wpvp_render_permissions_tab();
                    break;

                case 'accessschema':
                    wpvp_render_accessschema_tab();
                    break;

                default:
                    wpvp_render_general_tab();
            }

            submit_button();
            ?>
        </form>
    </div>
<?php
}

/**
 * General settings tab
 */
function wpvp_render_general_tab()
{
    settings_fields('wpvp_general');
    $settings = get_option('wpvp_settings', []);
?>

    <table class="form-table">
        <tr>
            <th scope="row"><?php echo __('Default Voting Type', 'wp-voting-plugin'); ?></th>
            <td>
                <select name="wpvp_settings[default_voting_type]">
                    <option value="single" <?php selected($settings['default_voting_type'] ?? 'single', 'single'); ?>>
                        <?php echo __('Single Choice', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="multiple" <?php selected($settings['default_voting_type'] ?? '', 'multiple'); ?>>
                        <?php echo __('Multiple Choice', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="irv" <?php selected($settings['default_voting_type'] ?? '', 'irv'); ?>>
                        <?php echo __('Instant Runoff (IRV)', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="stv" <?php selected($settings['default_voting_type'] ?? '', 'stv'); ?>>
                        <?php echo __('Single Transferable Vote (STV)', 'wp-voting-plugin'); ?>
                    </option>
                </select>
                <p class="description"><?php echo __('Default voting mechanism for new votes. Specialized types (Coordinator Elections, Disciplinary, etc.) can be selected when creating individual votes.', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php echo __('Enable Public Votes', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[enable_public_votes]" value="1"
                        <?php checked($settings['enable_public_votes'] ?? false); ?>>
                    <?php echo __('Allow non-logged-in users to view public votes by default', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Default visibility setting. Can be overridden per vote.', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php echo __('Show Results Before Closing', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[show_results_before_closing]" value="1"
                        <?php checked($settings['show_results_before_closing'] ?? false); ?>>
                    <?php echo __('Allow viewing results before vote closes by default', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Default results visibility. Can be overridden per vote.', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php echo __('Email Notifications', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[enable_email_notifications]" value="1"
                        <?php checked($settings['enable_email_notifications'] ?? true); ?>>
                    <?php echo __('Send email notifications for vote events by default', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Default notification setting. Can be overridden per vote.', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php echo __('Keep Data on Uninstall', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_keep_data_on_uninstall" value="1"
                        <?php checked(get_option('wpvp_keep_data_on_uninstall', false)); ?>>
                    <?php echo __('Preserve votes and settings if plugin is deleted', 'wp-voting-plugin'); ?>
                </label>
            </td>
        </tr>
    </table>
<?php
}

/**
 * Permissions settings tab
 */
function wpvp_render_permissions_tab()
{
    settings_fields('wpvp_permissions');
    $mode = get_option('wpvp_permission_mode', 'wordpress');
    $capabilities = get_option('wpvp_capabilities', []);
?>

    <h3><?php echo __('Permission Mode', 'wp-voting-plugin'); ?></h3>

    <table class="form-table">
        <tr>
            <th scope="row"><?php echo __('How to Handle Voting Permissions', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="radio" name="wpvp_permission_mode" value="wordpress"
                        <?php checked($mode, 'wordpress'); ?>>
                    <?php echo __('WordPress Roles & Capabilities', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Use standard WordPress roles (Administrator, Editor, etc.)', 'wp-voting-plugin'); ?></p>

                <label>
                    <input type="radio" name="wpvp_permission_mode" value="accessschema"
                        <?php checked($mode, 'accessschema'); ?>>
                    <?php echo __('AccessSchema Integration', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Use AccessSchema for role-based permissions', 'wp-voting-plugin'); ?></p>

                <label>
                    <input type="radio" name="wpvp_permission_mode" value="custom"
                        <?php checked($mode, 'custom'); ?>>
                    <?php echo __('Custom User List', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php echo __('Manually select which users can vote', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>
    </table>

    <div id="wordpress-caps" style="<?php echo $mode === 'wordpress' ? '' : 'display:none;'; ?>">
        <h3><?php echo __('WordPress Capability Mapping', 'wp-voting-plugin'); ?></h3>

        <table class="form-table">
            <tr>
                <th scope="row"><?php echo __('Create Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <select name="wpvp_capabilities[create_votes]">
                        <option value="manage_options" <?php selected($capabilities['create_votes'] ?? '', 'manage_options'); ?>>
                            <?php echo __('Administrator', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_others_posts" <?php selected($capabilities['create_votes'] ?? '', 'edit_others_posts'); ?>>
                            <?php echo __('Editor', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_posts" <?php selected($capabilities['create_votes'] ?? 'edit_posts', 'edit_posts'); ?>>
                            <?php echo __('Author', 'wp-voting-plugin'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php echo __('Cast Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <select name="wpvp_capabilities[cast_votes]">
                        <option value="read" <?php selected($capabilities['cast_votes'] ?? 'read', 'read'); ?>>
                            <?php echo __('All Logged-in Users', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_posts" <?php selected($capabilities['cast_votes'] ?? '', 'edit_posts'); ?>>
                            <?php echo __('Author and above', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_others_posts" <?php selected($capabilities['cast_votes'] ?? '', 'edit_others_posts'); ?>>
                            <?php echo __('Editor and above', 'wp-voting-plugin'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <div id="custom-users" style="<?php echo $mode === 'custom' ? '' : 'display:none;'; ?>">
        <h3><?php echo __('Custom Voting Users', 'wp-voting-plugin'); ?></h3>

        <p><?php echo __('Select users who are allowed to vote:', 'wp-voting-plugin'); ?></p>

        <?php
        $can_vote_users = get_option('wpvp_can_vote_users', []);
        // Ensure it's an array
        if (!is_array($can_vote_users)) {
            $can_vote_users = [];
        }
        
        $all_users = get_users(['orderby' => 'display_name']);

        echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
        
        if (!empty($all_users)) {
            foreach ($all_users as $user) {
                $user_id = intval($user->ID);
                $checked = in_array($user_id, $can_vote_users, true) ? 'checked="checked"' : '';
                
                echo '<label style="display: block; margin-bottom: 5px;">';
                echo '<input type="checkbox" name="wpvp_can_vote_users[]" value="' . $user_id . '" ' . $checked . ' />';
                echo ' ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
                echo '</label>';
            }
        } else {
            echo '<p>' . __('No users found.', 'wp-voting-plugin') . '</p>';
        }
        
        echo '</div>';
        ?>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('input[name="wpvp_permission_mode"]').change(function() {
                $('#wordpress-caps, #custom-users').hide();

                if ($(this).val() === 'wordpress') {
                    $('#wordpress-caps').show();
                } else if ($(this).val() === 'custom') {
                    $('#custom-users').show();
                }
            });
        });
    </script>
<?php
}

/**
 * AJAX handler to test AccessSchema connection
 */
add_action('wp_ajax_wpvp_test_accessschema_connection', 'wpvp_test_accessschema_connection');
function wpvp_test_accessschema_connection() {
    check_ajax_referer('wpvp_test_connection', 'nonce');
    
    $url = sanitize_text_field($_POST['url']);
    $key = sanitize_text_field($_POST['key']);
    
    if (empty($url) || empty($key)) {
        wp_send_json_error('URL and API Key are required');
    }
    
    // Build the API URL
    $api_url = rtrim($url, '/') . '/wp-json/access-schema/v1/roles/all';
    
    // Make the API call
    $response = wp_remote_get($api_url, [
        'headers' => [
            'X-API-Key' => $key,
            'Accept' => 'application/json'
        ],
        'timeout' => 15,
        'sslverify' => false // Set to true in production
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        wp_send_json_error('API Error (' . $response_code . '): ' . $body);
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON response');
    }
    
    // Extract role paths
    $paths = [];
    if (isset($data['roles']) && is_array($data['roles'])) {
        foreach ($data['roles'] as $role) {
            if (isset($role['path'])) {
                $paths[] = $role['path'];
            }
        }
    }
    
    $total = count($paths);
    $display_paths = array_slice($paths, 0, 5); // Show only first 5
    $display_text = implode(', ', $display_paths);
    
    if ($total > 5) {
        $display_text .= sprintf(' ... and %d more', $total - 5);
    }
    
    $message = sprintf('Connected! Found %d roles: %s', $total, $display_text);
    wp_send_json_success(['message' => $message, 'paths' => $paths, 'total' => $total]);
}

/**
 * AccessSchema settings tab - Main function
 */
function wpvp_render_accessschema_tab()
{
    settings_fields('wpvp_accessschema');
    
    // Get settings
    $settings = wpvp_get_accessschema_settings();
    
    // Render sections
    wpvp_render_accessschema_connection_settings($settings);
    
    if ($settings['mode'] !== 'none') {
        wpvp_render_accessschema_role_settings($settings);
    }
    
    // Add scripts and styles
    wpvp_add_accessschema_scripts($settings);
}

/**
 * Get AccessSchema settings
 */
function wpvp_get_accessschema_settings() {
    $prefix = 'wpvp';
    return [
        'prefix' => $prefix,
        'mode' => get_option("{$prefix}_accessschema_mode", 'none'),
        'url' => get_option("{$prefix}_accessschema_client_url", ''),
        'key' => get_option("{$prefix}_accessschema_client_key", ''),
        'capability_map' => get_option("{$prefix}_capability_map", [])
    ];
}

/**
 * Render connection settings section
 */
function wpvp_render_accessschema_connection_settings($settings) {
    $prefix = $settings['prefix'];
    $mode = $settings['mode'];
    $url = $settings['url'];
    $key = $settings['key'];
    ?>
    <h3><?php echo __('AccessSchema Configuration', 'wp-voting-plugin'); ?></h3>

    <table class="form-table">
        <tr>
            <th scope="row"><?php echo __('Connection Mode', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="none"
                        <?php checked($mode, 'none'); ?>>
                    <?php echo __('None - Use WordPress Permissions', 'wp-voting-plugin'); ?>
                </label><br>

                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="remote"
                        <?php checked($mode, 'remote'); ?>>
                    <?php echo __('Remote - Connect to external AccessSchema server', 'wp-voting-plugin'); ?>
                </label><br>

                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="local"
                        <?php checked($mode, 'local'); ?>>
                    <?php echo __('Local - Use local AccessSchema installation', 'wp-voting-plugin'); ?>
                </label>
            </td>
        </tr>

        <tr class="accessschema-remote" style="<?php echo $mode === 'remote' ? '' : 'display:none;'; ?>">
            <th scope="row"><?php echo __('Remote URL', 'wp-voting-plugin'); ?></th>
            <td>
                <input type="url" name="<?php echo $prefix; ?>_accessschema_client_url"
                    value="<?php echo esc_url($url); ?>" class="regular-text">
                <p class="description"><?php echo __('Full URL to your AccessSchema server', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr class="accessschema-remote" style="<?php echo $mode === 'remote' ? '' : 'display:none;'; ?>">
            <th scope="row"><?php echo __('API Key', 'wp-voting-plugin'); ?></th>
            <td>
                <input type="text" name="<?php echo $prefix; ?>_accessschema_client_key"
                    value="<?php echo esc_attr($key); ?>" class="regular-text">
                <p class="description"><?php echo __('Your AccessSchema API key', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <?php if ($mode === 'remote'): ?>
        <tr class="accessschema-remote">
            <th scope="row"><?php echo __('Test Connection', 'wp-voting-plugin'); ?></th>
            <td>
                <button type="button" id="test-accessschema-connection" class="button">
                    <?php echo __('Test Connection', 'wp-voting-plugin'); ?>
                </button>
                <span id="test-result" style="margin-left: 10px;"></span>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
}

/**
 * Render role settings section
 */
function wpvp_render_accessschema_role_settings($settings) {
    $prefix = $settings['prefix'];
    $capability_map = $settings['capability_map'];
    
    // Handle array values properly
    $create_votes = isset($capability_map['create_votes']) ? $capability_map['create_votes'] : ['Coordinator/$slug/Coordinator'];
    $cast_votes = isset($capability_map['cast_votes']) ? $capability_map['cast_votes'] : ['Chronicle/$slug/*'];
    
    // Ensure arrays
    if (!is_array($create_votes)) {
        $create_votes = [$create_votes];
    }
    if (!is_array($cast_votes)) {
        $cast_votes = [$cast_votes];
    }
    ?>
    <h3><?php echo __('Voting Role Paths', 'wp-voting-plugin'); ?></h3>
    <p><?php echo __('Configure which AccessSchema roles can perform voting actions:', 'wp-voting-plugin'); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row"><?php echo __('Create Votes', 'wp-voting-plugin'); ?></th>
            <td>
                <select name="<?php echo $prefix; ?>_capability_map[create_votes][]" 
                        multiple="multiple" 
                        class="wpvp-role-select" 
                        data-capability="create_votes"
                        style="width: 100%;">
                    <?php foreach ($create_votes as $role): ?>
                        <option value="<?php echo esc_attr($role); ?>" selected="selected">
                            <?php echo esc_html($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo __('AccessSchema role paths for creating votes', 'wp-voting-plugin'); ?><br>
                    <?php echo __('Use * for single-level wildcard, ** for multi-level wildcard', 'wp-voting-plugin'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php echo __('Cast Votes (Default)', 'wp-voting-plugin'); ?></th>
            <td>
                <select name="<?php echo $prefix; ?>_capability_map[cast_votes][]" 
                        multiple="multiple" 
                        class="wpvp-role-select" 
                        data-capability="cast_votes"
                        style="width: 100%;">
                    <?php foreach ($cast_votes as $role): ?>
                        <option value="<?php echo esc_attr($role); ?>" selected="selected">
                            <?php echo esc_html($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo __('Default roles allowed to cast votes. Can be overridden per vote.', 'wp-voting-plugin'); ?><br>
                    <?php echo __('Examples: Chronicle/$slug/*, Chronicle/KONY/CM, Storyteller/**', 'wp-voting-plugin'); ?>
                </p>
            </td>
        </tr>
    </table>

    <div class="notice notice-info inline">
        <p>
            <strong><?php echo __('Pattern Matching:', 'wp-voting-plugin'); ?></strong><br>
            • <code>*</code> = <?php echo __('Single level wildcard (matches one path segment)', 'wp-voting-plugin'); ?><br>
            • <code>**</code> = <?php echo __('Multi-level wildcard (matches multiple path segments)', 'wp-voting-plugin'); ?><br>
            • <code>$slug</code> = <?php echo __('Dynamic slug replacement (if configured)', 'wp-voting-plugin'); ?>
        </p>
    </div>
    <?php
}

/**
 * Add AccessSchema scripts and styles
 */
function wpvp_add_accessschema_scripts($settings) {
    $prefix = $settings['prefix'];
    $mode = $settings['mode'];
    
    // Enqueue select2 if needed
    if ($mode !== 'none') {
        wp_enqueue_script('select2', plugins_url('includes/assets/js/select2.min.js', dirname(dirname(__FILE__))), ['jquery'], '4.0.13', true);
        wp_enqueue_style('select2', plugins_url('includes/assets/css/select2.min.css', dirname(dirname(__FILE__))), [], '4.0.13');
        
        // Add nonce for AJAX
        wp_localize_script('select2', 'wpvp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpvp_admin_nonce'),
            'test_nonce' => wp_create_nonce('wpvp_test_connection')
        ]);
    }
    ?>
    <script>
        jQuery(document).ready(function($) {
            // Show/hide remote settings
            $('input[name="<?php echo $prefix; ?>_accessschema_mode"]').change(function() {
                if ($(this).val() === 'remote') {
                    $('.accessschema-remote').show();
                } else {
                    $('.accessschema-remote').hide();
                }
            });

            <?php if ($mode !== 'none'): ?>
            var availableRoles = [];
            var rolesLoaded = false;
            
            // Initialize select2 with dynamic loading
            $('.wpvp-role-select').select2({
                tags: true,
                placeholder: 'Select or enter role paths...',
                allowClear: true,
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;
                    
                    // Check if this exact term already exists
                    var found = false;
                    availableRoles.forEach(function(role) {
                        if (role.id === term) {
                            found = true;
                        }
                    });
                    
                    return {
                        id: term,
                        text: term + (found ? '' : ' (custom pattern)'),
                        isNew: true
                    };
                }
            });
            
            // Load roles when select2 is opened
            $('.wpvp-role-select').on('select2:open', function() {
                if (!rolesLoaded && '<?php echo $mode; ?>' === 'remote') {
                    loadAccessSchemaRoles();
                }
            });
            
            // Function to load roles from AccessSchema
            function loadAccessSchemaRoles() {
                $.ajax({
                    url: wpvp_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpvp_fetch_accessschema_roles',
                        nonce: wpvp_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            availableRoles = response.data;
                            rolesLoaded = true;
                            
                            // Add loaded roles as available options
                            $('.wpvp-role-select').each(function() {
                                var $select = $(this);
                                var currentValues = $select.val() || [];
                                
                                // Add new options that aren't already selected
                                availableRoles.forEach(function(role) {
                                    if (currentValues.indexOf(role.id) === -1) {
                                        var option = new Option(role.text, role.id, false, false);
                                        $select.append(option);
                                    }
                                });
                                
                                $select.trigger('change');
                            });
                        }
                    }
                });
            }
            <?php endif; ?>

            // Test connection button
            $('#test-accessschema-connection').click(function() {
                var button = $(this);
                var resultSpan = $('#test-result');
                
                button.prop('disabled', true);
                resultSpan.html('<span class="spinner is-active" style="float: none;"></span>');
                
                $.ajax({
                    url: wpvp_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpvp_test_accessschema_connection',
                        nonce: wpvp_ajax.test_nonce,
                        url: $('input[name="<?php echo $prefix; ?>_accessschema_client_url"]').val(),
                        key: $('input[name="<?php echo $prefix; ?>_accessschema_client_key"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            resultSpan.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                            
                            // Reset loaded roles when connection settings change
                            rolesLoaded = false;
                            availableRoles = [];
                        } else {
                            resultSpan.html('<span style="color: red;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        resultSpan.html('<span style="color: red;">✗ Connection failed</span>');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>

    <style>
        .notice.inline {
            margin: 15px 0;
        }
        .spinner {
            visibility: visible;
        }
        .wpvp-role-select + .select2-container {
            min-width: 400px;
        }
        .select2-container--default .select2-selection--multiple {
            min-height: 60px;
        }
        .select2-results__option[aria-selected] .is-new {
            font-style: italic;
            color: #666;
        }
    </style>
    <?php
}

/**
 * AJAX handler to fetch AccessSchema roles for select2
 */
add_action('wp_ajax_wpvp_fetch_accessschema_roles', 'wpvp_fetch_accessschema_roles');
function wpvp_fetch_accessschema_roles() {
    check_ajax_referer('wpvp_admin_nonce', 'nonce');
    
    $prefix = 'wpvp';
    $mode = get_option("{$prefix}_accessschema_mode", 'none');
    $url = get_option("{$prefix}_accessschema_client_url", '');
    $key = get_option("{$prefix}_accessschema_client_key", '');
    
    if ($mode === 'none' || empty($url) || empty($key)) {
        wp_send_json_error('AccessSchema not configured');
    }
    
    // Build the API URL
    $api_url = rtrim($url, '/') . '/wp-json/access-schema/v1/roles/all';
    
    // Make the API call
    $response = wp_remote_get($api_url, [
        'headers' => [
            'X-API-Key' => $key,
            'Accept' => 'application/json'
        ],
        'timeout' => 15,
        'sslverify' => false // Set to true in production
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        wp_send_json_error('API Error (' . $response_code . '): ' . $body);
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON response');
    }
    
    // Format for select2
    $formatted_roles = [];
    
    if (isset($data['roles']) && is_array($data['roles'])) {
        foreach ($data['roles'] as $role) {
            if (isset($role['path'])) {
                $formatted_roles[] = [
                    'id' => $role['path'],
                    'text' => $role['path']  // Just show the path
                ];
            }
        }
    }
    
    // Add common wildcard patterns as suggestions
    $formatted_roles[] = ['id' => 'chronicle/*/cm', 'text' => 'chronicle/*/cm'];
    $formatted_roles[] = ['id' => 'coordinator/*/coordinator', 'text' => 'coordinator/*/coordinator'];
    $formatted_roles[] = ['id' => 'exec/*/coordinator', 'text' => 'exec/*/coordinator'];
    
    wp_send_json_success($formatted_roles);
}