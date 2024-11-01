=== WP Property Feed Connector for Houzez Theme ===
Contributors: ultimatewebuk
Tags: Vebra, Alto, Vebra, Vebralive, LetMC, Real Estate, Estate Agent, BLM, Real Homes, Houzez, Fave, Property, Properties, Rightmove, Zoopla, Theme
Plugin URI: http://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Houzez
Requires at least: 3.5
Tested up to: 6.2
Requires PHP: 5.4
Stable tag: 1.38
License: GPL2

Automatically feeds Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular Houzez real estate theme. Requires the WP Property Feed plugin.


== Description ==
# WP Property Feed Connector for Houzez Theme
Automatically feed Alto, Jupix, Vebra, LetMC or BLM (Rightmove) property details into the popular Houzez real estate theme. This is a zero-maintenance plugin that means estate agents can avoid having to re-enter property details from their back office software into their WordPress website. Requires the [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Houzez).  If you�re using a different theme to Houzez, our [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=Houzez) can be customised to automatically feed searchable property details with any WP theme.

## Requirements

This plugin requires;
  - The [WP Property Feed plugin](https://www.wppropertyfeed.co.uk/?utm_source=wordpressplugin&utm_medium=referral&utm_campaign=RealHomes)
  - The [Houzez Theme](https://themeforest.net/item/houzez-real-estate-wordpress-theme/15752549)

== Installation ==
Once you have installed and set up your Houzez theme and the WP Property Feed Plugin you simply install this connector plugin and the rest is automatic.  You can download and install this plugin using the built in WordPress plugin installer in the wp-admin, just search for \"WP Property Feed Houzez\" and install the plugin then \"Activate\" to make it active.  Once active the connector will automatically update the Real Homes properties each time the WP Property Feed plugin updates from the feeds (normally every hour).  In the settings for WP Property Feed you will see a new Houzez tab.  The tab will show the last 10 automatic updates that were performed and has a checkbox to allow you to run the connector immediately.
It is advised that you set a long time out (max_execution_time) in your php.ini file as feed downloads can take a long time.

== Screenshots ==
1. Settings screen. Shows log of updates

== Changelog ==
* First version released 1st July 2018
* 1.2 Fixed update frequency issue
* 1.3 Fix to image synching
* 1.4 Fix to thumbnail locator
* 1.5 Update to image synching
* 1.6 Fixed problem with non-thumbnail image synching
* 1.7 Fixed issue with attachments and EPC's not pulling through
* 1.8 Fixed problem with property areas/status
* 1.9 Made the attachment naming more friendly
* 1.10 Added property data purge action
* 1.11 Updated published date to some from feed
* 1.12 Set the floorplan enble flag when there are floorplans
* 1.13 Fixed issue with post meta duplications
* 1.14 Fixed issue of dissappearing thumbnails
* 1.15 Added option to put the property summary (Excerpt) into the description
* 1.16 Added iframe embed wrapper for video urls as Houzez does not add them
* 1.17 Added action hook to allow interception of property update
* 1.18 Added price qualifier/prefix to Houzez
* 1.19 Added feed processing resumption
* 1.20 Fixed issue with strange resumption behaviour
* 1.21 Added virtual tour url into houzez
* 1.22 Fixed floorplan display when there is no title
* 1.23 Added shortcodes for property file
* 1.24 Added schedule watcher to re-instate if dropped
* 1.25 Added logic to handle video tours not from youtube
* 1.26 Fixed above logic to work for iframe tours
* 1.27 Added logic to remove details heading duplication
* 1.28 Added 3d tour option and fixed some youtube patterns
* 1.29 Fixed video tour logic if only one tour provided
* 1.30 Added the profileID= link pattern for Jupix
* 1.31 Fixed some of the video tour logic
* 1.32 Fixed a problem if there are many feed updates in one day to a property
* 1.33 Fixed an issue where properties with no country cause update error on some systems
* 1.34 Added Energy Rating and Current and Potential where the feed provides it
* 1.35 Fixed problem with thumbnails updating
* 1.36 Added some update logging and set timeout to unlimited (if allowed)
* 1.37 Added council tax band to the description if available
* 1.38 Fixed type "concil" and wrapped in div
* 1.39 Added span into council tax band in order to allow targeting of the title