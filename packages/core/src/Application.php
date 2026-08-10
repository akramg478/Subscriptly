<?php
/**
 * Application bootstrap.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly;

use Subscriptly\Admin\AdminServiceProvider;
use Subscriptly\Admin\Notices;
use Subscriptly\Database\Migrator;
use Subscriptly\Frontend\FrontendServiceProvider;
use Subscriptly\Integrations\WooCommerce\WooCommerceServiceProvider;
use Subscriptly\Requirements\RequirementsChecker;
use Subscriptly\Scheduling\SchedulerServiceProvider;
use Subscriptly\Services\FeatureRegistry;
use Subscriptly\Services\SubscriptionLifecycle;

/**
 * Central application orchestrator.
 */
final class Application {

	public const TEXT_DOMAIN = 'subscriptly';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Plugin distribution mode.
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * Whether the application finished booting.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Boot the application once.
	 *
	 * @param string $mode Plugin mode: free|pro.
	 * @return self|null Returns null when requirements fail.
	 */
	public static function boot( string $mode = 'free' ): ?self {
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		define( 'SUBSCRIPTLY_CORE_LOADED', true );

		self::$instance = new self( $mode );

		// HPOS compatibility must be declared before WooCommerce initializes.
		add_action( 'before_woocommerce_init', array( self::$instance, 'declare_hpos_compatibility' ) );

		// Boot after WordPress and WooCommerce have initialized translations and services.
		add_action( 'init', array( self::$instance, 'initialize' ), 10 );

		return self::$instance;
	}

	/**
	 * Get the application instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			throw new \RuntimeException( 'Subscriptly application has not been booted.' );
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $mode Plugin mode.
	 */
	private function __construct( string $mode ) {
		$this->mode      = $mode;
		$this->container = new Container();
	}

	/**
	 * Initialize plugin services.
	 *
	 * @return void
	 */
	public function initialize(): void {
		if ( $this->booted ) {
			return;
		}
		$this->register_core_services();

		$requirements = $this->container->get( RequirementsChecker::class );
		$notices      = $this->container->get( Notices::class );

		if ( ! $requirements->are_met() ) {
			$notices->register_requirement_notices( $requirements );
			return;
		}

		$this->booted = true;

		$this->container->get( Migrator::class )->maybe_upgrade();
		$this->container->get( FeatureRegistry::class )->boot( $this->mode );
		$this->register_service_providers();
		$this->register_hooks();

		/**
		 * Fires after Subscriptly has fully booted.
		 *
		 * @param Application $application Application instance.
		 */
		do_action( 'subscriptly_loaded', $this );
	}

	/**
	 * Register core services in the container.
	 *
	 * @return void
	 */
	private function register_core_services(): void {
		$this->container->set( Container::class, $this->container );
		$this->container->set( self::class, $this );
		$this->container->set( RequirementsChecker::class, new RequirementsChecker() );
		$this->container->set( Notices::class, new Notices() );
		$this->container->set( FeatureRegistry::class, new FeatureRegistry() );
		$this->container->set( Migrator::class, new Migrator() );
		$this->container->set( SubscriptionLifecycle::class, new SubscriptionLifecycle() );
	}

	/**
	 * Register modular service providers.
	 *
	 * @return void
	 */
	private function register_service_providers(): void {
		$providers = array(
			WooCommerceServiceProvider::class,
			SchedulerServiceProvider::class,
			AdminServiceProvider::class,
			FrontendServiceProvider::class,
		);

		foreach ( $providers as $provider_class ) {
			$provider = new $provider_class( $this->container );
			$provider->register();
		}
	}

	/**
	 * Register global hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// WordPress.org hosts translate.wordpress.org strings automatically.
	}

	/**
	 * Declare compatibility with WooCommerce HPOS.
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				SUBSCRIPTLY_PLUGIN_FILE,
				true
			);
		}
	}

	/**
	 * Whether the application booted successfully.
	 *
	 * @return bool
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Get the service container.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param class-string $id Service class name.
	 * @return mixed
	 */
	public function get( string $id ) {
		return $this->container->get( $id );
	}
}
