<?php
/**
 * Uninstall routine.
 *
 * @package Subscriptly
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'subscriptly_db_version' );

$subscriptly_tables = array(
	$wpdb->prefix . 'subscriptly_subscriptions',
	$wpdb->prefix . 'subscriptly_subscription_meta',
	$wpdb->prefix . 'subscriptly_subscription_items',
);

foreach ( $subscriptly_tables as $subscriptly_table ) {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$subscriptly_table}" );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
