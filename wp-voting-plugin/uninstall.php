<?php
/**
 * Fired when the plugin is uninstalled via the WordPress admin.
 *
 * Only runs if the user opted to remove data in Settings → Advanced.
 */

// Safety: only run through WordPress uninstall mechanism.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check whether the admin chose to remove all data.
if ( ! get_option( 'wpvp_remove_data_on_uninstall', false ) ) {
	return;
}

// Drop custom tables.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
WPVP_Database::drop_tables();

// Remove plugin options.
$options = array(
	'wpvp_db_version',
	'wpvp_default_voting_type',
	'wpvp_require_login',
	'wpvp_show_results_before_close',
	'wpvp_enable_email_notifications',
	'wpvp_remove_data_on_uninstall',
	'wpvp_timezone',
	'wpvp_accessschema_mode',
	'wpvp_accessschema_client_url',
	'wpvp_accessschema_client_key',
	'wpvp_capability_map',
	'wpvp_wp_capabilities',
	'wpvp_page_ids',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove transients.
delete_transient( 'wpvp_accessschema_roles' );

// Remove pages created by the plugin.
$page_ids = get_option( 'wpvp_page_ids', array() );
foreach ( $page_ids as $page_id ) {
	wp_delete_post( $page_id, true );
}

// Remove custom capabilities from all roles.
$caps = array( 'wpvp_create_votes', 'wpvp_manage_votes', 'wpvp_cast_votes', 'wpvp_view_results' );

foreach ( wp_roles()->roles as $role_name => $role_info ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}
