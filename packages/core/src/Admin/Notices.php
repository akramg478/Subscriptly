<?php
/**
 * Admin notices handler.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Admin;

use Subscriptly\Requirements\RequirementsChecker;

/**
 * Registers admin-facing notices.
 */
final class Notices {

	/**
	 * Register requirement failure notices for administrators.
	 *
	 * @param RequirementsChecker $requirements Requirements checker.
	 * @return void
	 */
	public function register_requirement_notices( RequirementsChecker $requirements ): void {
		add_action(
			'admin_notices',
			static function () use ( $requirements ): void {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				foreach ( $requirements->get_errors() as $error ) {
					printf(
						'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
						esc_html__( 'Subscriptly:', 'subscriptly' ),
						esc_html( $error )
					);
				}
			}
		);
	}

	/**
	 * Register a dismissible success notice.
	 *
	 * @param string $message Notice message.
	 * @param string $notice_class Notice class.
	 * @return void
	 */
	public function add_admin_notice( string $message, string $notice_class = 'notice-success' ): void {
		add_action(
			'admin_notices',
			static function () use ( $message, $notice_class ): void {
				printf(
					'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( $notice_class ),
					esc_html( $message )
				);
			}
		);
	}
}
