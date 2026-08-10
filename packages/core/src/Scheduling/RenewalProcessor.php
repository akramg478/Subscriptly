<?php
/**
 * Manual renewal processor.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Scheduling;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Services\SubscriptionLifecycle;

/**
 * Queues and processes pending manual renewals.
 */
final class RenewalProcessor {

	/**
	 * Data store.
	 *
	 * @var SubscriptionDataStore
	 */
	private SubscriptionDataStore $data_store;

	/**
	 * Lifecycle service.
	 *
	 * @var SubscriptionLifecycle
	 */
	private SubscriptionLifecycle $lifecycle;

	/**
	 * Constructor.
	 *
	 * @param SubscriptionDataStore $data_store Data store.
	 */
	public function __construct( SubscriptionDataStore $data_store ) {
		$this->data_store = $data_store;
		$this->lifecycle  = new SubscriptionLifecycle( $data_store );
	}

	/**
	 * Queue due renewals via Action Scheduler.
	 *
	 * @return void
	 */
	public function queue_due_renewals(): void {
		$due_subscriptions = $this->data_store->get_due_for_renewal( gmdate( 'Y-m-d H:i:s' ), 100 );

		foreach ( $due_subscriptions as $subscription ) {
			if ( function_exists( 'as_has_scheduled_action' ) &&
				as_has_scheduled_action( 'subscriptly_process_renewal', array( $subscription->get_id() ), 'subscriptly' ) ) {
				continue;
			}

			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action(
					'subscriptly_process_renewal',
					array( $subscription->get_id() ),
					'subscriptly'
				);
			}
		}
	}

	/**
	 * Process a single subscription renewal.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function process( int $subscription_id ): void {
		$subscription = $this->data_store->read( $subscription_id );

		if ( ! $subscription ) {
			return;
		}

		if ( SubscriptionStatus::ACTIVE !== $subscription->get_status() ) {
			return;
		}

		/**
		 * Allow extensions (Pro gateways) to handle automatic payment before manual renewal.
		 *
		 * Returning true skips the manual pending renewal flow.
		 *
		 * @param bool         $handled      Whether payment was handled.
		 * @param Subscription $subscription Subscription object.
		 */
		$handled = apply_filters( 'subscriptly_process_automatic_renewal', false, $subscription );

		if ( $handled ) {
			return;
		}

		$this->create_renewal_order( $subscription );
		$this->lifecycle->mark_pending_renewal( $subscription );
	}

	/**
	 * Create a pending renewal order for manual admin/customer payment.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	private function create_renewal_order( Subscription $subscription ): void {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return;
		}

		$order = wc_create_order(
			array(
				'customer_id' => $subscription->get_customer_id(),
				'status'      => 'pending',
			)
		);

		if ( is_wp_error( $order ) ) {
			return;
		}

		$product_name = (string) $subscription->get_meta_value( 'product_name', __( 'Subscription renewal', 'subscriptly' ) );
		$product      = wc_get_product( (int) $subscription->get_meta_value( 'product_id', 0 ) );

		if ( $product ) {
			$order->add_product(
				$product,
				1,
				array(
					'subtotal' => $subscription->get_recurring_total(),
					'total'    => $subscription->get_recurring_total(),
				)
			);
		} else {
			$fee = new \WC_Order_Item_Fee();
			$fee->set_name( $product_name );
			$fee->set_total( (float) $subscription->get_recurring_total() );
			$order->add_item( $fee );
		}

		$order->set_currency( $subscription->get_currency() );
		$order->calculate_totals();
		$order->update_meta_data( '_subscriptly_subscription_id', $subscription->get_id() );
		$order->update_meta_data( '_subscriptly_is_renewal_order', 'yes' );
		$order->save();

		$subscription->set_meta_value( 'last_renewal_order_id', $order->get_id() );
		$this->data_store->update( $subscription );

		/**
		 * Fires after a manual renewal order is created.
		 *
		 * @param \WC_Order    $order        Renewal order.
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_renewal_order_created', $order, $subscription );
	}
}
