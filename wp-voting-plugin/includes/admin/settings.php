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
    // Main menu
    add_menu_page(
        __('WP Voting', 'wp-voting-plugin'),
        __('WP Voting', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-settings',
        'wpvp_render_settings_page',
        'dashicons-chart-pie',
        30
    );

    // Settings submenu
    add_submenu_page(
        'wpvp-settings',
        __('Settings', 'wp-voting-plugin'),
        __('Settings', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-settings',
        'wpvp_render_settings_page'
    );

    // All Votes submenu
    add_submenu_page(
        'wpvp-settings',
        __('All Votes', 'wp-voting-plugin'),
        __('All Votes', 'wp-voting-plugin'),
        'manage_options',
        'wpvp-all-votes',
        'wpvp_render_all_votes_page'
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
            
            // Ensure create_votes and cast_votes are arrays
            foreach (['create_votes', 'cast_votes'] as $key) {
                if (isset($input[$key]) && !is_array($input[$key])) {
                    $input[$key] = [$input[$key]];
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
            <th scope="row"><?php _e('Enable Public Votes', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[enable_public_votes]" value="1"
                        <?php checked($settings['enable_public_votes'] ?? false); ?>>
                    <?php _e('Allow non-logged-in users to view public votes', 'wp-voting-plugin'); ?>
                </label>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Default Voting Type', 'wp-voting-plugin'); ?></th>
            <td>
                <select name="wpvp_settings[default_voting_type]">
                    <option value="single" <?php selected($settings['default_voting_type'] ?? 'single', 'single'); ?>>
                        <?php _e('Single Choice', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="irv" <?php selected($settings['default_voting_type'] ?? '', 'irv'); ?>>
                        <?php _e('Instant Runoff (IRV)', 'wp-voting-plugin'); ?>
                    </option>
                    <option value="stv" <?php selected($settings['default_voting_type'] ?? '', 'stv'); ?>>
                        <?php _e('Single Transferable Vote (STV)', 'wp-voting-plugin'); ?>
                    </option>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Show Results Before Closing', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[show_results_before_closing]" value="1"
                        <?php checked($settings['show_results_before_closing'] ?? false); ?>>
                    <?php _e('Allow viewing results before vote closes', 'wp-voting-plugin'); ?>
                </label>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Email Notifications', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="wpvp_settings[enable_email_notifications]" value="1"
                        <?php checked($settings['enable_email_notifications'] ?? true); ?>>
                    <?php _e('Send email notifications for vote events', 'wp-voting-plugin'); ?>
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

    <h3><?php _e('Permission Mode', 'wp-voting-plugin'); ?></h3>

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('How to Handle Voting Permissions', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="radio" name="wpvp_permission_mode" value="wordpress"
                        <?php checked($mode, 'wordpress'); ?>>
                    <?php _e('WordPress Roles & Capabilities', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php _e('Use standard WordPress roles (Administrator, Editor, etc.)', 'wp-voting-plugin'); ?></p>

                <label>
                    <input type="radio" name="wpvp_permission_mode" value="accessschema"
                        <?php checked($mode, 'accessschema'); ?>>
                    <?php _e('AccessSchema Integration', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php _e('Use AccessSchema for role-based permissions', 'wp-voting-plugin'); ?></p>

                <label>
                    <input type="radio" name="wpvp_permission_mode" value="custom"
                        <?php checked($mode, 'custom'); ?>>
                    <?php _e('Custom User List', 'wp-voting-plugin'); ?>
                </label>
                <p class="description"><?php _e('Manually select which users can vote', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>
    </table>

    <div id="wordpress-caps" style="<?php echo $mode === 'wordpress' ? '' : 'display:none;'; ?>">
        <h3><?php _e('WordPress Capability Mapping', 'wp-voting-plugin'); ?></h3>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Create Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <select name="wpvp_capabilities[create_votes]">
                        <option value="manage_options" <?php selected($capabilities['create_votes'] ?? '', 'manage_options'); ?>>
                            <?php _e('Administrator', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_others_posts" <?php selected($capabilities['create_votes'] ?? '', 'edit_others_posts'); ?>>
                            <?php _e('Editor', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_posts" <?php selected($capabilities['create_votes'] ?? 'edit_posts', 'edit_posts'); ?>>
                            <?php _e('Author', 'wp-voting-plugin'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Cast Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <select name="wpvp_capabilities[cast_votes]">
                        <option value="read" <?php selected($capabilities['cast_votes'] ?? 'read', 'read'); ?>>
                            <?php _e('All Logged-in Users', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_posts" <?php selected($capabilities['cast_votes'] ?? '', 'edit_posts'); ?>>
                            <?php _e('Author and above', 'wp-voting-plugin'); ?>
                        </option>
                        <option value="edit_others_posts" <?php selected($capabilities['cast_votes'] ?? '', 'edit_others_posts'); ?>>
                            <?php _e('Editor and above', 'wp-voting-plugin'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <div id="custom-users" style="<?php echo $mode === 'custom' ? '' : 'display:none;'; ?>">
        <h3><?php _e('Custom Voting Users', 'wp-voting-plugin'); ?></h3>

        <p><?php _e('Select users who are allowed to vote:', 'wp-voting-plugin'); ?></p>

        <?php
        $can_vote_users = get_option('wpvp_can_vote_users', []);
        $all_users = get_users(['orderby' => 'display_name']);

        echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
        foreach ($all_users as $user) {
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="wpvp_can_vote_users[]" value="' . $user->ID . '" ';
            echo in_array($user->ID, $can_vote_users) ? 'checked' : '';
            echo '> ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
            echo '</label>';
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
 * AccessSchema settings tab
 */
function wpvp_render_accessschema_tab()
{
    settings_fields('wpvp_accessschema');
    $prefix = 'wpvp';
    $mode = get_option("{$prefix}_accessschema_mode", 'none');
    $url = get_option("{$prefix}_accessschema_client_url", '');
    $key = get_option("{$prefix}_accessschema_client_key", '');
    
    // Enqueue select2 assets
    wp_enqueue_script('select2', plugins_url('includes/assets/js/select2.min.js', dirname(dirname(__FILE__))), ['jquery'], '4.0.13', true);
    wp_enqueue_style('select2', plugins_url('includes/assets/css/select2.min.css', dirname(dirname(__FILE__))), [], '4.0.13');
    
    // Add nonce for AJAX
    wp_localize_script('select2', 'wpvp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpvp_admin_nonce')
    ]);
?>

    <h3><?php _e('AccessSchema Configuration', 'wp-voting-plugin'); ?></h3>

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Connection Mode', 'wp-voting-plugin'); ?></th>
            <td>
                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="none"
                        <?php checked($mode, 'none'); ?>>
                    <?php _e('None - Use WordPress Permissions', 'wp-voting-plugin'); ?>
                </label><br>

                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="remote"
                        <?php checked($mode, 'remote'); ?>>
                    <?php _e('Remote - Connect to external AccessSchema server', 'wp-voting-plugin'); ?>
                </label><br>

                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_accessschema_mode" value="local"
                        <?php checked($mode, 'local'); ?>>
                    <?php _e('Local - Use local AccessSchema installation', 'wp-voting-plugin'); ?>
                </label>
            </td>
        </tr>

        <tr class="accessschema-remote" style="<?php echo $mode === 'remote' ? '' : 'display:none;'; ?>">
            <th scope="row"><?php _e('Remote URL', 'wp-voting-plugin'); ?></th>
            <td>
                <input type="url" name="<?php echo $prefix; ?>_accessschema_client_url"
                    value="<?php echo esc_url($url); ?>" class="regular-text">
                <p class="description"><?php _e('Full URL to your AccessSchema server', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>

        <tr class="accessschema-remote" style="<?php echo $mode === 'remote' ? '' : 'display:none;'; ?>">
            <th scope="row"><?php _e('API Key', 'wp-voting-plugin'); ?></th>
            <td>
                <input type="text" name="<?php echo $prefix; ?>_accessschema_client_key"
                    value="<?php echo esc_attr($key); ?>" class="regular-text">
                <p class="description"><?php _e('Your AccessSchema API key', 'wp-voting-plugin'); ?></p>
            </td>
        </tr>
    </table>

    <?php if ($mode !== 'none'): ?>
        <h3><?php _e('Voting Role Paths', 'wp-voting-plugin'); ?></h3>
        <p><?php _e('Configure which AccessSchema roles can perform voting actions:', 'wp-voting-plugin'); ?></p>

        <?php
        $capability_map = get_option("{$prefix}_capability_map", []);
        $create_votes = isset($capability_map['create_votes']) ? (array)$capability_map['create_votes'] : ['Coordinator/$slug/Coordinator'];
        $cast_votes = isset($capability_map['cast_votes']) ? (array)$capability_map['cast_votes'] : ['Chronicle/$slug/*'];
        ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Create Votes', 'wp-voting-plugin'); ?></th>
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
                        <?php _e('Start typing to search AccessSchema roles or enter custom patterns', 'wp-voting-plugin'); ?><br>
                        <?php _e('Use * for single-level wildcard, ** for multi-level wildcard', 'wp-voting-plugin'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Cast Votes (Default)', 'wp-voting-plugin'); ?></th>
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
                        <?php _e('Default roles allowed to cast votes. Can be overridden per vote.', 'wp-voting-plugin'); ?><br>
                        <?php _e('Search existing roles or add patterns like Chronicle/KONY/CM, Storyteller/**', 'wp-voting-plugin'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="wpvp-loading-roles" style="display:none;">
            <span class="spinner is-active" style="float:none;"></span>
            <?php _e('Loading AccessSchema roles...', 'wp-voting-plugin'); ?>
        </div>

        <div class="notice notice-info inline">
            <p>
                <strong><?php _e('Pattern Matching:', 'wp-voting-plugin'); ?></strong><br>
                • <code>*</code> = Single level wildcard (matches one path segment)<br>
                • <code>**</code> = Multi-level wildcard (matches multiple path segments)<br>
                • <code>$slug</code> = Dynamic slug replacement (if configured)
            </p>
        </div>
    <?php endif; ?>

    <script>
        jQuery(document).ready(function($) {
            var availableRoles = [];
            var rolesLoaded = false;
            
            // Initialize select2 for role selects
            $('.wpvp-role-select').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: 'Search roles or enter custom patterns...',
                allowClear: true,
                minimumInputLength: 0,
                
                // Custom matcher to search in loaded roles
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    
                    var term = params.term.toLowerCase();
                    var text = data.text.toLowerCase();
                    
                    if (text.indexOf(term) > -1) {
                        return data;
                    }
                    
                    return null;
                },
                
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
                if (!rolesLoaded && '<?php echo $mode; ?>' !== 'none') {
                    loadAccessSchemaRoles();
                }
            });
            
            // Function to load roles from AccessSchema
            function loadAccessSchemaRoles() {
                $('.wpvp-loading-roles').show();
                
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
                    },
                    complete: function() {
                        $('.wpvp-loading-roles').hide();
                    }
                });
            }
            
            // Show/hide remote settings
            $('input[name="<?php echo $prefix; ?>_accessschema_mode"]').change(function() {
                if ($(this).val() === 'remote') {
                    $('.accessschema-remote').show();
                } else {
                    $('.accessschema-remote').hide();
                }
                
                // Reset loaded roles when mode changes
                rolesLoaded = false;
                availableRoles = [];
            });
        });
    </script>

    <style>
        .wpvp-role-select + .select2-container {
            min-width: 400px;
        }
        .select2-container--default .select2-selection--multiple {
            min-height: 60px;
        }
        .notice.inline {
            margin: 15px 0;
        }
        .wpvp-loading-roles {
            margin: 10px 0;
        }
        .select2-results__option--highlighted[aria-selected] .is-new {
            font-style: italic;
        }
        .spinner {
            visibility: visible;
            float: left;
            margin: 0 10px 0 0;
        }
    </style>
<?php
}

/**
 * AJAX handler to fetch AccessSchema roles
 */
add_action('wp_ajax_wpvp_fetch_accessschema_roles', 'wpvp_fetch_accessschema_roles');
function wpvp_fetch_accessschema_roles() {
    check_ajax_referer('wpvp_admin_nonce', 'nonce');
    
    $prefix = 'wpvp';
    $mode = get_option("{$prefix}_accessschema_mode", 'none');
    
    if ($mode === 'none') {
        wp_send_json_error('AccessSchema not configured');
    }
    
    // Debug: Log the mode
    error_log('AccessSchema Mode: ' . $mode);
    
    // Get AccessSchema client
    $client = wpvp_get_accessschema_client();
    
    if (!$client) {
        wp_send_json_error('Could not connect to AccessSchema');
    }
    
    try {
        // Call the roles/all endpoint
        $response = $client->roles->all(); // or $client->get('/roles/all') depending on your client
        
        // Debug: Log the raw response
        error_log('AccessSchema Response: ' . print_r($response, true));
        
        // Format for select2
        $formatted_roles = [];
        
        // Handle different response formats
        if (is_object($response) && isset($response->data)) {
            $roles = $response->data;
        } elseif (is_array($response)) {
            $roles = $response;
        } else {
            $roles = [];
        }
        
        // Debug: Log the roles array
        error_log('Roles array: ' . print_r($roles, true));
        
        if (is_array($roles)) {
            foreach ($roles as $role) {
                // Handle different role formats
                $role_path = '';
                $role_name = '';
                
                if (is_array($role)) {
                    $role_path = $role['path'] ?? $role['name'] ?? $role['role'] ?? '';
                    $role_name = $role['display_name'] ?? $role['description'] ?? '';
                } elseif (is_object($role)) {
                    $role_path = $role->path ?? $role->name ?? $role->role ?? '';
                    $role_name = $role->display_name ?? $role->description ?? '';
                } elseif (is_string($role)) {
                    $role_path = $role;
                    $role_name = '';
                }
                
                if (!empty($role_path)) {
                    $formatted_roles[] = [
                        'id' => $role_path,
                        'text' => $role_path . ($role_name ? ' - ' . $role_name : '')
                    ];
                }
            }
        }
        
        // Add common wildcard patterns as suggestions
        $formatted_roles[] = ['id' => 'Chronicle/$slug/*', 'text' => 'Chronicle/$slug/* (All chronicle roles)'];
        $formatted_roles[] = ['id' => 'Coordinator/$slug/*', 'text' => 'Coordinator/$slug/* (All coordinator roles)'];
        $formatted_roles[] = ['id' => 'Exec/**', 'text' => 'Exec/** (All executive roles)'];
        $formatted_roles[] = ['id' => 'Storyteller/**', 'text' => 'Storyteller/** (All storyteller roles)'];
        
        // Debug: Log formatted roles
        error_log('Formatted roles: ' . print_r($formatted_roles, true));
        
        wp_send_json_success($formatted_roles);
        
    } catch (Exception $e) {
        error_log('AccessSchema Error: ' . $e->getMessage());
        wp_send_json_error('Failed to fetch roles: ' . $e->getMessage());
    }
}

/**
 * Get AccessSchema client instance
 */
function wpvp_get_accessschema_client() {
    $prefix = 'wpvp';
    $mode = get_option("{$prefix}_accessschema_mode", 'none');
    
    if ($mode === 'local' && class_exists('AccessSchemaClient')) {
        // Use local installation
        return new AccessSchemaClient();
    } elseif ($mode === 'remote') {
        // Use remote API
        $url = get_option("{$prefix}_accessschema_client_url", '');
        $key = get_option("{$prefix}_accessschema_client_key", '');
        
        error_log('AccessSchema URL: ' . $url);
        error_log('AccessSchema Key exists: ' . (!empty($key) ? 'Yes' : 'No'));
        
        if (empty($url) || empty($key)) {
            return false;
        }
        
        // Check if AccessSchemaClient is available
        if (class_exists('AccessSchemaClient')) {
            try {
                return new AccessSchemaClient($url, $key);
            } catch (Exception $e) {
                error_log('Failed to create AccessSchemaClient: ' . $e->getMessage());
                return false;
            }
        }
        
        // Fallback: Use WordPress HTTP API
        return new WPVPAccessSchemaAPIWrapper($url, $key);
    }
    
    return false;
}

/**
 * Simple API wrapper using WordPress HTTP API
 */
class WPVPAccessSchemaAPIWrapper {
    private $url;
    private $key;
    
    public function __construct($url, $key) {
        $this->url = rtrim($url, '/');
        $this->key = $key;
    }
    
    public function get($endpoint) {
        $response = wp_remote_get($this->url . '/' . ltrim($endpoint, '/'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Accept' => 'application/json'
            ],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }
        
        return $data;
    }
    
    public function __get($name) {
        if ($name === 'roles') {
            return new class($this) {
                private $api;
                public function __construct($api) {
                    $this->api = $api;
                }
                public function all() {
                    return $this->api->get('/roles/all');
                }
            };
        }
    }
}