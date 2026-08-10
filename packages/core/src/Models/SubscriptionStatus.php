<?php
/**
 * Subscription status constants.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Models;

/**
 * Subscription status definitions.
 */
final class SubscriptionStatus {

	public const PENDING         = 'pending';
	public const TRIALING        = 'trialing';
	public const ACTIVE          = 'active';
	public const ON_HOLD         = 'on-hold';
	public const PENDING_RENEWAL = 'pending-renewal';
	public const CANCELLED       = 'cancelled';
	public const EXPIRED         = 'expired';

	/**
	 * Get all valid subscription statuses.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::PENDING,
			self::TRIALING,
			self::ACTIVE,
			self::ON_HOLD,
			self::PENDING_RENEWAL,
			self::CANCELLED,
			self::EXPIRED,
		);
	}

	/**
	 * Determine whether a status is valid.
	 *
	 * @param string $status Status slug.
	 * @return bool
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::all(), true );
	}

	/**
	 * Get a human-readable status label.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function get_label( string $status ): string {
		$labels = array(
			self::PENDING         => __( 'Pending', 'subscriptly' ),
			self::TRIALING        => __( 'Trial', 'subscriptly' ),
			self::ACTIVE          => __( 'Active', 'subscriptly' ),
			self::ON_HOLD         => __( 'On hold', 'subscriptly' ),
			self::PENDING_RENEWAL => __( 'Pending renewal', 'subscriptly' ),
			self::CANCELLED       => __( 'Cancelled', 'subscriptly' ),
			self::EXPIRED         => __( 'Expired', 'subscriptly' ),
		);

		return $labels[ $status ] ?? $status;
	}

	/**
	 * Get a human-readable billing period label.
	 *
	 * @param string $period   Billing period slug.
	 * @param int    $interval Billing interval.
	 * @return string
	 */
	public static function get_billing_period_label( string $period, int $interval = 1 ): string {
		return match ( $period ) {
			'day'   => _n( 'day', 'days', $interval, 'subscriptly' ),
			'week'  => _n( 'week', 'weeks', $interval, 'subscriptly' ),
			'year'  => _n( 'year', 'years', $interval, 'subscriptly' ),
			default => _n( 'month', 'months', $interval, 'subscriptly' ),
		};
	}

	/**
	 * Statuses that allow customer-facing management actions.
	 *
	 * @return string[]
	 */
	public static function customer_manageable(): array {
		return array(
			self::TRIALING,
			self::ACTIVE,
			self::ON_HOLD,
			self::PENDING_RENEWAL,
		);
	}
}
