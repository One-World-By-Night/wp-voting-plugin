<?php
/**
 * Plugin Name: WP Voting Plugin
 * Plugin URI:  https://github.com/One-World-By-Night/wp-voting-plugin
 * Description: A voting system supporting multiple algorithms (FPTP, RCV, STV, Condorcet, Disciplinary) with AccessSchema role-based permissions and WordPress capability fallback.
 * Version:     2.8.1
 * Author:      One World By Night
 * Author URI:  https://www.owbn.net
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-voting-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WPVP_VERSION', '2.8.1' );
define( 'WPVP_PLUGIN_FILE', __FILE__ );
define( 'WPVP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPVP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// PHP version check.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			printf(
			/* translators: %s: required PHP version */
				esc_html__( 'WP Voting Plugin requires PHP %s or higher.', 'wp-voting-plugin' ),
				'7.4'
			);
			echo '</p></div>';
		}
	);
	return;
}

// Load the plugin.
require_once WPVP_PLUGIN_DIR . 'includes/class-plugin.php';

// Activation and deactivation hooks (must be in main file).
register_activation_hook( __FILE__, array( 'WPVP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPVP_Activator', 'deactivate' ) );

// Boot the plugin.
WPVP_Plugin::instance();
