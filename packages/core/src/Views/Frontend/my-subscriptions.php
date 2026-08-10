<?php
/**
 * My Account subscriptions view.
 *
 * @package Subscriptly
 *
 * @var Subscriptly\Models\Subscription[] $subscriptions
 */

defined( 'ABSPATH' ) || exit;

use Subscriptly\Models\SubscriptionStatus;
use Subscriptly\Utilities\SubscriptionFormatter;

if ( empty( $subscriptions ) ) :
	?>
	<p><?php esc_html_e( 'You do not have any subscriptions yet.', 'subscriptly' ); ?></p>
	<?php
	return;
endif;
?>

<table class="shop_table shop_table_responsive my_account_subscriptions">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Subscription', 'subscriptly' ); ?></th>
			<th><?php esc_html_e( 'Status', 'subscriptly' ); ?></th>
			<th><?php esc_html_e( 'Next payment', 'subscriptly' ); ?></th>
			<th><?php esc_html_e( 'Total', 'subscriptly' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'subscriptly' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $subscriptions as $subscriptly_subscription ) : ?>
			<tr>
				<td data-title="<?php esc_attr_e( 'Subscription', 'subscriptly' ); ?>">
					#<?php echo esc_html( (string) $subscriptly_subscription->get_id() ); ?>
				</td>
				<td data-title="<?php esc_attr_e( 'Status', 'subscriptly' ); ?>">
					<?php echo esc_html( SubscriptionFormatter::format_status( $subscriptly_subscription->get_status() ) ); ?>
				</td>
				<td data-title="<?php echo esc_attr( SubscriptionFormatter::get_next_payment_label( $subscriptly_subscription ) ); ?>">
					<?php if ( SubscriptionStatus::TRIALING === $subscriptly_subscription->get_status() ) : ?>
						<span class="subscriptly-trial-label"><?php esc_html_e( 'Trial ends', 'subscriptly' ); ?>:</span>
					<?php endif; ?>
					<?php echo esc_html( SubscriptionFormatter::format_datetime( $subscriptly_subscription->get_next_payment_date() ) ); ?>
				</td>
				<td data-title="<?php esc_attr_e( 'Total', 'subscriptly' ); ?>">
					<?php echo wp_kses_post( SubscriptionFormatter::format_price( $subscriptly_subscription->get_recurring_total(), $subscriptly_subscription->get_currency() ) ); ?>
				</td>
				<td data-title="<?php esc_attr_e( 'Actions', 'subscriptly' ); ?>">
					<?php if ( in_array( $subscriptly_subscription->get_status(), SubscriptionStatus::customer_manageable(), true ) ) : ?>
						<?php if ( SubscriptionStatus::ACTIVE === $subscriptly_subscription->get_status() ) : ?>
							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'subscriptly_action' => 'pause',
											'subscription_id' => $subscriptly_subscription->get_id(),
										),
										wc_get_account_endpoint_url( 'subscriptions' )
									),
									'subscriptly_subscription_action_' . $subscriptly_subscription->get_id()
								)
							);
							?>
										">
								<?php esc_html_e( 'Pause', 'subscriptly' ); ?>
							</a>
							|
							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'subscriptly_action' => 'cancel',
											'subscription_id' => $subscriptly_subscription->get_id(),
										),
										wc_get_account_endpoint_url( 'subscriptions' )
									),
									'subscriptly_subscription_action_' . $subscriptly_subscription->get_id()
								)
							);
							?>
										">
								<?php esc_html_e( 'Cancel', 'subscriptly' ); ?>
							</a>
						<?php elseif ( SubscriptionStatus::TRIALING === $subscriptly_subscription->get_status() ) : ?>
							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'subscriptly_action' => 'cancel',
											'subscription_id' => $subscriptly_subscription->get_id(),
										),
										wc_get_account_endpoint_url( 'subscriptions' )
									),
									'subscriptly_subscription_action_' . $subscriptly_subscription->get_id()
								)
							);
							?>
										">
								<?php esc_html_e( 'Cancel', 'subscriptly' ); ?>
							</a>
						<?php elseif ( SubscriptionStatus::ON_HOLD === $subscriptly_subscription->get_status() ) : ?>
							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									add_query_arg(
										array(
											'subscriptly_action' => 'resume',
											'subscription_id' => $subscriptly_subscription->get_id(),
										),
										wc_get_account_endpoint_url( 'subscriptions' )
									),
									'subscriptly_subscription_action_' . $subscriptly_subscription->get_id()
								)
							);
							?>
										">
								<?php esc_html_e( 'Resume', 'subscriptly' ); ?>
							</a>
						<?php endif; ?>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
