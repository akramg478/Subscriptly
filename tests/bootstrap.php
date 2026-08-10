<?php
/**
 * PHPUnit bootstrap.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/tests/stubs/wordpress/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! isset( $GLOBALS['wpdb'] ) ) {
	$GLOBALS['wpdb'] = new class() {
		public string $prefix = 'wp_';

		/**
		 * Minimal charset collate stub for schema tests.
		 *
		 * @return string
		 */
		public function get_charset_collate(): string {
			return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
		}
	};
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Minimal translation stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( '_n' ) ) {
	/**
	 * Minimal plural translation stub.
	 *
	 * @param string $single Singular.
	 * @param string $plural Plural.
	 * @param int    $number Number.
	 * @return string
	 */
	function _n( string $single, string $plural, int $number ): string {
		return 1 === $number ? $single : $plural;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Minimal esc_html stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Minimal apply_filters stub with optional callback support in tests.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value to filter.
	 * @return mixed
	 */
	function apply_filters( string $hook, $value ) {
		foreach ( $GLOBALS['subscriptly_test_filters'][ $hook ] ?? array() as $callback ) {
			$value = $callback( $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback for tests.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @return true
	 */
	function add_filter( string $hook, callable $callback ) {
		$GLOBALS['subscriptly_test_filters'][ $hook ][] = $callback;

		return true;
	}
}

$GLOBALS['subscriptly_test_filters'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Minimal get_option stub.
	 *
	 * @param string $option Option name.
	 * @return string
	 */
	function get_option( string $option ): string {
		return match ( $option ) {
			'date_format' => 'Y-m-d',
			'time_format' => 'H:i',
			'gmt_offset'  => '0',
			default       => '',
		};
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	/**
	 * Minimal date_i18n stub.
	 *
	 * @param string $format   Date format.
	 * @param int    $timestamp Timestamp.
	 * @return string
	 */
	function date_i18n( string $format, int $timestamp ): string {
		return gmdate( $format, $timestamp );
	}
}
