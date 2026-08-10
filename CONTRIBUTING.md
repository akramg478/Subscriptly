# Contributing to Subscriptly

Thank you for contributing. This project follows WordPress VIP-oriented patterns and ships with PHPUnit + PHPCS in CI.

## Getting started

```bash
git clone https://github.com/akramg478/Subscriptly.git
cd subscriptly
composer install
composer test
composer phpcs
```

## Pull request checklist

- [ ] `composer test:unit` passes locally
- [ ] `composer phpcs` passes (or documented ignores with a good reason)
- [ ] User-facing strings use the `subscriptly` text domain
- [ ] Database access stays inside data store classes with prepared SQL
- [ ] Admin/customer state changes use capability checks and nonces

## Branching

- `main` — stable release line
- Feature branches — `feature/short-description`
- Bugfix branches — `fix/short-description`

## Coding standards

- PHP 8.0+ with `declare(strict_types=1);` in new files
- PSR-4 namespace `Subscriptly\`
- WordPress Coding Standards (WPCS) via `phpcs.xml.dist`

## Translations

Regenerate the POT file after changing translatable strings:

```bash
composer i18n:pot
```

## Releases

WordPress.org releases are built from tagged versions. Tag format: `v1.0.0`.

```bash
git tag -a v1.0.0 -m "Release 1.0.0"
git push origin v1.0.0
```

The release workflow uploads a WordPress.org-ready zip to GitHub Releases.
