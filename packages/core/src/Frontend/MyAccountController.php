<?php
/**
 * My Account subscriptions controller.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Frontend;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Services\SubscriptionLifecycle;
use Subscriptly\Utilities\ViewLoader;

/**
 * Customer subscription account area.
 */
final class MyAccountController {

	public const ENDPOINT = 'subscriptions';

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
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_endpoint' ), 5 );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'get_endpoint_title' ) );
		add_filter( 'woocommerce_page_title', array( $this, 'filter_page_title' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'handle_actions' ) );
	}

	/**
	 * Register rewrite endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Register WooCommerce account query var.
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public function register_query_vars( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;

		return $vars;
	}

	/**
	 * Endpoint page title (used by WooCommerce and compatible themes).
	 *
	 * @param string $title Default title.
	 * @return string
	 */
	public function get_endpoint_title( string $title ): string {
		return __( 'Subscriptions', 'subscriptly' );
	}

	/**
	 * Adjust the My Account page title for the subscriptions endpoint only.
	 *
	 * @param string $title Page title.
	 * @return string
	 */
	public function filter_page_title( string $title ): string {
		if ( $this->is_subscriptions_view() ) {
			return __( 'Subscriptions', 'subscriptly' );
		}

		return $title;
	}

	/**
	 * Add subscriptions item to My Account menu.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( array $items ): array {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			$new_items[ $key ] = $label;

			if ( 'orders' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Subscriptions', 'subscriptly' );
			}
		}

		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Subscriptions', 'subscriptly' );
		}

		return $new_items;
	}

	/**
	 * Whether the customer is viewing the subscriptions endpoint.
	 *
	 * @return bool
	 */
	private function is_subscriptions_view(): bool {
		if ( ! is_account_page() || ! function_exists( 'WC' ) || ! WC()->query ) {
			return false;
		}

		return self::ENDPOINT === WC()->query->get_current_endpoint();
	}

	/**
	 * Render subscriptions endpoint content.
	 *
	 * @return void
	 */
	public function render_endpoint(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$subscriptions = $this->data_store->query(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => 50,
				'offset'      => 0,
			)
		);

		$template = ViewLoader::path( 'Frontend/my-subscriptions.php' );

		if ( ! is_readable( $template ) ) {
			echo '<p>' . esc_html__( 'Unable to load subscriptions. Please contact the store administrator.', 'subscriptly' ) . '</p>';
			return;
		}

		ViewLoader::render(
			'Frontend/my-subscriptions.php',
			array(
				'subscriptions' => $subscriptions,
			)
		);
	}

	/**
	 * Handle customer subscription actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! is_user_logged_in() || ! isset( $_GET['subscriptly_action'], $_GET['subscription_id'], $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! $this->is_subscriptions_view() ) {
			return;
		}

		$action          = sanitize_key( wp_unslash( (string) $_GET['subscriptly_action'] ) );
		$subscription_id = absint( wp_unslash( (string) $_GET['subscription_id'] ) );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'subscriptly_subscription_action_' . $subscription_id ) ) {
			wc_add_notice( __( 'Invalid subscription action.', 'subscriptly' ), 'error' );
			return;
		}

		$subscription = $this->data_store->read( $subscription_id );

		if ( ! $subscription ) {
			wc_add_notice( __( 'Subscription not found.', 'subscriptly' ), 'error' );
			return;
		}

		$lifecycle = new SubscriptionLifecycle( $this->data_store );

		if ( ! $lifecycle->current_user_can_manage( $subscription, $action ) ) {
			wc_add_notice( __( 'You do not have permission to manage this subscription.', 'subscriptly' ), 'error' );
			return;
		}

		try {
			match ( $action ) {
				'cancel' => $lifecycle->cancel( $subscription ),
				'pause'  => $lifecycle->pause( $subscription ),
				'resume' => $lifecycle->resume( $subscription ),
				default  => throw new \InvalidArgumentException( 'Unsupported action.' ),
			};

			wc_add_notice( __( 'Subscription updated successfully.', 'subscriptly' ), 'success' );
		} catch ( \Throwable $exception ) {
			wc_add_notice( __( 'Unable to update subscription.', 'subscriptly' ), 'error' );
		}

		wp_safe_redirect( wc_get_account_endpoint_url( self::ENDPOINT ) );
		exit;
	}
}
