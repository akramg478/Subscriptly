<?php
/**
 * Environment requirements checker.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Requirements;

/**
 * Validates runtime requirements before booting Subscriptly.
 */
final class RequirementsChecker {

	public const MIN_PHP_VERSION = '8.0.0';
	public const MIN_WP_VERSION  = '6.4.0';
	public const MIN_WC_VERSION  = '8.0.0';

	/**
	 * Cached requirement errors.
	 *
	 * @var string[]|null
	 */
	private ?array $errors = null;

	/**
	 * Determine whether all requirements are met.
	 *
	 * @return bool
	 */
	public function are_met(): bool {
		return empty( $this->get_errors() );
	}

	/**
	 * Get human-readable requirement errors.
	 *
	 * @return string[]
	 */
	public function get_errors(): array {
		if ( null !== $this->errors ) {
			return $this->errors;
		}

		$this->errors = array();

		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'Subscriptly requires PHP %1$s or higher. You are running PHP %2$s.', 'subscriptly' ),
				self::MIN_PHP_VERSION,
				PHP_VERSION
			);
		}

		if ( ! $this->is_woocommerce_active() ) {
			$this->errors[] = __(
				'Subscriptly requires WooCommerce to be installed and active.',
				'subscriptly'
			);
			return $this->errors;
		}

		global $wp_version;

		if ( version_compare( (string) $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: required WordPress version, 2: current WordPress version */
				__( 'Subscriptly requires WordPress %1$s or higher. You are running WordPress %2$s.', 'subscriptly' ),
				self::MIN_WP_VERSION,
				$wp_version
			);
		}

		$wc_version = self::get_woocommerce_version();

		if ( version_compare( $wc_version, self::MIN_WC_VERSION, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: required WooCommerce version, 2: current WooCommerce version */
				__( 'Subscriptly requires WooCommerce %1$s or higher. You are running WooCommerce %2$s.', 'subscriptly' ),
				self::MIN_WC_VERSION,
				$wc_version
			);
		}

		return $this->errors;
	}

	/**
	 * Resolve the installed WooCommerce version.
	 *
	 * @return string
	 */
	public static function get_woocommerce_version(): string {
		if ( defined( 'WC_VERSION' ) ) {
			return WC_VERSION;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';

		if ( ! is_readable( $plugin_file ) ) {
			return '0.0.0';
		}

		$plugin_data = get_plugin_data( $plugin_file, false, false );

		return ! empty( $plugin_data['Version'] ) ? (string) $plugin_data['Version'] : '0.0.0';
	}

	/**
	 * Determine whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}
}
