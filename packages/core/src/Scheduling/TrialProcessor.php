<?php
/**
 * Trial expiration processor.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Scheduling;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Services\SubscriptionLifecycle;

/**
 * Activates subscriptions when their trial period ends.
 */
final class TrialProcessor {

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
	 * Activate subscriptions whose trial has ended.
	 *
	 * @return void
	 */
	public function activate_expired_trials(): void {
		$subscriptions = $this->data_store->get_expired_trials( gmdate( 'Y-m-d H:i:s' ), 100 );

		foreach ( $subscriptions as $subscription ) {
			$subscription->set_next_payment_date(
				$this->lifecycle->calculate_next_payment_date( $subscription )
			);
			$this->data_store->update( $subscription );
			$this->lifecycle->activate( $subscription );

			/**
			 * Fires when a subscription trial ends and the subscription becomes active.
			 *
			 * @param \Subscriptly\Models\Subscription $subscription Subscription object.
			 */
			do_action( 'subscriptly_subscription_trial_ended', $subscription );
		}
	}
}
