<?php
/**
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subscriptly\Models\SubscriptionStatus;

final class SubscriptionStatusTest extends TestCase {

	public function test_all_statuses_are_valid(): void {
		foreach ( SubscriptionStatus::all() as $status ) {
			$this->assertTrue( SubscriptionStatus::is_valid( $status ) );
		}
	}

	public function test_invalid_status_is_rejected(): void {
		$this->assertFalse( SubscriptionStatus::is_valid( 'invalid-status' ) );
	}

	public function test_customer_manageable_statuses_are_subset_of_all(): void {
		foreach ( SubscriptionStatus::customer_manageable() as $status ) {
			$this->assertTrue( SubscriptionStatus::is_valid( $status ) );
		}
	}

	public function test_cancelled_status_is_not_customer_manageable(): void {
		$this->assertNotContains( SubscriptionStatus::CANCELLED, SubscriptionStatus::customer_manageable() );
	}

	/**
	 * @dataProvider billing_period_provider
	 */
	public function test_billing_period_labels( string $period, int $interval, string $expected ): void {
		$this->assertSame( $expected, SubscriptionStatus::get_billing_period_label( $period, $interval ) );
	}

	/**
	 * @return array<string, array{0: string, 1: int, 2: string}>
	 */
	public function billing_period_provider(): array {
		return array(
			'day singular'   => array( 'day', 1, 'day' ),
			'day plural'       => array( 'day', 2, 'days' ),
			'week plural'      => array( 'week', 3, 'weeks' ),
			'month default'  => array( 'month', 1, 'month' ),
			'year plural'      => array( 'year', 2, 'years' ),
			'unknown period'   => array( 'quarter', 1, 'month' ),
		);
	}
}
