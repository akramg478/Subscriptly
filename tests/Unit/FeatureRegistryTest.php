<?php
/**
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subscriptly\Services\FeatureRegistry;

final class FeatureRegistryTest extends TestCase {

	public function test_free_mode_enables_core_features_only(): void {
		$registry = new FeatureRegistry();
		$registry->boot( 'free' );

		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_SUBSCRIPTIONS ) );
		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_MANUAL_RENEWALS ) );
		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_MY_ACCOUNT ) );
		$this->assertFalse( $registry->is_enabled( FeatureRegistry::FEATURE_AUTO_PAYMENTS ) );
		$this->assertFalse( $registry->is_enabled( FeatureRegistry::FEATURE_PAYMENT_GATEWAYS ) );
	}

	public function test_pro_mode_enables_pro_features(): void {
		$registry = new FeatureRegistry();
		$registry->boot( 'pro' );

		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_AUTO_PAYMENTS ) );
		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_IMPORT_EXPORT ) );
	}

	public function test_pro_constant_enables_pro_features_in_free_mode(): void {
		if ( ! defined( 'SUBSCRIPTLY_PRO_VERSION' ) ) {
			define( 'SUBSCRIPTLY_PRO_VERSION', '1.0.0' );
		}

		$registry = new FeatureRegistry();
		$registry->boot( 'free' );

		$this->assertTrue( $registry->is_enabled( FeatureRegistry::FEATURE_AUTO_PAYMENTS ) );
	}

	public function test_enabled_features_filter_can_remove_features(): void {
		add_filter(
			'subscriptly_enabled_features',
			static function ( array $enabled ): array {
				return array_values(
					array_diff( $enabled, array( FeatureRegistry::FEATURE_BASIC_REPORTS ) )
				);
			}
		);

		$registry = new FeatureRegistry();
		$registry->boot( 'free' );

		$this->assertFalse( $registry->is_enabled( FeatureRegistry::FEATURE_BASIC_REPORTS ) );
	}
}
