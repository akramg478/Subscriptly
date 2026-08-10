# Subscriptly

[![CI](https://github.com/akramg478/Subscriptly/actions/workflows/ci.yml/badge.svg)](https://github.com/akramg478/Subscriptly/actions/workflows/ci.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/subscriptly.svg)](https://wordpress.org/plugins/subscriptly/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://www.php.net/)

Enterprise-grade WooCommerce subscription management for WordPress.

**Install from WordPress.org:** [https://wordpress.org/plugins/subscriptly/](https://wordpress.org/plugins/subscriptly/)

Subscriptly provides a namespaced, extensible subscription engine with **manual renewals** in the free tier. Subscriptly Pro (separate product) adds automatic recurring payments, analytics, and advanced billing.

## Requirements

- PHP 8.0+
- WordPress 6.4+
- WooCommerce 8.0+

## Features (Free)

- Simple subscription product type.
- Daily, weekly, monthly, and yearly billing
- Trial periods and sign-up fees
- Subscription lifecycle: active, on-hold, pending-renewal, cancelled, expired
- Manual renewal flow with pending renewal orders
- WooCommerce admin subscription list and detail screens
- Customer My Account subscriptions endpoint
- REST API foundation (`subscriptly/v1`)
- WP-CLI foundation
- HPOS compatibility
- Action Scheduler powered renewals
- Custom database tables with data store abstraction

## Installation

### From WordPress.org (recommended for sites)

Install **[Subscriptly on WordPress.org](https://wordpress.org/plugins/subscriptly/)**. WooCommerce must be active.

### From GitHub (development)

```bash
git clone https://github.com/akramg478/Subscriptly.git wp-content/plugins/subscriptly
cd wp-content/plugins/subscriptly
composer install --no-dev --optimize-autoloader
```

Activate **Subscriptly** in WordPress, then visit **Settings → Permalinks → Save Changes** once.

## Development

```bash
git clone https://github.com/akramg478/Subscriptly.git
cd subscriptly
composer install
composer test
composer phpcs
```

### Useful commands

| Command | Description |
|---------|-------------|
| `composer test` | Run PHPUnit suite |
| `composer test:unit` | Run unit tests only |
| `composer phpcs` | WordPress Coding Standards check |
| `composer phpcbf` | Auto-fix PHPCS issues where possible |
| `composer i18n:pot` | Regenerate `languages/subscriptly.pot` |

## Architecture

```text
packages/core/src/
├── Application.php          # Bootstrap + service container
├── Admin/                   # Admin controllers and list tables
├── DataStores/              # Database access layer
├── Models/                  # Domain models
├── Services/                # Business logic
├── Integrations/WooCommerce/
├── Scheduling/              # Action Scheduler renewals
├── Frontend/                # My Account
├── Rest/                    # REST foundation
├── Cli/                     # WP-CLI foundation
├── Contracts/               # Gateway and provider interfaces
└── Views/                   # PHP templates
```

## Extension hooks

| Hook | Purpose |
|------|---------|
| `subscriptly_loaded` | Fires after plugin boot |
| `subscriptly_subscription_created` | After subscription creation |
| `subscriptly_subscription_status_changed` | Status transitions |
| `subscriptly_process_automatic_renewal` | Pro gateways can take over renewals |
| `subscriptly_enabled_features` | Filter enabled features |
| `subscriptly_renewal_order_created` | After manual renewal order creation |

## Testing

Unit tests cover domain rules, feature registry, schema definitions, and formatting helpers without requiring a full WordPress test suite.

```bash
composer test:unit
```

GitHub Actions runs PHPUnit on PHP 8.0, 8.2, and 8.3 plus PHPCS on every push.

## Building a WordPress.org zip

```bash
composer install --no-dev --optimize-autoloader
# Then zip excluding dev files — see .distignore
```

Or tag a release (`v1.0.0`) and GitHub Actions will attach a production zip automatically.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-change`)
3. Run `composer test` and `composer phpcs`
4. Open a pull request against `main`

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).

## Author

**Akram Ul Haq** — [@akramg478](https://github.com/akramg478)
