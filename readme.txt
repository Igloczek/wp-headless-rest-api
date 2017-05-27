=== Plugin Name ===
Contributors: igloczek
Tags: api, json, json-rest-api, REST, wp-api, wp-rest-api, spa, pwa, headless, mobile
Requires at least: 4.7
Tested up to: 4.7.5
Stable tag: 4.7
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl.html

Additional WP REST API endpoints, useful for building a headless Wordpress instances

== Description ==

This plugin extends the [WordPress REST API](https://developer.wordpress.org/rest-api/) with new routes useful for building a headless Wordpress, for example Single Page Applications, Progressive Web Application or Mobile applications

The new routes available will be:

* `/headless/v1/get_type_by_url/<url>` - (string) Get the post type by URL
* `/headless/v1/get_post_by_url/<url>` - (array) Get the post object by URL
* `/headless/v1/get_menu/<id|name|slug>` - (array) Get data for a specific menu by id, name or slug

== Installation ==

1. Install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= Can I contribute to the project? =

Of course! This is the GitHub Repository https://github.com/Igloczek/wp-headless-rest-api

== Screenshots ==

Nothing to show. This plugin has no settings or frontend, it just extends WP API with new routes.

== Changelog ==

1.0.0 - Initial release
