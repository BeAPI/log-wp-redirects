=== Log WP Redirects ===
Contributors: beapi
Tags: redirect, log, wp_redirect, monitoring, network, multisite
Requires at least: 5.5
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Log all WordPress redirections made via wp_redirect() function.

== Description ==

Log WP Redirects is a powerful tool for WordPress administrators and developers to monitor and debug redirections happening on their sites. This plugin captures all redirects made through WordPress's `wp_redirect()` function, giving you insights into what's happening behind the scenes.

= Key Features =
* Log all redirects with detailed information
* Track HTTP status codes (301, 302, 307, etc.)
* Record referrer URLs and request URIs
* Capture user agent information
* Track user IDs for authenticated users
* IP address logging (configurable)
* Cookie tracking (names only, not values)
* Full backtrace to identify redirect sources
* Multisite/Network compatible
* Configurable log retention period

= Use Cases =
* Debugging theme and plugin redirects
* Monitoring login/logout redirects
* Tracking form submissions
* Analyzing user flow through your site
* Identifying unexpected redirects

= Network Support =
The plugin is fully compatible with WordPress Multisite installations, providing both site-level and network-level log views.

= Available Hooks =
Customize how long logs are kept before being automatically deleted:

```php
add_filter( 'lwr_expiration_days', function( $days ) {
    return 14; // default = 7
});
```

Filter which redirects should be logged based on status code or other criteria:

```php
// Example: Don't log 302 temporary redirects
add_filter( 'lwr_should_log_redirect', function( $should_log, $status, $location, $redirect_data ) {
    if ( 302 === $status ) {
        return false;
    }
    return $should_log;
}, 10, 4 );
```

Modify the data before it's inserted into the database:

```php
// Example: Remove all cookies information for privacy
add_filter( 'lwr_pre_insert_data', function( $data, $location, $status ) {
    // Empty the cookies field
    $data['cookies'] = '';
    
    // Or you could also sanitize/modify other fields
    // $data['user_agent'] = 'Anonymized';
    
    return $data;
}, 10, 3 );
```

= Privacy Considerations =
This plugin logs IP addresses by default. If you need to comply with privacy regulations such as GDPR, you can disable IP logging by defining the following constant in your wp-config.php:

```php
define('LWR_LOG_IP', false);
```

== Installation ==

= Standard Installation =

1. Upload the `log-wp-redirects` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. For single sites, access logs via Tools > Log WP Redirects
4. For multisite, access network-wide logs via Network Admin > Settings > Log WP Redirects

= Composer Installation =

You can install this plugin via Composer:

`composer require beapi/log-wp-redirects`

Or add it to your `composer.json` file:

`"require": {
    "beapi/log-wp-redirects": "^1.0"
}`

== Frequently Asked Questions ==

= Will this plugin affect my site's performance? =

The plugin is designed to have minimal impact on performance. It only hooks into redirect functions and stores the data efficiently.

= How long are logs kept? =

By default, logs are kept for 7 days before being automatically deleted. You can customize this period using the `lwr_expiration_days` filter.

= Can I export the logs? =

The current version does not include an export feature, but this is planned for future releases.

= Does this work with multisite/network installations? =

Yes! The plugin fully supports WordPress Multisite and provides both site-specific and network-wide views of redirect logs.

= Will this log all redirects on my site? =

The plugin logs all redirects made through WordPress's standard `wp_redirect()` function. It will not log:
- Redirects made by your server configuration (e.g., .htaccess)
- Redirects made using PHP's header() function directly
- JavaScript redirects

== Screenshots ==

1. Settings page showing the main redirect log interface.
2. Detailed view of a specific redirect with complete backtrace information.
3. Network admin view for managing redirects across multiple sites in a WordPress multisite installation.

== Changelog ==

= 1.0.3 =
* Replaced custom time formatting with WordPress native human_time_diff() function
* Added Composer support for easier installation in modern WordPress projects
* Improved WP-Cron implementation using standard WordPress activation/deactivation hooks
* Switched from single events to properly scheduled daily events for log cleanup
* Added optimized database indexes for better performance, especially for cleanup operations
* Added a composite index (blog_id, date_added) to significantly improve cleanup query performance

= 1.0.2 =
* Added support for the `$x_redirect_by` parameter of wp_redirect() function
* The source of redirects is now tracked and displayed in the admin interface
* Enhanced database structure to accommodate the new data
* Improved database schema management using WordPress's dbDelta() function
* Added new filter `lwr_pre_insert_data` to modify data before database insertion
* Increased field size for x_redirect_by from 100 to 255 characters

= 1.0.1 =
* Added new filter `lwr_should_log_redirect` to control which redirects are logged
* Improved code documentation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.3 =
This update improves time formatting, adds Composer support, enhances the scheduled cleanup process, and optimizes database performance with better indexes.

= 1.0.2 =
This update adds tracking of redirect sources, database improvements, and a new filter to modify log data before saving.

= 1.0.1 =
This update adds a new filter for more fine-grained control over which redirects are logged.

= 1.0.0 =
Initial release of Log WP Redirects. 