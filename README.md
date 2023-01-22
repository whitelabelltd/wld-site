# WLD Site Plugin

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v6.1%20tested-success.svg)

> This plugin configures WordPress to better protect sites with a default set of functions and features. This plugin is not meant for general-distribution, but is available for public perusal.

## Requirements

* PHP 8.0+
* [WordPress](http://wordpress.org) 6.1+

## Updates

Updates use the built-in WordPress update system to pull from GitHub releases.

## Functionality

### REST API

- Adds an option to general settings to restrict REST API access. The options are: show REST API to everyone, only show REST API to logged in users, and show REST API to everyone except `/users` endpoint. By default, the plugin requires authentication for the `/users` endpoint.

*Configured in `Settings > Platform`.*

### Authors

- Removes Agency WP Users's author archives so they aren't mistakenly indexed by search engines.

There is 1 filter available for this
- `wlds_author_archive_disable` - (bool) default value `TRUE`

### WP Admin

- Removes the WordPress Events and News widget from the dashboard
- Option to enable, If the WP User is NOT an agency user (see `client.json` below) this plugin cannot be de-activated accidentally
- Removes some Core WP Admin Bar items (WP Logo and related items)

### Email

Disables specific email notifications normally sent to the WP Site Admin
- successfull plugin auto-update email notifications
- successfull theme auto-update email notifications
- "Your Site Has Been Updated..." emails
- password change notification emails for non-admin/store-manager users
- new user notification emails for non-admin users

There are 5 filters available for this
- `wlds_email_disable_update_success_plugin` - (bool) default value `TRUE`
- `wlds_email_disable_update_success_theme`  - (bool) default value `TRUE`
- `wlds_email_disable_update_success_wp`     - (bool) default value `TRUE`
- `wlds_email_disable_update_password`       - (bool) default value `TRUE`
- `wlds_email_disable_update_new_user`       - (bool) default value `TRUE`

### Headers

`X-Frame-Origins` is set to `sameorigin` to prevent click-jacking.

There are 2 filters available here:
- `wlds_x_frame_options` - (default value) `SAMEORIGIN` can be changed to `DENY`.
- `wlds_disable_x_frame_options` - (bool) default value `FALSE` can be changed to `TRUE` - doing so will omit the header.

### General Security

- Disables XML-RPC and adds a 404 redirect if accessed directly (Re-Enable XML-RPC if you plan on using Jetpack)
- Disables File Editor
- Removes the WordPress version from the Generator hook in <head>
- Disables login specific error messages and returns a generic error message to improve security

*Configured in `Settings > Platform`.*

### Speed Improvements

- Disables
  - WP Embeds
  - Pingbacks
  - Shortlinks
  - RSS
  - W.ORG Relational Links
  - Emoji's
  - Comments
  - Windows Live Writer
  - RDS Link

*Configured in `Settings > Platform`.*

### Support

- Adds the ability for clients to submit support requests from the admin dashboard when they are an editor or above
- Adds a support widget (if enabled in `client.json`, see below)

### Third Party

#### Cloudflare

- Restores the visitor IP address automatically if it detects the site is sitting behind Cloudflare
- Easily add a Cloudflare Beacon to the site

*Configured in `Settings > Platform`.*

There is 1 filter available for this
- `wlds_cloudflare_set_real_ip` - (bool) default value `TRUE`

#### Flatsome Theme

- Removes Admin Bar Items

There is 1 filter available for this
- `wlds_flatsome_admin_bar_remove` - (bool) default value `TRUE`

#### Google Tags

- Easily add a Google Tag to the site

*Configured in `Settings > Platform`.*

#### ManageWP

- Hides the welcome banner in WP Admin if not an agency user

#### WooCommerce

- Disables Marketplace suggestions

#### WP Rocket

- Disables HTML Comments

#### Yoast SEO

- Disables HTML Comments
- Disables WP Admin bar icon

There is 1 filter available for this
- `wlds_yoast_admin_bar_remove` - (bool) default value `TRUE`

### Hosting Platforms

- Adds specific functionality when a site is running on the specific hosting platforms

### Options

Available to site admins in `Settings > Platform`.

There is 1 filter available for this
- `wlds_options_page_show` - (bool) default value `TRUE`

### client.json

You can include the `client.json` file in the plugin root directory, with the needed data for the plugin to autoload this upon activating the plugin, then the file gets deleted.

*Sample client.json file*
```json
{
  "disable_plugin_deactivation": true,
  "agency_domains": [""],
  "support_email_to": "",
  "managed_by_name": "",
  "managed_by_url": "",
  "site_admin_email": "",
  "is_fresh_wp_install": false,
  "fresh_wp_install_wplang": "",
  "fresh_wp_install_timezone_string": ""
}
```

- `disable_plugin_deactivation` (bool) If true, any non-agency member admin will not be able to use the deactivate link on the plugin (gets ignored if no agency domains are loaded)
- `agency_domains` (string[]) Domain name, any WP user with an email on that domain name is considered an Agency user.
- `support_email_to` (string) Email Address, where support form emails are sent to and is also displayed on the dashboard in the support widget
- `managed_by_name` (string) Name/Org Name, Shown in the admin as the author of the plugin and also in the footer in WP Admin
- `managed_by_url` (string) URL, Link for author of the plugin and also in the footer in WP Admin
- `site_admin_email` (string) Email, If set updates the WP Admin email
- `is_fresh_wp_install` (bool), if true it sets WP Options back to specific options (disable user registrations, closes ping and comments, disables avatars) on plugin activation
- `fresh_wp_install_wplang` (string), if `is_fresh_wp_install` is true, it sets the WP language set here, expects a [locale string](https://wpastra.com/docs/complete-list-wordpress-locale-codes/)
- `fresh_wp_install_timezone_string` (string), if `is_fresh_wp_install` is true, it sets the WP Timezone, expects a [PHP Timezone String](https://www.php.net/manual/en/timezones.php)

## Changelog

A complete listing of all notable changes to this plugin are documented in [`CHANGELOG.md`](../blob/master/CHANGELOG.md).

## Credits

This plugin is inspired and partially based on the [10up Experience Plugin](https://github.com/10up/10up-experience)
