<?php
/**
 * Plugin deactivation handler.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Activation;

/**
 * Runs plugin deactivation routines.
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'subscriptly_process_renewal', array(), 'subscriptly' );
			as_unschedule_all_actions( 'subscriptly_check_due_renewals', array(), 'subscriptly' );
			as_unschedule_all_actions( 'subscriptly_check_trial_endings', array(), 'subscriptly' );
		}

		flush_rewrite_rules();
	}
}
