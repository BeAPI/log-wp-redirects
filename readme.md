# Log WP Redirects

![WordPress](https://img.shields.io/badge/WordPress-5.8+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.0+-blue.svg)

**Log WP Redirects** is a powerful WordPress plugin that captures and logs all redirects made through WordPress's built-in `wp_redirect()` function, providing valuable insights for debugging and monitoring.

## Features

- 📊 Log all redirects with detailed information
- 🔢 Track HTTP status codes (301, 302, 307, etc.)
- 🔗 Record referrer URLs and request URIs
- 🖥️ Capture user agent information
- 👤 Track user IDs for authenticated users
- 🌐 IP address logging (configurable)
- 🍪 Cookie tracking (names only, not values)
- 🔍 Full backtrace to identify redirect sources
- 🌍 Multisite/Network compatible
- ⏱️ Configurable log retention period

## Screenshots

[Screenshots to be added]

## Installation

1. Upload the `log-wp-redirects` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. For single sites, access logs via Tools > Log WP Redirects
4. For multisite, access network-wide logs via Network Admin > Settings > Log WP Redirects

## Usage

After installing and activating the plugin, it automatically begins capturing all redirects made using WordPress's `wp_redirect()` function.

### Single Site

1. Go to Tools > Log WP Redirects in your WordPress admin
2. View the comprehensive list of all redirects
3. Click on a redirect to view detailed information including backtrace

### Multisite

1. Go to Network Admin > Settings > Log WP Redirects
2. View redirects across all sites or filter by specific site
3. Click on a redirect to view detailed information

## Hooks & Filters

### `lwr_expiration_days`

Customize how long logs are kept before being automatically deleted:

```php
add_filter( 'lwr_expiration_days', function( $days ) {
    return 14; // default = 7
});
```

## Privacy Considerations

This plugin logs IP addresses by default. If you need to comply with privacy regulations such as GDPR, you can disable IP logging by defining the following constant in your wp-config.php:

```php
define('LWR_LOG_IP', false);
```

## Requirements

- WordPress 5.8 or higher
- PHP 7.0 or higher

## License

This project is licensed under the GPLv2 or later license.

## Credits

Developed by [Be API](https://beapi.fr) 