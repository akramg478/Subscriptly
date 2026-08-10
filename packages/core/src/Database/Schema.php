<?php
/**
 * Database schema definitions.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Database;

/**
 * Stores table names and schema versions.
 */
final class Schema {

	public const VERSION_OPTION = 'subscriptly_db_version';
	public const VERSION        = '1.1.0';

	/**
	 * Get the subscriptions table name.
	 *
	 * @return string
	 */
	public static function subscriptions_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'subscriptly_subscriptions';
	}

	/**
	 * Get the subscription meta table name.
	 *
	 * @return string
	 */
	public static function subscription_meta_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'subscriptly_subscription_meta';
	}

	/**
	 * Get the subscription items table name.
	 *
	 * @return string
	 */
	public static function subscription_items_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'subscriptly_subscription_items';
	}

	/**
	 * Get dbDelta-compatible schema statements.
	 *
	 * @return string[]
	 */
	public static function get_tables_sql(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$subscriptions_table = self::subscriptions_table();
		$meta_table          = self::subscription_meta_table();
		$items_table         = self::subscription_items_table();

		return array(
			"CREATE TABLE {$subscriptions_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent_order_id bigint(20) unsigned NOT NULL DEFAULT 0,
				customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'pending',
				currency varchar(3) NOT NULL DEFAULT '',
				billing_period varchar(20) NOT NULL DEFAULT 'month',
				billing_interval int(11) NOT NULL DEFAULT 1,
				recurring_total decimal(19,4) NOT NULL DEFAULT 0.0000,
				sign_up_fee decimal(19,4) NOT NULL DEFAULT 0.0000,
				trial_length int(11) NOT NULL DEFAULT 0,
				trial_end datetime NULL DEFAULT NULL,
				next_payment_date datetime NULL DEFAULT NULL,
				start_date datetime NULL DEFAULT NULL,
				end_date datetime NULL DEFAULT NULL,
				date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY customer_id (customer_id),
				KEY status (status),
				KEY parent_order_id (parent_order_id),
				KEY next_payment_date (next_payment_date)
			) {$charset_collate};",
			"CREATE TABLE {$meta_table} (
				meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (meta_id),
				KEY subscription_id (subscription_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};",
			"CREATE TABLE {$items_table} (
				item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,
				product_id bigint(20) unsigned NOT NULL DEFAULT 0,
				variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
				name text NOT NULL,
				quantity int(11) NOT NULL DEFAULT 1,
				subtotal decimal(19,4) NOT NULL DEFAULT 0.0000,
				total decimal(19,4) NOT NULL DEFAULT 0.0000,
				PRIMARY KEY  (item_id),
				KEY subscription_id (subscription_id),
				KEY product_id (product_id)
			) {$charset_collate};",
		);
	}
}
