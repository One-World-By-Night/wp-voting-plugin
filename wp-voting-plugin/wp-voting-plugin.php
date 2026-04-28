<?php
/**
 * Plugin Name: WP Voting Plugin
 * Plugin URI:  https://github.com/One-World-By-Night/wp-voting-plugin
 * Description: A voting system supporting multiple algorithms (FPTP, RCV, STV, Condorcet, Disciplinary) with AccessSchema role-based permissions and WordPress capability fallback.
 * Version:     3.13.3
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

define( 'WPVP_VERSION', '3.13.3' );
define( 'WPVP_PLUGIN_FILE', __FILE__ );
define( 'WPVP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPVP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPVP_ABSTAIN_LABEL', 'Abstain' );

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

require_once WPVP_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'WPVP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPVP_Activator', 'deactivate' ) );

WPVP_Plugin::instance();
