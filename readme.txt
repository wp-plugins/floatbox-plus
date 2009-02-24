=== Floatbox Plus ===
Contributors: Oliver Schaal
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/plugins/floatbox-plus/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=C2RBCTVPU9QKJ&lc=DE&item_name=splash%2ede&item_number=WordPress%20Plugin%3a%20Floatbox%20Plus&cn=Mitteilung%20an%20den%20Entwickler&no_shipping=1&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: lightview, images, lightbox, photo, image, ajax, picture, floatbox, overlay, fancybox, thickbox
License: GPL v3, see LICENSE
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 0.2.0

Seamless integration of Floatbox (jscript similar to Lightview/Lightbox/Shadowbox/Fancybox/Thickbox) to create nice overlay display images/videos without the need to change html.

== Description ==

Floatbox Plus is a plugin that implements Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> (a javascript similar to Lightview/Lightbox/Shadowbox/Fancybox/Thickbox).
Floatbox Plus is used to create overlay display images/videos (only youtube/vimeo) on the webpage and to automatically add the correct overlay links to images.
Floatbox Plus permits users to view larger versions of images/videos without having to leave the current page, and is also able to display
simple slideshows. Floatbox Plus captures the image title for display in the overlay.

This plugin automatically enhance image links to use Floatbox, videos from youtube/vimeo can be inserted via wysiwyg-editor-plugin.

No other (external) JavaScript-Libraries (like Mootools/Prototype, Scriptaculous/JQuery) are needed. Unlike Lightview Plus
this plugin should work with other plugins using prototyp/scriptaculous like Referrer Detector (the reason, why i made this fork).

Cause Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> is licensed
under the terms of Creative Commons Attribution 3.0 License (http://creativecommons.org/licenses/by/3.0/)
it is not included (not GPL compatible). You have two options, let the plugin download/install floatbox for you or
do a manual installation of floatbox on your own (see installations instructions).

Please report bugs and/or feature-request to our ticket-system: [Bugtracker/Wiki](http://trac.splash.de/floatboxplus).
For Support, please use the [forum](http://board.splash.de/forumdisplay.php?f=103).

Floatbox Plus is based on the WP-Plugin "Lightview Plus" by [Thorsten Puzich](http://www.puzich.com/wordpress-plugins/lightview).

== Installation ==

1. Upload the 'floatbox-plus' folder to '/wp-content/plugins/'
2. Activate the plugin through the 'Plugins' menu in the WordPress admin
3. Let the plugin download/install floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> via option from the plugin page

or (manual installation of floatbox)

1. Upload the 'floatbox-plus' folder to '/wp-content/plugins/'
2. Download Floatbox from http://randomous.com/tools/floatbox/download.php
3. Upload the 'floatbox' folder to '/wp-content/plugins/floatbox-plus/ (Only floatbox.css/.js, framebox.js and the folders graphics and languages are needed)
4. Activate the plugin through the 'Plugins' menu in the WordPress admin

== Frequently Asked Questions ==

= Can I use this plugin and Lightview Plus (other any other plugin with similar function) at the same time? =

No!

= Possible to use this plugin with other plugins (like referrer detector) using the JQuery-Library? =

Yes, Floatbox doesn't depend on external JavaScript-Libraries and therefor it is compatible to referrer detector
and other plugins using JQuery.

= Is it possible to change the options of floatbox.js without editing the file itself? =

Yes and no, actually only some options (theme selection + animation options) can be adjusted at the plugin options page.

For other questions, take a look at the [support forum](http://board.splash.de/forumdisplay.php?f=103).

== Changelog ==

`0.2.0
- [NEW] direct download of floatbox.js inside the plugin, no more manual upload needed
0.1.4
- [FIX] URL to language files/graphics of floatbox
0.1.3
- [FIX] using captions instead of title
- [FIX] NextGen Gallery: HTML in comments... (#5)
0.1.2
- [NEW] change (some) floatbox options at plugin options page
- [NEW] Updatenotices
- [FIX] Backup and restore of floatbox during auto update of the plugin
0.1.1
- update (lightview: 2.0.3 -> 2.1.0)
0.1.0
- initial release`