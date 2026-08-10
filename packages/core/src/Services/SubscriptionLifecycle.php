<?php
/**
 * Subscription lifecycle service.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Services;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;

/**
 * Handles subscription status transitions and business rules.
 */
final class SubscriptionLifecycle {

	/**
	 * Data store.
	 *
	 * @var SubscriptionDataStore
	 */
	private SubscriptionDataStore $data_store;

	/**
	 * Constructor.
	 *
	 * @param SubscriptionDataStore|null $data_store Optional data store.
	 */
	public function __construct( ?SubscriptionDataStore $data_store = null ) {
		$this->data_store = $data_store ?? new SubscriptionDataStore();
	}

	/**
	 * Activate a subscription.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function activate( Subscription $subscription ): void {
		$this->transition( $subscription, SubscriptionStatus::ACTIVE );

		/**
		 * Fires when a subscription is activated.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_activated', $subscription );
	}

	/**
	 * Put a subscription on hold.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function pause( Subscription $subscription ): void {
		$this->transition( $subscription, SubscriptionStatus::ON_HOLD );

		/**
		 * Fires when a subscription is paused.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_paused', $subscription );
	}

	/**
	 * Resume a paused subscription.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function resume( Subscription $subscription ): void {
		$this->transition( $subscription, SubscriptionStatus::ACTIVE );

		/**
		 * Fires when a subscription is resumed.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_resumed', $subscription );
	}

	/**
	 * Cancel a subscription.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function cancel( Subscription $subscription ): void {
		$subscription->set_end_date( current_time( 'mysql', true ) );
		$this->transition( $subscription, SubscriptionStatus::CANCELLED );

		/**
		 * Fires when a subscription is cancelled.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_cancelled', $subscription );
	}

	/**
	 * Mark a subscription as pending renewal.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function mark_pending_renewal( Subscription $subscription ): void {
		$this->transition( $subscription, SubscriptionStatus::PENDING_RENEWAL );

		/**
		 * Fires when a subscription enters pending renewal.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_pending_renewal', $subscription );
	}

	/**
	 * Complete a pending renewal after manual approval/payment.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return void
	 */
	public function complete_renewal( Subscription $subscription ): void {
		$subscription->set_next_payment_date(
			$this->calculate_next_payment_date( $subscription )
		);

		$this->transition( $subscription, SubscriptionStatus::ACTIVE );

		/**
		 * Fires when a subscription renewal is completed.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_renewed', $subscription );
	}

	/**
	 * Determine whether the current user can manage a subscription.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @param string       $capability Required capability action.
	 * @return bool
	 */
	public function current_user_can_manage( Subscription $subscription, string $capability = 'view' ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( $subscription->get_customer_id() !== $user_id ) {
			return false;
		}

		return in_array( $capability, array( 'view', 'cancel', 'pause', 'resume' ), true );
	}

	/**
	 * Transition a subscription to a new status.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @param string       $new_status New status.
	 * @return void
	 */
	private function transition( Subscription $subscription, string $new_status ): void {
		$old_status = $subscription->get_status();

		if ( $old_status === $new_status ) {
			return;
		}

		/**
		 * Filter whether a subscription status transition is allowed.
		 *
		 * @param bool         $allowed      Whether transition is allowed.
		 * @param Subscription $subscription Subscription object.
		 * @param string       $old_status   Old status.
		 * @param string       $new_status   New status.
		 */
		$allowed = apply_filters(
			'subscriptly_subscription_status_transition_allowed',
			true,
			$subscription,
			$old_status,
			$new_status
		);

		if ( ! $allowed ) {
			throw new \RuntimeException(
				sprintf(
					'Transition from %s to %s is not allowed for subscription #%d.',
					sanitize_key( $old_status ),
					sanitize_key( $new_status ),
					absint( $subscription->get_id() )
				)
			);
		}

		$subscription->set_status( $new_status );
		$this->data_store->update( $subscription );

		/**
		 * Fires when a subscription status changes.
		 *
		 * @param Subscription $subscription Subscription object.
		 * @param string       $old_status   Previous status.
		 * @param string       $new_status   New status.
		 */
		do_action( 'subscriptly_subscription_status_changed', $subscription, $old_status, $new_status );
	}

	/**
	 * Calculate the next payment date from billing settings.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return string UTC mysql datetime.
	 */
	public function calculate_next_payment_date( Subscription $subscription ): string {
		$base_timestamp = time();
		$interval       = max( 1, $subscription->get_billing_interval() );

		$modifier = match ( $subscription->get_billing_period() ) {
			'day'   => sprintf( '+%d day', $interval ),
			'week'  => sprintf( '+%d week', $interval ),
			'year'  => sprintf( '+%d year', $interval ),
			default => sprintf( '+%d month', $interval ),
		};

		return gmdate( 'Y-m-d H:i:s', strtotime( $modifier, $base_timestamp ) );
	}
}
