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
});

// In your admin settings
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

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Create Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <input type="text" name="<?php echo $prefix; ?>_capability_map[create_votes]"
                        value="<?php echo esc_attr(get_option("{$prefix}_capability_map")['create_votes'] ?? 'Coordinator/$slug/Coordinator'); ?>"
                        class="regular-text">
                    <p class="description"><?php _e('Example: Coordinator/$slug/Coordinator', 'wp-voting-plugin'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Cast Votes', 'wp-voting-plugin'); ?></th>
                <td>
                    <input type="text" name="<?php echo $prefix; ?>_capability_map[cast_votes]"
                        value="<?php echo esc_attr(get_option("{$prefix}_capability_map")['cast_votes'] ?? 'Chronicle/$slug/*'); ?>"
                        class="regular-text">
                    <p class="description"><?php _e('Example: Chronicle/$slug/* (wildcard for all roles)', 'wp-voting-plugin'); ?></p>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <script>
        jQuery(document).ready(function($) {
            $('input[name="<?php echo $prefix; ?>_accessschema_mode"]').change(function() {
                if ($(this).val() === 'remote') {
                    $('.accessschema-remote').show();
                } else {
                    $('.accessschema-remote').hide();
                }
            });
        });
    </script>
<?php
}
