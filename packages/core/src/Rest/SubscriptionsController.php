<?php
/**
 * REST API subscriptions controller foundation.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Rest;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\Subscription;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registers read-only subscription REST routes.
 */
final class SubscriptionsController {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'subscriptly/v1';

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
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/subscriptions',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_subscriptions' ),
					'permission_callback' => array( $this, 'can_list' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/subscriptions/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_subscription' ),
					'permission_callback' => array( $this, 'can_view' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * List subscriptions for the current user or admins.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function list_subscriptions( WP_REST_Request $request ): WP_REST_Response {
		$args = array(
			'limit'  => 20,
			'offset' => 0,
		);

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			$args['customer_id'] = get_current_user_id();
		}

		$subscriptions = array_map(
			array( $this, 'serialize_subscription' ),
			$this->data_store->query( $args )
		);

		return new WP_REST_Response( $subscriptions );
	}

	/**
	 * Get a single subscription.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_subscription( WP_REST_Request $request ) {
		$subscription = $this->data_store->read( (int) $request->get_param( 'id' ) );

		if ( ! $subscription ) {
			return new \WP_Error( 'subscriptly_not_found', __( 'Subscription not found.', 'subscriptly' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $this->serialize_subscription( $subscription ) );
	}

	/**
	 * Permission callback for listing subscriptions.
	 *
	 * @return bool
	 */
	public function can_list(): bool {
		return current_user_can( 'manage_woocommerce' ) || get_current_user_id() > 0;
	}

	/**
	 * Permission callback for viewing a subscription.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public function can_view( WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$subscription = $this->data_store->read( (int) $request->get_param( 'id' ) );

		return $subscription && $subscription->get_customer_id() === get_current_user_id();
	}

	/**
	 * Serialize a subscription for REST output.
	 *
	 * @param Subscription $subscription Subscription object.
	 * @return array<string, mixed>
	 */
	private function serialize_subscription( Subscription $subscription ): array {
		return array(
			'id'                => $subscription->get_id(),
			'status'            => $subscription->get_status(),
			'customer_id'       => $subscription->get_customer_id(),
			'currency'          => $subscription->get_currency(),
			'recurring_total'   => $subscription->get_recurring_total(),
			'next_payment_date' => $subscription->get_next_payment_date(),
			'billing_period'    => $subscription->get_billing_period(),
			'billing_interval'  => $subscription->get_billing_interval(),
		);
	}
}
