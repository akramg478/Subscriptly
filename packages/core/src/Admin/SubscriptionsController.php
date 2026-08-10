<?php
/**
 * Admin subscriptions controller.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Admin;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Services\SubscriptionLifecycle;
use Subscriptly\Utilities\ViewLoader;

/**
 * Handles admin subscription screens and actions.
 */
final class SubscriptionsController {

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
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_subscriptly_update_subscription_status', array( $this, 'handle_status_update' ) );
		add_action( 'admin_post_subscriptly_complete_renewal', array( $this, 'handle_complete_renewal' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
	}

	/**
	 * Register WooCommerce submenu page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Subscriptions', 'subscriptly' ),
			__( 'Subscriptions', 'subscriptly' ),
			'manage_woocommerce',
			'subscriptly-subscriptions',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render subscriptions admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view subscriptions.', 'subscriptly' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( (string) $_GET['view'] ) ) : 'list';

		if ( 'detail' === $view ) {
			$this->render_detail_page();
			return;
		}

		$list_table = new SubscriptionsListTable( $this->data_store );
		$list_table->prepare_items();

		include ViewLoader::path( 'Admin/subscriptions-list.php' );
	}

	/**
	 * Render subscription detail page.
	 *
	 * @return void
	 */
	private function render_detail_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin detail screen.
		$subscription_id = isset( $_GET['subscription_id'] ) ? absint( wp_unslash( (string) $_GET['subscription_id'] ) ) : 0;
		$subscription    = $this->data_store->read( $subscription_id );

		if ( ! $subscription ) {
			wp_die( esc_html__( 'Subscription not found.', 'subscriptly' ) );
		}

		include ViewLoader::path( 'Admin/subscription-detail.php' );
	}

	/**
	 * Handle admin subscription status updates.
	 *
	 * @return void
	 */
	public function handle_status_update(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to update subscriptions.', 'subscriptly' ) );
		}

		check_admin_referer( 'subscriptly_update_subscription_status' );

		$subscription_id = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( (string) $_POST['subscription_id'] ) ) : 0;
		$new_status      = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( (string) $_POST['new_status'] ) ) : '';

		$subscription = $this->data_store->read( $subscription_id );

		if ( ! $subscription || ! SubscriptionStatus::is_valid( $new_status ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=subscriptly-subscriptions&subscriptly_error=1' ) );
			exit;
		}

		$lifecycle = new SubscriptionLifecycle( $this->data_store );

		match ( $new_status ) {
			SubscriptionStatus::ACTIVE          => $lifecycle->activate( $subscription ),
			SubscriptionStatus::ON_HOLD         => $lifecycle->pause( $subscription ),
			SubscriptionStatus::CANCELLED       => $lifecycle->cancel( $subscription ),
			SubscriptionStatus::PENDING_RENEWAL => $lifecycle->mark_pending_renewal( $subscription ),
			default                             => $subscription->set_status( $new_status ),
		};

		if ( ! in_array(
			$new_status,
			array(
				SubscriptionStatus::ACTIVE,
				SubscriptionStatus::ON_HOLD,
				SubscriptionStatus::CANCELLED,
				SubscriptionStatus::PENDING_RENEWAL,
			),
			true
		) ) {
			$this->data_store->update( $subscription );
		}

		wp_safe_redirect(
			admin_url(
				sprintf(
					'admin.php?page=subscriptly-subscriptions&view=detail&subscription_id=%d&updated=1',
					$subscription_id
				)
			)
		);
		exit;
	}

	/**
	 * Handle manual renewal completion by admin.
	 *
	 * @return void
	 */
	public function handle_complete_renewal(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to complete renewals.', 'subscriptly' ) );
		}

		check_admin_referer( 'subscriptly_complete_renewal' );

		$subscription_id = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( (string) $_POST['subscription_id'] ) ) : 0;
		$subscription    = $this->data_store->read( $subscription_id );

		if ( ! $subscription ) {
			wp_safe_redirect( admin_url( 'admin.php?page=subscriptly-subscriptions&subscriptly_error=1' ) );
			exit;
		}

		( new SubscriptionLifecycle( $this->data_store ) )->complete_renewal( $subscription );

		wp_safe_redirect(
			admin_url(
				sprintf(
					'admin.php?page=subscriptly-subscriptions&view=detail&subscription_id=%d&renewed=1',
					$subscription_id
				)
			)
		);
		exit;
	}

	/**
	 * Register dashboard summary widget.
	 *
	 * @return void
	 */
	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'subscriptly_dashboard_summary',
			__( 'Subscriptly Summary', 'subscriptly' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$active_count  = $this->data_store->count( array( 'status' => SubscriptionStatus::ACTIVE ) );
		$pending_count = $this->data_store->count( array( 'status' => SubscriptionStatus::PENDING_RENEWAL ) );

		include ViewLoader::path( 'Admin/dashboard-widget.php' );
	}
}
