<?php
/**
 * Checkout and order subscription creation.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Integrations\WooCommerce;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Services\SubscriptionLifecycle;

/**
 * Creates subscriptions from paid orders.
 */
final class CheckoutHandler {

	/**
	 * Data store.
	 *
	 * @var SubscriptionDataStore
	 */
	private SubscriptionDataStore $data_store;

	/**
	 * Constructor.
	 *
	 * @param SubscriptionDataStore $data_store Data store.
	 */
	public function __construct( SubscriptionDataStore $data_store ) {
		$this->data_store = $data_store;
	}

	/**
	 * Register checkout hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_create_subscriptions' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_create_subscriptions' ), 10, 1 );
	}

	/**
	 * Create subscriptions for qualifying order items.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function maybe_create_subscriptions( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( $order->get_meta( '_subscriptly_subscriptions_created' ) ) {
			return;
		}

		$created = false;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product || 'subscriptly_subscription' !== $product->get_type() ) {
				continue;
			}

			if ( ! $product instanceof SubscriptionProduct ) {
				continue;
			}

			$subscription = $this->build_subscription_from_product( $product, $order );
			$this->data_store->create( $subscription );

			$order->add_order_note(
				sprintf(
					/* translators: %d: subscription ID */
					__( 'Subscriptly subscription #%d created.', 'subscriptly' ),
					$subscription->get_id()
				)
			);

			$created = true;
		}

		if ( $created ) {
			$order->update_meta_data( '_subscriptly_subscriptions_created', 'yes' );
			$order->save();
		}
	}

	/**
	 * Build a subscription model from an order item product.
	 *
	 * @param SubscriptionProduct $product Subscription product.
	 * @param \WC_Order           $order   Parent order.
	 * @return Subscription
	 */
	private function build_subscription_from_product( SubscriptionProduct $product, \WC_Order $order ): Subscription {
		$lifecycle    = new SubscriptionLifecycle( $this->data_store );
		$subscription = new Subscription();

		$now          = current_time( 'mysql', true );
		$trial_length = $product->get_trial_length();
		$trial_end    = null;
		$status       = SubscriptionStatus::ACTIVE;

		if ( $trial_length > 0 ) {
			$trial_end = gmdate( 'Y-m-d H:i:s', strtotime( "+{$trial_length} days", strtotime( $now ) ) );
			$status    = SubscriptionStatus::TRIALING;
		}

		$subscription->set_parent_order_id( $order->get_id() );
		$subscription->set_customer_id( (int) $order->get_customer_id() );
		$subscription->set_status( $status );
		$subscription->set_currency( $order->get_currency() );
		$subscription->set_billing_period( $product->get_billing_period() );
		$subscription->set_billing_interval( 1 );
		$subscription->set_recurring_total( $product->get_subscription_price() );
		$subscription->set_sign_up_fee( $product->get_sign_up_fee() );
		$subscription->set_trial_length( $trial_length );
		$subscription->set_trial_end( $trial_end );
		$subscription->set_start_date( $now );
		$subscription->set_next_payment_date(
			$trial_end ?? $lifecycle->calculate_next_payment_date( $subscription )
		);
		$subscription->set_meta_value( 'product_id', $product->get_id() );
		$subscription->set_meta_value( 'product_name', $product->get_name() );

		return $subscription;
	}
}
