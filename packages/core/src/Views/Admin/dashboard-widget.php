<?php
/**
 * Dashboard widget view.
 *
 * @package Subscriptly
 *
 * @var int $active_count
 * @var int $pending_count
 */

defined( 'ABSPATH' ) || exit;
?>
<ul>
	<li>
		<strong><?php echo esc_html( (string) $active_count ); ?></strong>
		<?php esc_html_e( 'active subscriptions', 'subscriptly' ); ?>
	</li>
	<li>
		<strong><?php echo esc_html( (string) $pending_count ); ?></strong>
		<?php esc_html_e( 'pending renewals', 'subscriptly' ); ?>
	</li>
</ul>
<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=subscriptly-subscriptions' ) ); ?>">
		<?php esc_html_e( 'Manage subscriptions', 'subscriptly' ); ?>
	</a>
</p>
