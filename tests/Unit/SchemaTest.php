<?php
/**
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subscriptly\Database\Schema;

final class SchemaTest extends TestCase {

	public function test_table_names_use_wordpress_prefix(): void {
		$this->assertSame( 'wp_subscriptly_subscriptions', Schema::subscriptions_table() );
		$this->assertSame( 'wp_subscriptly_subscription_meta', Schema::subscription_meta_table() );
		$this->assertSame( 'wp_subscriptly_subscription_items', Schema::subscription_items_table() );
	}

	public function test_schema_version_is_defined(): void {
		$this->assertSame( '1.1.0', Schema::VERSION );
		$this->assertSame( 'subscriptly_db_version', Schema::VERSION_OPTION );
	}

	public function test_get_tables_sql_returns_three_statements(): void {
		$tables = Schema::get_tables_sql();

		$this->assertCount( 3, $tables );

		foreach ( $tables as $sql ) {
			$this->assertStringContainsString( 'CREATE TABLE', $sql );
			$this->assertStringContainsString( 'wp_subscriptly_', $sql );
		}
	}
}
