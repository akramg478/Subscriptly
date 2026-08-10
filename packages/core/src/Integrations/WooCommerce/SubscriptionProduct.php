<?php
/**
 * Subscription product class.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Integrations\WooCommerce;

/**
 * WooCommerce product implementation for simple subscriptions.
 */
class SubscriptionProduct extends \WC_Product_Simple {

	/**
	 * Product type slug.
	 *
	 * @var string
	 */
	protected $product_type = 'subscriptly_subscription';

	/**
	 * Get internal product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'subscriptly_subscription';
	}

	/**
	 * Subscription products are always virtual.
	 *
	 * @param string $context View context.
	 * @return bool
	 */
	public function is_virtual( $context = 'view' ): bool {
		return true;
	}

	/**
	 * Subscription products are sold individually by default.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}

	/**
	 * Determine whether the subscription product can be purchased.
	 *
	 * @param string $context View context.
	 * @return bool
	 */
	public function is_purchasable( $context = 'view' ): bool {
		$purchasable = (float) $this->get_price( $context ) >= 0 && '' !== $this->get_subscription_price();

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter.
		return (bool) apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * Use subscription price for cart and catalog display.
	 *
	 * @param string $context View context.
	 * @return string
	 */
	public function get_price( $context = 'view' ) {
		$subscription_price = $this->get_subscription_price();

		if ( '' !== $subscription_price ) {
			return wc_format_decimal( $subscription_price );
		}

		return parent::get_price( $context );
	}

	/**
	 * Use subscription price as regular price.
	 *
	 * @param string $context View context.
	 * @return string
	 */
	public function get_regular_price( $context = 'view' ) {
		$subscription_price = $this->get_subscription_price();

		if ( '' !== $subscription_price ) {
			return wc_format_decimal( $subscription_price );
		}

		return parent::get_regular_price( $context );
	}

	/**
	 * Get recurring subscription price.
	 *
	 * @return string
	 */
	public function get_subscription_price(): string {
		$price = (string) $this->get_meta( '_subscriptly_subscription_price', true );

		if ( '' !== $price ) {
			return $price;
		}

		// Back-compat for products saved before price sync existed.
		return (string) parent::get_regular_price( 'edit' );
	}

	/**
	 * Get sign-up fee.
	 *
	 * @return string
	 */
	public function get_sign_up_fee(): string {
		return (string) $this->get_meta( '_subscriptly_sign_up_fee', true );
	}

	/**
	 * Get billing period.
	 *
	 * @return string
	 */
	public function get_billing_period(): string {
		$period = (string) $this->get_meta( '_subscriptly_billing_period', true );

		return '' !== $period ? $period : 'month';
	}

	/**
	 * Get trial length in days.
	 *
	 * @return int
	 */
	public function get_trial_length(): int {
		return (int) $this->get_meta( '_subscriptly_trial_length', true );
	}
}
