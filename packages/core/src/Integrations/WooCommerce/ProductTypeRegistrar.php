<?php
/**
 * Subscription product type registration.
 *
 * @package Subscriptly
 */

declare(strict_types=1);

namespace Subscriptly\Integrations\WooCommerce;

/**
 * Registers the simple subscription product type.
 */
final class ProductTypeRegistrar {

	/**
	 * Register product type hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'product_type_selector', array( $this, 'add_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'map_product_class' ), 10, 2 );
		add_filter( 'woocommerce_product_supports', array( $this, 'add_product_support' ), 10, 3 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_fields' ) );
		add_action( 'woocommerce_subscriptly_subscription_add_to_cart', 'woocommerce_simple_add_to_cart' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add subscription product type to selector.
	 *
	 * @param array<string, string> $types Product types.
	 * @return array<string, string>
	 */
	public function add_product_type( array $types ): array {
		$types['subscriptly_subscription'] = __( 'Simple Subscription', 'subscriptly' );

		return $types;
	}

	/**
	 * Map product type to product class.
	 *
	 * @param string $classname Product class name.
	 * @param string $product_type Product type slug.
	 * @return string
	 */
	public function map_product_class( string $classname, string $product_type ): string {
		if ( 'subscriptly_subscription' === $product_type ) {
			return SubscriptionProduct::class;
		}

		return $classname;
	}

	/**
	 * Enable standard WooCommerce features for subscription products.
	 *
	 * @param bool        $supports Whether the feature is supported.
	 * @param string      $feature Feature name.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function add_product_support( bool $supports, string $feature, $product ): bool {
		if ( ! $product instanceof SubscriptionProduct ) {
			return $supports;
		}

		if ( in_array( $feature, array( 'ajax_add_to_cart', 'add_to_cart' ), true ) ) {
			return true;
		}

		return $supports;
	}

	/**
	 * Enqueue admin product editor assets.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'subscriptly-product-admin',
			SUBSCRIPTLY_PLUGIN_URL . 'assets/js/admin-product.js',
			array( 'jquery', 'woocommerce_admin' ),
			SUBSCRIPTLY_VERSION,
			true
		);
	}

	/**
	 * Render subscription product fields in admin.
	 *
	 * @return void
	 */
	public function render_product_fields(): void {
		global $post;

		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		echo '<div class="options_group show_if_subscriptly_subscription">';

		woocommerce_wp_text_input(
			array(
				'id'                => '_subscriptly_subscription_price',
				'label'             => __( 'Subscription price', 'subscriptly' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'type'              => 'number',
				'value'             => get_post_meta( $post->ID, '_subscriptly_subscription_price', true ),
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_subscriptly_sign_up_fee',
				'label'             => __( 'Sign-up fee', 'subscriptly' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'type'              => 'number',
				'value'             => get_post_meta( $post->ID, '_subscriptly_sign_up_fee', true ),
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			)
		);

		$subscriptly_billing_period = get_post_meta( $post->ID, '_subscriptly_billing_period', true );
		if ( ! is_string( $subscriptly_billing_period ) || '' === $subscriptly_billing_period ) {
			$subscriptly_billing_period = 'month';
		}

		woocommerce_wp_select(
			array(
				'id'      => '_subscriptly_billing_period',
				'label'   => __( 'Billing period', 'subscriptly' ),
				'value'   => $subscriptly_billing_period,
				'options' => array(
					'day'   => __( 'Daily', 'subscriptly' ),
					'week'  => __( 'Weekly', 'subscriptly' ),
					'month' => __( 'Monthly', 'subscriptly' ),
					'year'  => __( 'Yearly', 'subscriptly' ),
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_subscriptly_trial_length',
				'label'             => __( 'Trial length (days)', 'subscriptly' ),
				'type'              => 'number',
				'value'             => get_post_meta( $post->ID, '_subscriptly_trial_length', true ),
				'custom_attributes' => array(
					'min' => '0',
				),
			)
		);

		echo '</div>';
	}

	/**
	 * Save subscription product fields.
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function save_product_fields( \WC_Product $product ): void {
		if ( 'subscriptly_subscription' !== $product->get_type() ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product->get_id() ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce admin product save supplies its own nonce verification.
		$subscription_price = wc_format_decimal(
			sanitize_text_field( wp_unslash( (string) ( $_POST['_subscriptly_subscription_price'] ?? '0' ) ) )
		);
		$sign_up_fee        = wc_format_decimal(
			sanitize_text_field( wp_unslash( (string) ( $_POST['_subscriptly_sign_up_fee'] ?? '0' ) ) )
		);
		$billing_period     = sanitize_key(
			wp_unslash( (string) ( $_POST['_subscriptly_billing_period'] ?? 'month' ) )
		);
		$trial_length       = absint(
			wp_unslash( (string) ( $_POST['_subscriptly_trial_length'] ?? '0' ) )
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! in_array( $billing_period, array( 'day', 'week', 'month', 'year' ), true ) ) {
			$billing_period = 'month';
		}

		$product->update_meta_data( '_subscriptly_subscription_price', $subscription_price );
		$product->update_meta_data( '_subscriptly_sign_up_fee', $sign_up_fee );
		$product->update_meta_data( '_subscriptly_billing_period', $billing_period );
		$product->update_meta_data( '_subscriptly_trial_length', $trial_length );

		// Sync WooCommerce core price fields so cart/catalog templates work.
		$product->set_regular_price( $subscription_price );
		$product->set_price( $subscription_price );
	}
}
