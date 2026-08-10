<?php
/**
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subscriptly\Models\Subscription;
use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Utilities\SubscriptionFormatter;

final class SubscriptionFormatterTest extends TestCase {

	public function test_format_price_without_woocommerce(): void {
		$this->assertSame( '19.99', SubscriptionFormatter::format_price( 19.99 ) );
	}

	public function test_format_status_uses_subscription_status_labels(): void {
		$this->assertSame( 'Active', SubscriptionFormatter::format_status( SubscriptionStatus::ACTIVE ) );
	}

	public function test_format_datetime_returns_dash_for_empty_value(): void {
		$this->assertSame( '—', SubscriptionFormatter::format_datetime( null ) );
		$this->assertSame( '—', SubscriptionFormatter::format_datetime( '' ) );
	}

	public function test_get_next_payment_label_for_trial_subscription(): void {
		$subscription = new Subscription();
		$subscription->set_status( SubscriptionStatus::TRIALING );

		$this->assertSame( 'Trial ends', SubscriptionFormatter::get_next_payment_label( $subscription ) );
	}

	public function test_format_billing_schedule(): void {
		$subscription = new Subscription();
		$subscription->set_billing_interval( 2 );
		$subscription->set_billing_period( 'week' );

		$this->assertSame( 'Every 2 weeks', SubscriptionFormatter::format_billing_schedule( $subscription ) );
	}
}
