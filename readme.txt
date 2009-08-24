=== Floatbox Plus ===
Contributors: Oliver Schaal
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/plugins/floatbox-plus/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=C2RBCTVPU9QKJ&lc=DE&item_name=splash%2ede&item_number=WordPress%20Plugin%3a%20Floatbox%20Plus&cn=Mitteilung%20an%20den%20Entwickler&no_shipping=1&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: lightview, images, lightbox, photo, image, ajax, picture, floatbox, overlay, fancybox, thickbox
License: GPL v3, see LICENSE
Requires at least: 2.5
Tested up to: 2.8.4
Stable tag: 1.2.3

Seamless integration of Floatbox (jscript similar to Lightview/Lightbox/Shadowbox/Fancybox/Thickbox) to create nice overlay display images/videos without the need to change html.

== Description ==

> Please go to the options page and set which version of floatbox you use! Otherwise the plugin may not work properly.

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
3. Extract the downloaded zip file and upload the whole 'floatbox' folder to '/wp-content/plugins/floatbox-plus/floatbox/
4. Activate the plugin through the 'Plugins' menu in the WordPress admin

== Frequently Asked Questions ==

= Can I use this plugin with floatbox 3.50 or higher? =

This plugin is made for floatbox 3.24 (and below), if you want to use it with
floatbox 3.50 or higher, please use the following patch: [patch/instructions](http://board.splash.de/showthread.php?t=11165)

= Can I use this plugin and Lightview Plus (other any other plugin with similar function) at the same time? =

No!

= Possible to use this plugin with other plugins (like referrer detector) using the JQuery-Library? =

Yes, Floatbox doesn't depend on external JavaScript-Libraries and therefor it is compatible to referrer detector
and other plugins using JQuery.

= Can i exlude an image from showing with floatbox? =

Yes, you can:

for floatbox 3.50 and higher:
add class="nofloatbox" to the link

for earlier versions of floatbox:
add rel="nofloatbox" to the link

= Is it possible to change the options of floatbox.js without editing the file itself? =

Yes and no, actually only some options (theme selection + animation options) can be adjusted at the plugin options page.

For other questions, take a look at the [support forum](http://board.splash.de/forumdisplay.php?f=103).

== Changelog ==

= 1.2.3 =
* [NEW] allow youtube videos to be shown in fullscreen-mode

= 1.2.2 =
* [FIX] YouTube HQ-Video
* [NEW] set floatbox-licensekey via options page
* [more information](http://blog.splash.de/2009/08/06/floatbox-plus-1-2-0-1-2-2-download-option/)

= 1.2.1 =
* [FIX] updating the options didn't work

= 1.2.0 =
* [FIX] german translation
* [FIX] download option reactivated
* [FIX] updated faq: howto exlude images...

= 1.1.0 =
* [FIX] WP <2.8 compatibility/enqueue js/cssfiles
* [more information](http://blog.splash.de/2009/06/14/floatbox-plus-1-1-0-cssjs-ladefunktion-angepasst/)

= 1.0.0 =
* [NEW] videogallery (#7)
* [NEW] compatibility for floatbox 3.50 and above/older versions are still supported (#14)
* [FIX] compatibility fix for wordpress 2.8 (2.5++) (#12)
* [FIX] Updated video links, with caption title (+ validation error fixed) (#13)

= 0.3.4 =
* [FIX] YouTube HQ

= 0.3.3 =
* [FIX] Floatbox plus causing Error on IE7 - Expected identifier, string or number #10

= 0.3.2 =
* [FIX] direct download disabled, due to changes in the licensing of floatbox

= 0.3.1 =
* [FIX] showing YouTube HQ videos direct in HQ mode

= 0.3.0 =
* [NEW] simplexml_load_file support for PHP4 and PHP5, where it is not compiled with
* [FIX] adding default values

= 0.2.0 =
* [NEW] direct download of floatbox.js inside the plugin, no more manual upload needed
* [more information](http://blog.splash.de/2009/02/25/floatbox-plus-020-integrierte-download-option-fur-floatbox/)

= 0.1.4 =
* [FIX] URL to language files/graphics of floatbox
* [more information](http://blog.splash.de/2009/02/20/floatbox-plus-014-bugfix-release/)

= 0.1.3 =
* [FIX] using captions instead of title
* [FIX] NextGen Gallery: HTML in comments... (#5)
* [more information](http://blog.splash.de/2009/02/06/floatbox-plus-013-caption-anstatt-title-und-damit-hoffentlich-weniger-probleme/)

= 0.1.2 =
* [NEW] change (some) floatbox options at plugin options page
* [NEW] Updatenotices
* [FIX] Backup and restore of floatbox during auto update of the plugin
* [more information](http://blog.splash.de/2009/02/01/floatbox-plus-012-backuprestore-floatbox-optinen/)

= 0.1.1 =
* update (lightview: 2.0.3 -> 2.1.0)

= 0.1.0 =
* initial release
* [more information](http://blog.splash.de/2009/01/29/floatbox-plus/)
