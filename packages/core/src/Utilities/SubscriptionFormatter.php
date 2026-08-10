<?php
/**
 * Subscription display formatting helpers.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Utilities;

use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;

/**
 * Formats subscription values for admin and storefront display.
 */
final class SubscriptionFormatter {

	/**
	 * Format a subscription amount using WooCommerce currency settings.
	 *
	 * @param string|float $amount   Amount to format.
	 * @param string       $currency Currency code.
	 * @return string HTML-safe formatted price.
	 */
	public static function format_price( $amount, string $currency = '' ): string {
		if ( function_exists( 'wc_price' ) ) {
			$args = array();

			if ( '' !== $currency ) {
				$args['currency'] = $currency;
			}

			return (string) wc_price( $amount, $args );
		}

		return esc_html( number_format( (float) $amount, 2, '.', '' ) );
	}

	/**
	 * Get a human-readable subscription status label.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function format_status( string $status ): string {
		return SubscriptionStatus::get_label( $status );
	}

	/**
	 * Format a UTC mysql datetime for display.
	 *
	 * @param string|null $datetime UTC mysql datetime.
	 * @return string
	 */
	public static function format_datetime( ?string $datetime ): string {
		if ( empty( $datetime ) ) {
			return '—';
		}

		if ( function_exists( 'wc_string_to_datetime' ) ) {
			$date = wc_string_to_datetime( $datetime );

			if ( $date && function_exists( 'wc_format_datetime' ) ) {
				return wc_format_datetime( $date );
			}
		}

		$timestamp = strtotime( $datetime . ' UTC' );

		if ( false === $timestamp ) {
			return $datetime;
		}

		return date_i18n(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$timestamp + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
		);
	}

	/**
	 * Get the next payment column label for a subscription.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return string
	 */
	public static function get_next_payment_label( Subscription $subscription ): string {
		if ( SubscriptionStatus::TRIALING === $subscription->get_status() ) {
			return __( 'Trial ends', 'subscriptly' );
		}

		return __( 'Next payment', 'subscriptly' );
	}

	/**
	 * Format billing schedule for display.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return string
	 */
	public static function format_billing_schedule( Subscription $subscription ): string {
		$interval = max( 1, $subscription->get_billing_interval() );
		$period   = SubscriptionStatus::get_billing_period_label( $subscription->get_billing_period(), $interval );

		return sprintf(
			/* translators: 1: billing interval number, 2: billing period label */
			__( 'Every %1$d %2$s', 'subscriptly' ),
			$interval,
			$period
		);
	}
}
