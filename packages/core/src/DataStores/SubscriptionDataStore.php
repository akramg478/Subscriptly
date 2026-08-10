<?php
/**
 * Subscription data store.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\DataStores;

use Subscriptly\Database\Schema;
use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;

/**
 * Handles CRUD operations for subscriptions using custom tables.
 */
final class SubscriptionDataStore {

	/**
	 * Create a subscription record.
	 *
	 * @param Subscription $subscription Subscription model.
	 * @return int Subscription ID.
	 */
	public function create( Subscription $subscription ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$subscription->set_date_created( $now );
		$subscription->set_date_modified( $now );

		$table = Schema::subscriptions_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			$this->prepare_row( $subscription ),
			$this->get_row_formats()
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to create subscription record.' );
		}

		$subscription_id = (int) $wpdb->insert_id;
		$subscription->set_id( $subscription_id );

		$this->save_meta( $subscription );

		/**
		 * Fires after a subscription is created.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_created', $subscription );

		return $subscription_id;
	}

	/**
	 * Update a subscription record.
	 *
	 * @param Subscription $subscription Subscription model.
	 * @return void
	 */
	public function update( Subscription $subscription ): void {
		global $wpdb;

		if ( $subscription->get_id() <= 0 ) {
			throw new \InvalidArgumentException( 'Cannot update a subscription without an ID.' );
		}

		$subscription->set_date_modified( current_time( 'mysql', true ) );

		$table = Schema::subscriptions_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			$this->prepare_row( $subscription ),
			array( 'id' => $subscription->get_id() ),
			$this->get_row_formats(),
			array( '%d' )
		);

		if ( false === $updated ) {
			throw new \RuntimeException(
				sprintf(
					'Failed to update subscription #%d.',
					absint( $subscription->get_id() )
				)
			);
		}

		$this->save_meta( $subscription );

