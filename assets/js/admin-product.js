( function ( $ ) {
	'use strict';

	$( function () {
		function toggleSubscriptionFields() {
			const type = $( 'select#product-type' ).val();

			$( '.show_if_subscriptly_subscription' ).hide();

			if ( type === 'subscriptly_subscription' ) {
				$( '.show_if_subscriptly_subscription' ).show();
				$( '.pricing' ).hide();
				$( '.show_if_simple:not(.show_if_subscriptly_subscription)' ).hide();
			}
		}

		$( 'select#product-type' ).on( 'change', toggleSubscriptionFields );
		$( '#woocommerce-product-data' ).on( 'woocommerce-product-type-change', toggleSubscriptionFields );

		toggleSubscriptionFields();
	} );
}( jQuery ) );
