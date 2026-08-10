<?php
/**
 * Feature registry for free vs pro capabilities.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Services;

/**
 * Controls which plugin features are enabled at runtime.
 */
final class FeatureRegistry {

	public const FEATURE_SUBSCRIPTIONS        = 'subscriptions';
	public const FEATURE_MANUAL_RENEWALS      = 'manual_renewals';
	public const FEATURE_SUBSCRIPTION_PRODUCT = 'subscription_product';
	public const FEATURE_MY_ACCOUNT           = 'my_account';
	public const FEATURE_ADMIN_LIST           = 'admin_list';
	public const FEATURE_BASIC_EMAILS         = 'basic_emails';
	public const FEATURE_BASIC_REPORTS        = 'basic_reports';
	public const FEATURE_REST_FOUNDATION      = 'rest_foundation';
	public const FEATURE_WPCLI_FOUNDATION     = 'wpcli_foundation';
	public const FEATURE_AUTO_PAYMENTS        = 'auto_payments';
	public const FEATURE_PAYMENT_GATEWAYS     = 'payment_gateways';
	public const FEATURE_ADVANCED_ANALYTICS   = 'advanced_analytics';
	public const FEATURE_PRO_EMAILS           = 'pro_emails';
	public const FEATURE_WEBHOOKS             = 'webhooks';
	public const FEATURE_PAYMENT_RECOVERY     = 'payment_recovery';
	public const FEATURE_FULL_REST_API        = 'full_rest_api';
	public const FEATURE_VARIABLE_PRODUCTS    = 'variable_products';
	public const FEATURE_SUBSCRIPTION_COUPONS = 'subscription_coupons';
	public const FEATURE_PLAN_SWITCHING       = 'plan_switching';
	public const FEATURE_BULK_ACTIONS         = 'bulk_actions';
	public const FEATURE_IMPORT_EXPORT        = 'import_export';
	public const FEATURE_ACTIVITY_LOG         = 'activity_log';

	/**
	 * Free-tier features always available when core boots.
	 *
	 * @var string[]
	 */
	private const FREE_FEATURES = array(
		self::FEATURE_SUBSCRIPTIONS,
		self::FEATURE_MANUAL_RENEWALS,
		self::FEATURE_SUBSCRIPTION_PRODUCT,
		self::FEATURE_MY_ACCOUNT,
		self::FEATURE_ADMIN_LIST,
		self::FEATURE_BASIC_EMAILS,
		self::FEATURE_BASIC_REPORTS,
		self::FEATURE_REST_FOUNDATION,
		self::FEATURE_WPCLI_FOUNDATION,
	);

	/**
	 * Pro-only features.
	 *
	 * @var string[]
	 */
	private const PRO_FEATURES = array(
		self::FEATURE_AUTO_PAYMENTS,
		self::FEATURE_PAYMENT_GATEWAYS,
		self::FEATURE_ADVANCED_ANALYTICS,
		self::FEATURE_PRO_EMAILS,
		self::FEATURE_WEBHOOKS,
		self::FEATURE_PAYMENT_RECOVERY,
		self::FEATURE_FULL_REST_API,
		self::FEATURE_VARIABLE_PRODUCTS,
		self::FEATURE_SUBSCRIPTION_COUPONS,
		self::FEATURE_PLAN_SWITCHING,
		self::FEATURE_BULK_ACTIONS,
		self::FEATURE_IMPORT_EXPORT,
		self::FEATURE_ACTIVITY_LOG,
	);

	/**
	 * Enabled features for the current request.
	 *
	 * @var string[]
	 */
	private array $enabled = array();

	/**
	 * Boot the registry for the current distribution mode.
	 *
	 * @param string $mode Plugin mode: free|pro.
	 * @return void
	 */
	public function boot( string $mode ): void {
		$this->enabled = self::FREE_FEATURES;

		if ( 'pro' === $mode || $this->is_pro_active() ) {
			$this->enabled = array_values(
				array_unique(
					array_merge( $this->enabled, self::PRO_FEATURES )
				)
			);
		}

		/**
		 * Filter enabled Subscriptly features.
		 *
		 * @param string[] $enabled Enabled feature keys.
		 * @param string   $mode    Plugin boot mode.
		 */
		$this->enabled = apply_filters( 'subscriptly_enabled_features', $this->enabled, $mode );
	}

	/**
	 * Determine whether a feature is enabled.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function is_enabled( string $feature ): bool {
		return in_array( $feature, $this->enabled, true );
	}

	/**
	 * Get all enabled features.
	 *
	 * @return string[]
	 */
	public function all(): array {
		return $this->enabled;
	}

	/**
	 * Determine whether Subscriptly Pro is active.
	 *
	 * @return bool
	 */
	public function is_pro_active(): bool {
		return defined( 'SUBSCRIPTLY_PRO_VERSION' );
	}
}
