<?php
/**
 * Subscriptions list admin view.
 *
 * @package Subscriptly
 *
 * @var Subscriptly\Admin\SubscriptionsListTable $list_table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Subscriptions', 'subscriptly' ); ?></h1>
	<hr class="wp-header-end" />

	<form method="get">
		<input type="hidden" name="page" value="subscriptly-subscriptions" />
		<?php
		$list_table->search_box( __( 'Search by ID', 'subscriptly' ), 'subscriptly-subscription' );
		$list_table->display();
		?>
	</form>
</div>
