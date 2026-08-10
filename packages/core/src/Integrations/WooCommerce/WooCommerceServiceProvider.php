<?php
/**
 * WooCommerce integration service provider.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Integrations\WooCommerce;

use Subscriptly\Container;
use Subscriptly\Contracts\ServiceProviderInterface;
use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Services\FeatureRegistry;

/**
 * Registers WooCommerce-specific integrations.
 */
final class WooCommerceServiceProvider implements ServiceProviderInterface {

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
		if ( ! $this->container->get( FeatureRegistry::class )->is_enabled( FeatureRegistry::FEATURE_SUBSCRIPTION_PRODUCT ) ) {
			return;
		}

		$this->container->set( SubscriptionDataStore::class, new SubscriptionDataStore() );
		$this->container->set( ProductTypeRegistrar::class, new ProductTypeRegistrar() );
		$this->container->set(
			CheckoutHandler::class,
			new CheckoutHandler(
				$this->container->get( SubscriptionDataStore::class )
			)
		);

		$this->container->get( ProductTypeRegistrar::class )->register();
		$this->container->get( CheckoutHandler::class )->register();
	}
}
