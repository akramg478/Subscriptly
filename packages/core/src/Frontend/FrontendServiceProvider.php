<?php
/**
 * Frontend service provider.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Frontend;

use Subscriptly\Container;
use Subscriptly\Contracts\ServiceProviderInterface;
use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Rest\SubscriptionsController as RestSubscriptionsController;
use Subscriptly\Services\FeatureRegistry;

/**
 * Registers customer-facing features.
 */
final class FrontendServiceProvider implements ServiceProviderInterface {

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
		if ( ! $this->container->has( SubscriptionDataStore::class ) ) {
			$this->container->set( SubscriptionDataStore::class, new SubscriptionDataStore() );
		}

		$data_store = $this->container->get( SubscriptionDataStore::class );
		$registry   = $this->container->get( FeatureRegistry::class );

		if ( $registry->is_enabled( FeatureRegistry::FEATURE_MY_ACCOUNT ) ) {
			( new MyAccountController( $data_store ) )->register();
		}

		if ( $registry->is_enabled( FeatureRegistry::FEATURE_REST_FOUNDATION ) ) {
			add_action( 'rest_api_init', array( new RestSubscriptionsController( $data_store ), 'register_routes' ) );
		}

		if ( $registry->is_enabled( FeatureRegistry::FEATURE_WPCLI_FOUNDATION ) && defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'subscriptly subscriptions', '\\Subscriptly\\Cli\\SubscriptionsCommand' );
		}
	}
}
