=== Grid Social Boxes ===
Contributors: edwardbock,mkernel,skroppenstedt
Donate link: http://palasthotel.de/
Tags: grid, landingpage, editor, admin, page, containerist
Requires at least: 4.0
Tested up to: 5.9.0
Stable tag: 1.7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl

Additional boxes for Grid Plugin

== Description ==

Extends the Grid Plugin with Facebook, Instagram, Youtube and Twitter Boxes.


== Installation ==

1. Upload Plugin zip file to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `grid-social-boxes` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add Facebook and Twitter Boxes to your Grids

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.7.0 =
* Feature: YouTube box can use playlist ids now

= 1.6.2 =
* Bugfix: Grid refactoring 2.1 compatibility

= 1.6.1 =
 * Optimization: YouTube Feed box editmode template

= 1.6.0 =
 * Feature: new YouTube Feed box that needs no api configuration
 * Breaking change: Heavy refactoring of namespaces and class names.
 * Library update: Google Services for YouTube
 * Library update: Twitter
 * Library update: Facebook
 * Library update: Instagram

= 1.5.0 =
 * Breaking change: YouTube by Channel ID and username are two fields now. Please check your box configuration.
 * Optimization: Auto extend token for facebook
 * Bugfix: YouTube API exception handling

= 1.4.10 =
 * Optimization: Update of youtube php client to 2.4.0

= 1.4.9 =
 * Bugfix: twitter api calls in timeline

= 1.4.8 =
 * Optimization: Facebook PHP Library update.
 * Bugfix: Expired Instagram token in settings.

= 1.4.7 =
 * Bugfix: IE templatestring problems

= 1.4.6 =
 * Lazy load facebook option
 * No need for facebook app id in box anymore
 * Twitter API update

= 1.4.5 =
 * Twitter full text 280 characters fix

= 1.4.4 =
 * Timezone fix

= 1.4.3 =
 * Added Facebook posts support
 * Template files fix

= 1.4.2 =
* Spcial Timeline undefined key fix

= 1.4.1 =
* Twitter callback url

= 1.4.0 =
* Instagram box
* Youtube box
* Social timeline box

= 1.3.2 =
* Refacoring to object
* Facebook language fix
* Editmode templates

= 1.3 =
* WP 4.3 ready

= 1.3 =
* Moved from Grid to a separate Plugin


== Upgrade Notice ==

With version 1.6.0 we changed some namespaces and classnames.

If you used the YouTube type "Channel" in Version 1.4.10- you have to change this to "Username" for Versions 1.4.11+

With 1.4.6 update you need to save permalinks to get twitter authorization work properly.

$item->time property was replaced by $item->datetime for timezone fix. Please check your theme templates grid-box-social_timeline--*.tpl.php.
