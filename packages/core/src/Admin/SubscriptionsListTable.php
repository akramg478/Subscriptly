<?php
/**
 * Admin subscriptions list table.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Admin;

defined( 'ABSPATH' ) || exit;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\Subscription;
use Subscriptly\Utilities\SubscriptionFormatter;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table implementation for subscriptions.
 */
final class SubscriptionsListTable extends \WP_List_Table {

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
		parent::__construct(
			array(
				'singular' => 'subscription',
				'plural'   => 'subscriptions',
				'ajax'     => false,
			)
		);

		$this->data_store = $data_store;
	}

	/**
	 * Define table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'id'                => __( 'ID', 'subscriptly' ),
			'customer'          => __( 'Customer', 'subscriptly' ),
			'status'            => __( 'Status', 'subscriptly' ),
			'recurring_total'   => __( 'Recurring Total', 'subscriptly' ),
			'next_payment_date' => __( 'Next Payment', 'subscriptly' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array<string, array<int, bool>>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'id'                => array( 'id', false ),
			'status'            => array( 'status', false ),
			'next_payment_date' => array( 'next_payment_date', false ),
		);
	}

	/**
	 * Message shown when no subscriptions exist.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No subscriptions found.', 'subscriptly' );
	}

	/**
	 * Prepare table items.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin list table filters use GET without a nonce, matching core list tables.
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( (string) $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_page = 20;

		$args = array(
			'status' => $status,
			'search' => $search,
			'limit'  => $per_page,
			'offset' => ( $paged - 1 ) * $per_page,
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		$this->items = $this->data_store->query( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $this->data_store->count( $args ),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Default column renderer.
	 *
	 * @param Subscription $item Subscription item.
	 * @param string       $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'id':
				return sprintf(
					'<a href="%s"><strong>#%d</strong></a>',
					esc_url(
						admin_url(
							sprintf(
								'admin.php?page=subscriptly-subscriptions&view=detail&subscription_id=%d',
								$item->get_id()
							)
						)
					),
					$item->get_id()
				);
			case 'customer':
				$user = get_user_by( 'id', $item->get_customer_id() );
				return esc_html( $user ? $user->display_name : __( 'Guest', 'subscriptly' ) );
			case 'status':
				return esc_html( SubscriptionFormatter::format_status( $item->get_status() ) );
			case 'recurring_total':
				return wp_kses_post(
					SubscriptionFormatter::format_price(
						$item->get_recurring_total(),
						$item->get_currency()
					)
				);
			case 'next_payment_date':
				return esc_html( SubscriptionFormatter::format_datetime( $item->get_next_payment_date() ) );
		}

		return '';
	}
}
