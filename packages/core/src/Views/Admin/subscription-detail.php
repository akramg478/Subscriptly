<?php
/**
 * Subscription detail admin view.
 *
 * @package Subscriptly
 *
 * @var Subscriptly\Models\Subscription $subscription
 */

defined( 'ABSPATH' ) || exit;

use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Utilities\SubscriptionFormatter;

$subscriptly_customer = get_user_by( 'id', $subscription->get_customer_id() );
?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %d: subscription ID */
			esc_html__( 'Subscription #%d', 'subscriptly' ),
			(int) $subscription->get_id()
		);
		?>
	</h1>

	<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Subscription updated.', 'subscriptly' ); ?></p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['renewed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Renewal completed.', 'subscriptly' ); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Status', 'subscriptly' ); ?></th>
				<td><?php echo esc_html( SubscriptionFormatter::format_status( $subscription->get_status() ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Customer', 'subscriptly' ); ?></th>
				<td><?php echo esc_html( $subscriptly_customer ? $subscriptly_customer->display_name : __( 'Guest', 'subscriptly' ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Recurring total', 'subscriptly' ); ?></th>
				<td><?php echo wp_kses_post( SubscriptionFormatter::format_price( $subscription->get_recurring_total(), $subscription->get_currency() ) ); ?></td>
			</tr>
			<?php if ( $subscription->get_trial_length() > 0 ) : ?>
				<tr>
					<th><?php esc_html_e( 'Trial length', 'subscriptly' ); ?></th>
					<td>
						<?php
						printf(
							/* translators: %d: number of trial days */
							esc_html( _n( '%d day', '%d days', $subscription->get_trial_length(), 'subscriptly' ) ),
							(int) $subscription->get_trial_length()
						);
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Trial ends', 'subscriptly' ); ?></th>
					<td><?php echo esc_html( SubscriptionFormatter::format_datetime( $subscription->get_trial_end() ) ); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><?php echo esc_html( SubscriptionFormatter::get_next_payment_label( $subscription ) ); ?></th>
				<td><?php echo esc_html( SubscriptionFormatter::format_datetime( $subscription->get_next_payment_date() ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Billing schedule', 'subscriptly' ); ?></th>
				<td><?php echo esc_html( SubscriptionFormatter::format_billing_schedule( $subscription ) ); ?></td>
			</tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Actions', 'subscriptly' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
		<?php wp_nonce_field( 'subscriptly_update_subscription_status' ); ?>
		<input type="hidden" name="action" value="subscriptly_update_subscription_status" />
		<input type="hidden" name="subscription_id" value="<?php echo esc_attr( (string) $subscription->get_id() ); ?>" />
		<select name="new_status">
			<?php foreach ( SubscriptionStatus::all() as $subscriptly_status ) : ?>
				<option value="<?php echo esc_attr( $subscriptly_status ); ?>" <?php selected( $subscription->get_status(), $subscriptly_status ); ?>>
					<?php echo esc_html( SubscriptionFormatter::format_status( $subscriptly_status ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Update Status', 'subscriptly' ), 'secondary', 'submit', false ); ?>
	</form>

	<?php if ( SubscriptionStatus::PENDING_RENEWAL === $subscription->get_status() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
			<?php wp_nonce_field( 'subscriptly_complete_renewal' ); ?>
			<input type="hidden" name="action" value="subscriptly_complete_renewal" />
			<input type="hidden" name="subscription_id" value="<?php echo esc_attr( (string) $subscription->get_id() ); ?>" />
			<?php submit_button( __( 'Approve Renewal', 'subscriptly' ), 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>

	<?php do_action( 'subscriptly_admin_subscription_detail_after', $subscription ); ?>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=subscriptly-subscriptions' ) ); ?>">
			<?php esc_html_e( 'Back to subscriptions', 'subscriptly' ); ?>
		</a>
	</p>
</div>
