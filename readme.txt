=== Wordfence Cloudfront Proxy IP Addresses ===
Tags: security, wordfence, cloudfront, proxy
Requires at least: 5.0
Tested up to: 5.8.3
Requires PHP: 5.6
Stable tag: 1.0
Contributors: emfluencekc, mightyturtle
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically update Wordfence's list of proxy IP addresses with Cloudfront IP addresses

== Description ==

If you have Cloudfront in front of Wordpress and are using Wordfence, this plugin is for you.

If you don't provide Cloudfront's IP addresses to Wordfence, any bad behavior out there can get Cloudfront itself blocked by Wordfence - and then no one will be able to access your site.

This plugin addds and automatically updates the proxy IP addresses for Cloudfront in Wordfence, so that Wordfence can correctly identify the end user's IP address.

Cloudfront updates its list of IP addresses every now and then. Don't manually add IP addresses, and then try to keep track of Cloudfront IP address changes. Just install this plugin and stop worrying!

Want to change how this plugin works, or add to it? Fork it on GitHub!
https://github.com/emfluencekc/Wordfence-Cloudfront-IPs

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wordfence-cloudfront-ips` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. That's it. This plugin has no settings page, and work with both individual and network setups. Check the plugin's row on the admin plugins page to see how recently this plugin has updated IP addresses.

== Changelog ==

= 1.0 =
Initial release. Install this plugin and your proxy IP addresses are taken care of.
