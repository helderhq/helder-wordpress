# Helder for WordPress

WordPress plugin that adds [Helder chat](https://helderhq.com) to a site with one click.

- Connect with one button; no code to paste.
- The Helder loader on every front-end page, with any theme or page builder.
- Logged-in visitors are passed on to Helder with a signed token, fetched separately so it never ends up in a page cache.

Install from WordPress.org (once published) or download `helder.zip` from the [releases](https://github.com/helderhq/helder-wordpress/releases). See `readme.txt` for details and the external services the plugin uses.

## Development

```sh
composer install
composer lint   # PHPCS with the WordPress Coding Standards
composer test   # token format and loader tag
```

A throwaway WooCommerce shop over https for testing: see [`test-shop/`](test-shop/README.md).

Point the plugin at another Helder environment with `define( 'HELDER_APP_URL', 'http://localhost:3000' );` in `wp-config.php`.

License: GPL-2.0-or-later.
