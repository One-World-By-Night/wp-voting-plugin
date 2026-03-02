<?php
/**
 * Fired when the plugin is uninstalled via the WordPress admin.
 *
 * Only runs if the user opted to remove data in Settings → Advanced.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! get_option( 'wpvp_remove_data_on_uninstall', false ) ) {
	return;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
WPVP_Database::drop_tables();

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

delete_transient( 'wpvp_accessschema_roles' );

$page_ids = get_option( 'wpvp_page_ids', array() );
foreach ( $page_ids as $page_id ) {
	wp_delete_post( $page_id, true );
}

$caps = array( 'wpvp_create_votes', 'wpvp_manage_votes', 'wpvp_cast_votes', 'wpvp_view_results' );

foreach ( wp_roles()->roles as $role_name => $role_info ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}