		/**
		 * Fires after a subscription is updated.
		 *
		 * @param Subscription $subscription Subscription object.
		 */
		do_action( 'subscriptly_subscription_updated', $subscription );
	}

	/**
	 * Read a subscription by ID.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return Subscription|null
	 */
	public function read( int $subscription_id ): ?Subscription {
		global $wpdb;

		if ( $subscription_id <= 0 ) {
			return null;
		}

		$table = Schema::subscriptions_table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$subscription_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( ! $row ) {
			return null;
		}

		$subscription = Subscription::from_row( $row );
		$subscription->set_meta( $this->read_meta( $subscription_id ) );

		return $subscription;
	}

	/**
	 * Delete a subscription by ID.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function delete( int $subscription_id ): bool {
		global $wpdb;

		if ( $subscription_id <= 0 ) {
			return false;
		}

		$subscription = $this->read( $subscription_id );

		$subscriptions_table = Schema::subscriptions_table();
		$meta_table          = Schema::subscription_meta_table();
		$items_table         = Schema::subscription_items_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $meta_table, array( 'subscription_id' => $subscription_id ), array( '%d' ) );
		$wpdb->delete( $items_table, array( 'subscription_id' => $subscription_id ), array( '%d' ) );

		$deleted = (bool) $wpdb->delete(
			$subscriptions_table,
			array( 'id' => $subscription_id ),
			array( '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $deleted && $subscription ) {
			/**
			 * Fires after a subscription is deleted.
			 *
			 * @param Subscription $subscription Deleted subscription object.
			 */
			do_action( 'subscriptly_subscription_deleted', $subscription );
		}

		return $deleted;
	}

	/**
	 * Query subscriptions with basic filters.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return Subscription[]
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'status'      => '',
			'customer_id' => 0,
			'search'      => '',
			'limit'       => 20,
			'offset'      => 0,
			'orderby'     => 'date_created',
			'order'       => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$table  = Schema::subscriptions_table();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) && SubscriptionStatus::is_valid( (string) $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['customer_id'] ) ) {
			$where[]  = 'customer_id = %d';
			$values[] = (int) $args['customer_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'id = %d';
			$values[] = (int) $args['search'];
		}

		$allowed_orderby = array(
			'date_created'      => 'date_created',
			'next_payment_date' => 'next_payment_date',
			'status'            => 'status',
		);

		$orderby      = $allowed_orderby[ (string) $args['orderby'] ] ?? 'date_created';
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$limit        = max( 1, (int) $args['limit'] );
		$offset       = max( 0, (int) $args['offset'] );
		$where_sql    = implode( ' AND ', $where );
		$query_values = array_merge( $values, array( $limit, $offset ) );
		$sql          = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( empty( $values ) ) {
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ) );
		} else {
			$rows = $wpdb->get_results( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $query_values ) ) );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$subscriptions = array();

		foreach ( $rows as $row ) {
			$subscription = Subscription::from_row( $row );
			$subscription->set_meta( $this->read_meta( $subscription->get_id() ) );
			$subscriptions[] = $subscription;
		}

		return $subscriptions;
	}

	/**
	 * Get active subscriptions due for renewal.
	 *
	 * @param string $before_utc UTC mysql datetime.
	 * @param int    $limit      Maximum records.
	 * @return Subscription[]
	 */
	public function get_due_for_renewal( string $before_utc, int $limit = 100 ): array {
		global $wpdb;

		$table = Schema::subscriptions_table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE status = %s
				AND next_payment_date IS NOT NULL
				AND next_payment_date <= %s
				ORDER BY next_payment_date ASC
				LIMIT %d",
				SubscriptionStatus::ACTIVE,
				$before_utc,
				max( 1, $limit )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$subscriptions = array();

		foreach ( $rows as $row ) {
			$subscription = Subscription::from_row( $row );
			$subscription->set_meta( $this->read_meta( $subscription->get_id() ) );
			$subscriptions[] = $subscription;
		}

		return $subscriptions;
	}

	/**
	 * Get trialing subscriptions whose trial end date has passed.
	 *
	 * @param string $before_utc UTC mysql datetime.
	 * @param int    $limit      Maximum records.
	 * @return Subscription[]
	 */
	public function get_expired_trials( string $before_utc, int $limit = 100 ): array {
		global $wpdb;

		$table = Schema::subscriptions_table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE status = %s
				AND trial_end IS NOT NULL
				AND trial_end <= %s
				ORDER BY trial_end ASC
				LIMIT %d",
				SubscriptionStatus::TRIALING,
				$before_utc,
				max( 1, $limit )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$subscriptions = array();

		foreach ( $rows as $row ) {
			$subscription = Subscription::from_row( $row );
			$subscription->set_meta( $this->read_meta( $subscription->get_id() ) );
			$subscriptions[] = $subscription;
		}

		return $subscriptions;
	}

	/**
	 * Count subscriptions matching filters.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return int
	 */
	public function count( array $args = array() ): int {
		global $wpdb;

		$table  = Schema::subscriptions_table();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) && SubscriptionStatus::is_valid( (string) $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['customer_id'] ) ) {
			$where[]  = 'customer_id = %d';
			$values[] = (int) $args['customer_id'];
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( empty( $values ) ) {
			$count = (int) $wpdb->get_var( $sql );
		} else {
			$count = (int) $wpdb->get_var( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $values ) ) );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $count;
	}

	/**
	 * Prepare row data for insert/update.
	 *
	 * @param Subscription $subscription Subscription model.
	 * @return array<string, mixed>
	 */
	private function prepare_row( Subscription $subscription ): array {
		return array(
			'parent_order_id'   => $subscription->get_parent_order_id(),
			'customer_id'       => $subscription->get_customer_id(),
			'status'            => $subscription->get_status(),
			'currency'          => $subscription->get_currency(),
			'billing_period'    => $subscription->get_billing_period(),
			'billing_interval'  => $subscription->get_billing_interval(),
			'recurring_total'   => $subscription->get_recurring_total(),
			'sign_up_fee'       => $subscription->get_sign_up_fee(),
			'trial_length'      => $subscription->get_trial_length(),
			'trial_end'         => $subscription->get_trial_end(),
			'next_payment_date' => $subscription->get_next_payment_date(),
			'start_date'        => $subscription->get_start_date(),
			'end_date'          => $subscription->get_end_date(),
			'date_created'      => $subscription->get_date_created(),
			'date_modified'     => $subscription->get_date_modified(),
		);
	}

	/**
	 * Get wpdb format strings for subscription rows.
	 *
	 * @return string[]
	 */
	private function get_row_formats(): array {
		return array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%d',
			'%f',
			'%f',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);
	}

	/**
	 * Read subscription meta.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return array<string, mixed>
	 */
	private function read_meta( int $subscription_id ): array {
		global $wpdb;

		$table = Schema::subscription_meta_table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$table} WHERE subscription_id = %d",
				$subscription_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$meta = array();

		foreach ( $rows as $row ) {
			$meta[ (string) $row->meta_key ] = maybe_unserialize( $row->meta_value );
		}

		return $meta;
	}

	/**
	 * Save subscription meta values.
	 *
	 * @param Subscription $subscription Subscription model.
	 * @return void
	 */
	private function save_meta( Subscription $subscription ): void {
		global $wpdb;

		$table = Schema::subscription_meta_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$wpdb->delete(
			$table,
			array( 'subscription_id' => $subscription->get_id() ),
			array( '%d' )
		);

		foreach ( $subscription->get_meta() as $meta_key => $meta_value ) {
			$wpdb->insert(
				$table,
				array(
					'subscription_id' => $subscription->get_id(),
					'meta_key'        => (string) $meta_key,
					'meta_value'      => maybe_serialize( $meta_value ),
				),
				array( '%d', '%s', '%s' )
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	}
}
