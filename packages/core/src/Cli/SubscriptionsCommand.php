<?php
/**
 * WP-CLI subscriptions command foundation.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Cli;

use Subscriptly\DataStores\SubscriptionDataStore;
use Subscriptly\Models\SubscriptionStatus;
use WP_CLI;

/**
 * Basic WP-CLI commands for subscriptions.
 */
final class SubscriptionsCommand {

	/**
	 * List subscriptions.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by subscription status.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$data_store = new SubscriptionDataStore();
		$status     = isset( $assoc_args['status'] ) ? sanitize_key( $assoc_args['status'] ) : '';

		if ( $status && ! SubscriptionStatus::is_valid( $status ) ) {
			WP_CLI::error( 'Invalid subscription status.' );
		}

		$subscriptions = $data_store->query(
			array(
				'status' => $status,
				'limit'  => 100,
				'offset' => 0,
			)
		);

		$rows = array_map(
			static function ( $subscription ): array {
				return array(
					'id'                => $subscription->get_id(),
					'status'            => $subscription->get_status(),
					'customer_id'       => $subscription->get_customer_id(),
					'recurring_total'   => $subscription->get_recurring_total(),
					'next_payment_date' => $subscription->get_next_payment_date(),
				);
			},
			$subscriptions
		);

		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array_keys( $rows[0] ?? array() ) );
	}
}
