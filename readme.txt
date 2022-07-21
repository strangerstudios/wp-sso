=== WP Single Sign On (SSO) ===
Contributors: strangerstudios
Tags: authentication, sso, single sign on
Requires at least: 5
Tested up to: 6.0.1
Stable tag: .1.1

Connect multiple non-multisite WordPress sites together to share logins.

== Description ==

This plugin is useful if you want to allow users from one WordPress site to log into another WordPress site. While this can be achieved with WordPress Multisite, sometimes you'd rather keep the sites as individual WordPress installations.

This plugin should be installed and activated on both the "host" and "client" sites. The host site is the main site where users will already have accounts. There should only be one site configured as a host. There can be multiple client sites pointing to the same host. When someone tries to log into a client site, if the provided username and password doesn't work, the plugin will attempt to connect to the host site with the same username and password. If that authentication works, then either a new user is created on the client site or the password of the user on the client site is updated.

New users are given the default role on the client site. This role should be "Subscriber" by default, but double check the general settings. Make sure subscribers only have access to what you want them to have access to.

== Installation ==

= Setup the Host =
1. Download the latest version of the plugin.
1. Unzip the downloaded file to your computer.
1. Upload the /wp-sso/ directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings -> WP SSO.
1. Check the box to enable host.
1. Copy the host URL.
1. Save settings.

= Setup a Client =
1. Download the latest version of the plugin.
1. Unzip the downloaded file to your computer.
1. Upload the /wp-sso/ directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings -> WP SSO.
1. Check the box to enable client.
1. Paste the host URL from the host site into the box.
1. Save settings.

Now users from the host site will be able to log into the client sites with the same username and passwords.

== Changelog ==
= .1.1 - 2022-07-22 =
* BUG FIX: Updated Basic Authentication code to support subfolder multisite installs.
* ENHANCEMENT: Handling WP_Error results when attempting to connect to the host.

= .1 =
* Initial commit.
