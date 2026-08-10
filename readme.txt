=== Subscriptly ===
Contributors: akramg478
Tags: woocommerce, subscriptions, recurring, membership, billing
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade WooCommerce subscriptions with manual renewals, lifecycle management, and extensible architecture.

== Description ==

Subscriptly adds subscription products and lifecycle management to WooCommerce using a modern, namespaced architecture designed for WordPress VIP standards.

**Free features include:**

* Simple subscription product type
* Daily, weekly, monthly, and yearly billing intervals
* Trial periods and sign-up fees
* Subscription statuses and lifecycle actions
* Manual renewal flow with pending renewal orders
* WooCommerce admin subscription management
* Customer My Account subscriptions page
* REST API and WP-CLI foundations
* HPOS compatibility
* Action Scheduler renewals

Subscriptly Pro (sold separately) extends the same core with automatic recurring payments, advanced analytics, and enterprise tooling.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/subscriptly`, or install from the WordPress.org plugin directory
2. Activate the plugin through the **Plugins** screen
3. Ensure WooCommerce is active
4. Visit **Settings → Permalinks** and click **Save Changes** once so My Account subscription URLs work correctly

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes. Subscriptly will show an admin notice if WooCommerce is missing or outdated.

= How do renewals work in the free version? =

Renewals are scheduled automatically, but payments are not captured automatically. A pending renewal order is created for manual admin/customer payment.

= Is HPOS supported? =

Yes. Subscriptly declares compatibility with WooCommerce High-Performance Order Storage.

== Hooks for developers ==

* `subscriptly_loaded`
* `subscriptly_subscription_created`
* `subscriptly_subscription_status_changed`
* `subscriptly_process_automatic_renewal`
* `subscriptly_enabled_features`

== Changelog ==

= 1.0.0 =
* Initial release with enterprise foundation
