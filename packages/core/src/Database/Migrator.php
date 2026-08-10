<?php
/**
 * Database migration handler.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Database;

use Subscriptly\Models\SubscriptionStatus;

/**
 * Creates and upgrades custom database tables.
 */
final class Migrator {

	/**
	 * Install or upgrade database tables.
	 *
	 * @return void
	 */
	public function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( Schema::get_tables_sql() as $sql ) {
			dbDelta( $sql );
		}

		update_option( Schema::VERSION_OPTION, Schema::VERSION );
	}

	/**
	 * Run migrations when the stored version is outdated.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$installed_version = get_option( Schema::VERSION_OPTION, '0.0.0' );

		if ( version_compare( (string) $installed_version, Schema::VERSION, '>=' ) ) {
			return;
		}

		$this->install();
		$this->migrate_data( (string) $installed_version );
	}

	/**
	 * Run data migrations between schema versions.
	 *
	 * @param string $installed_version Previously installed version.
	 * @return void
	 */
	private function migrate_data( string $installed_version ): void {
		if ( version_compare( $installed_version, '1.1.0', '>=' ) ) {
			return;
		}

		global $wpdb;

		$table = Schema::subscriptions_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET status = %s
				WHERE status = %s
				AND trial_length > 0
				AND trial_end IS NOT NULL",
				SubscriptionStatus::TRIALING,
				SubscriptionStatus::ON_HOLD
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}
}
