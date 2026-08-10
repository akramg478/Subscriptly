<?php
/**
 * Plugin activation handler.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Activation;

use Subscriptly\Database\Migrator;
use Subscriptly\Requirements\RequirementsChecker;

/**
 * Runs plugin activation routines.
 */
final class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$requirements = new RequirementsChecker();

		if ( ! $requirements->are_met() ) {
			deactivate_plugins( SUBSCRIPTLY_PLUGIN_BASENAME );

			wp_die(
				wp_kses_post(
					implode(
						'<br />',
						array_map(
							static fn( string $error ): string => esc_html( $error ),
							$requirements->get_errors()
						)
					)
				),
				esc_html__( 'Subscriptly Activation Error', 'subscriptly' ),
				array( 'back_link' => true )
			);
		}

		( new Migrator() )->install();

		add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
}
