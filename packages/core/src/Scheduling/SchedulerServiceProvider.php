<?php
/**
 * Scheduler service provider — deferred Action Scheduler registration.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Scheduling;

use Subscriptly\Container;
use Subscriptly\Contracts\ServiceProviderInterface;
use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Services\FeatureRegistry;

/**
 * Registers Action Scheduler renewal jobs.
 */
final class SchedulerServiceProvider implements ServiceProviderInterface {

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		if ( ! $this->container->get( FeatureRegistry::class )->is_enabled( FeatureRegistry::FEATURE_MANUAL_RENEWALS ) ) {
			return;
		}

		if ( ! $this->container->has( SubscriptionDataStore::class ) ) {
			$this->container->set( SubscriptionDataStore::class, new SubscriptionDataStore() );
		}

		$renewal_processor = new RenewalProcessor(
			$this->container->get( SubscriptionDataStore::class )
		);
		$trial_processor   = new TrialProcessor(
			$this->container->get( SubscriptionDataStore::class )
		);

		$this->container->set( RenewalProcessor::class, $renewal_processor );
		$this->container->set( TrialProcessor::class, $trial_processor );

		add_action( 'subscriptly_process_renewal', array( $renewal_processor, 'process' ), 10, 1 );
		add_action( 'subscriptly_check_due_renewals', array( $renewal_processor, 'queue_due_renewals' ) );
		add_action( 'subscriptly_check_trial_endings', array( $trial_processor, 'activate_expired_trials' ) );

		add_action( 'init', array( $this, 'schedule_recurring_jobs' ), 20 );
	}

	/**
	 * Schedule recurring Action Scheduler jobs after the store is initialized.
	 *
	 * @return void
	 */
	public function schedule_recurring_jobs(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( ! as_has_scheduled_action( 'subscriptly_check_due_renewals', array(), 'subscriptly' ) ) {
			as_schedule_recurring_action(
				time() + HOUR_IN_SECONDS,
				HOUR_IN_SECONDS,
				'subscriptly_check_due_renewals',
				array(),
				'subscriptly'
			);
		}

		if ( ! as_has_scheduled_action( 'subscriptly_check_trial_endings', array(), 'subscriptly' ) ) {
			as_schedule_recurring_action(
				time() + HOUR_IN_SECONDS,
				HOUR_IN_SECONDS,
				'subscriptly_check_trial_endings',
				array(),
				'subscriptly'
			);
		}
	}
}
