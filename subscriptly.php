<?php
/**
 * Plugin Name:       Subscriptly
 * Plugin URI:        https://wordpress.org/plugins/subscriptly/
 * Description:       Enterprise-grade WooCommerce subscription management with manual renewals, lifecycle controls, and extensible architecture.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Akram Ul Haq
 * Author URI:        https://github.com/akramg478
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       subscriptly
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package Subscriptly
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'SUBSCRIPTLY_CORE_LOADED' ) ) {
	return;
}

define( 'SUBSCRIPTLY_VERSION', '1.0.0' );
define( 'SUBSCRIPTLY_PLUGIN_FILE', __FILE__ );
define( 'SUBSCRIPTLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SUBSCRIPTLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SUBSCRIPTLY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$subscriptly_autoloader = SUBSCRIPTLY_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! is_readable( $subscriptly_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'Subscriptly:', 'subscriptly' ),
				esc_html__(
					'Composer autoload files are missing. Run "composer install" in the plugin directory.',
					'subscriptly'
				)
			);
		}
	);

	return;
}

require $subscriptly_autoloader;

register_activation_hook(
	__FILE__,
	static function (): void {
		\Subscriptly\Activation\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\Subscriptly\Activation\Deactivator::deactivate();
	}
);

\Subscriptly\Application::boot( 'free' );
