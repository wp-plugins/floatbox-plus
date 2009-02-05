=== Floatbox Plus ===
Contributors: Oliver Schaal
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/plugins/floatbox-plus/
Tags: lightview, images, lightbox, photo, image, ajax, picture, floatbox, overlay
License: GPL v3, see LICENSE
Requires at least: 2.5
Tested up to: 2.7.0
Stable tag: 0.1.3

Floatbox Plus is a plugin that implements Floatbox by Byron McGregor (a javascript similar to Lightview/Lightbox/Shadowbox) to create nice overlay display images/videos.

== Description ==

Floatbox Plus is a plugin that implements Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> (a javascript similar to Lightview).
Floatbox Plus is used to create overlay display images/videos (only youtube/vimeo) on the webpage and to automatically add the correct overlay links to images.
Floatbox Plus permits users to view larger versions of images/videos without having to leave the current page, and is also able to display
simple slideshows. Floatbox Plus captures the image title for display in the overlay.

Cause Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> is licensed
under the terms of Creative Commons Attribution 3.0 License (http://creativecommons.org/licenses/by/3.0/)
it is not included (not GPL compatible).

Please read installation instructions carefully.

This plugin automatically enhance image links to use Floatbox.

No other (external) JavaScript-Libraries are needed, less incompatible with other plugins...

Please report bugs and/or feature-request to our ticket-system: [Bugtracker/Wiki](http://trac.splash.de/floatboxplus).

Floatbox Plus is based on the WP-Plugin "Lightview Plus" by [Thorsten Puzich](http://www.puzich.com/wordpress-plugins/lightview).

== Installation ==

1. Upload the 'floatbox-plus' folder to '/wp-content/plugins/'
2. Download Floatbox from http://randomous.com/tools/floatbox/download.php
3. Upload the 'floatbox' folder to '/wp-content/plugins/floatbox-plus/ (Only floatbox.css/.js, framebox.js and the folders graphics and languages are needed)
4. Activate the plugin through the 'Plugins' menu in the WordPress admin

== Frequently Asked Questions ==

= Can I use this plugin and Lightview Plus (other any other plugin with similar function) at the same time? =

No!

= Possible to use this plugin with other plugins (like referrer detector) using the JQuery-Library? =

Yes, Floatbox doesn't depend on external JavaScript-Libraries and is therefor compatible to referrer detector.

= Is it possible to change the options of floatbox.js without editing the file itself? =

Yes and no, actually only some options (theme selection + animation options) can be adjusted at the plugin options page.

== Changelog ==

`0.1.3
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