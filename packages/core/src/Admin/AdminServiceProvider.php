<?php
/**
 * Admin service provider.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Admin;

use Subscriptly\Container;
use Subscriptly\Contracts\ServiceProviderInterface;
use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Services\FeatureRegistry;

/**
 * Registers admin-facing features.
 */
final class AdminServiceProvider implements ServiceProviderInterface {

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
		if ( ! $this->container->get( FeatureRegistry::class )->is_enabled( FeatureRegistry::FEATURE_ADMIN_LIST ) ) {
			return;
		}

		if ( ! $this->container->has( SubscriptionDataStore::class ) ) {
			$this->container->set( SubscriptionDataStore::class, new SubscriptionDataStore() );
		}

		$controller = new SubscriptionsController(
			$this->container->get( SubscriptionDataStore::class )
		);

		$this->container->set( SubscriptionsController::class, $controller );
		$controller->register();
	}
}
