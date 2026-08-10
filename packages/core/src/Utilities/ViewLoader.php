<?php
/**
 * Resolves core view template paths.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Utilities;

/**
 * Loads view templates relative to core source (works for Free and Pro autoload paths).
 */
final class ViewLoader {

	/**
	 * Absolute path to a core view template.
	 *
	 * @param string $view View path relative to Views/ (e.g. Admin/subscriptions-list.php).
	 * @return string
	 */
	public static function path( string $view ): string {
		return dirname( __DIR__ ) . '/Views/' . ltrim( $view, '/' );
	}

	/**
	 * Include a core view template.
	 *
	 * @param string               $view View path relative to Views/.
	 * @param array<string, mixed> $vars Variables extracted into template scope.
	 * @return void
	 */
	public static function render( string $view, array $vars = array() ): void {
		$template = self::path( $view );

		if ( ! is_readable( $template ) ) {
			return;
		}

		if ( ! empty( $vars ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Scoped view variables.
			extract( $vars, EXTR_SKIP );
		}

		include $template;
	}
}
