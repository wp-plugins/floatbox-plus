<?php
/*
Author: Oliver Schaal
Plugin Name: Floatbox Plus
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/plugins/floatbox-plus
Version: 0.3.2
Description: Seamless integration of Floatbox (jscript similar to Lightview/Lightbox/Shadowbox/Fancybox/Thickbox) to create nice overlay display images/videos without the need to change html. Because Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> is licensed under the terms of <a href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 License</a> it isn't included (not GPL compatible). Just use the included download option or read the instructions for manual installation on <a href="http://blog.splash.de/plugins/floatbox-plus">my website</a> or in the readme.txt.
*/

global $wp_version;
define('WPV27', version_compare($wp_version, '2.7', '>='));

class floatbox_plus {

    // version
    var $version = '0.3.2';

    // put all options in
    var $options = array();

    // put all video tags in
    var $video = array();

    // backup dir and file
    var $bkp_folder = '.floatbox.bkp';

    function floatbox_plus() {
        $this->__construct();
    }

    function __construct() {
        //load language
        if (function_exists('load_plugin_textdomain'))
        load_plugin_textdomain('floatboxplus', '/wp-content/plugins/floatbox-plus/langs');

        // get options
        $this->options = get_option('floatbox_plus');
        (!is_array($this->options) && !empty($this->options)) ? $this->options = unserialize($this->options) : $this->options = false;
        
        // install default options
        register_activation_hook(__FILE__, array(&$this, 'install'));

        // uninstall features
        register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));

        // quick and dirty fix for wp 2.7
        if (WPV27 == true) {
            add_action('admin_head', array(&$this, 'backup_before_update'), 10, 2);
        }

        // nagscreen at plugins page
        add_action( 'after_plugin_row', array(&$this, 'plugin_version_nag') );

        // add wp-filter
        add_filter('the_content', array(&$this, 'change_content'), 150);

        //add wp-action
        add_action('wp_head', array(&$this, 'add_header'));
        add_action('admin_menu', array(&$this, 'AdminMenu'));

        //add wp-shortcodes
        if($this->options['load_gallery'] && WPV27 == false)
        add_filter('attachment_link', array(&$this, 'direct_image_urls_for_galleries'), 10, 2);

        // add MCE Editor Button
        if($this->options['show_video']) {
            add_action('init', array(&$this, 'mceinit'));
            add_action('admin_print_scripts', array(&$this, 'add_admin_header'));
        }

        // plugin page links
        add_filter(
            'plugin_action_links',
            array(
                $this,
                'set_plugin_actions'
                ),
            10,
            2
            );

		// define object targets and links
		$this->video['youtube']['height'] = floor($this->options['video_width']*14/17);
		$this->video['youtube']['preview_height'] = floor($this->options['video_preview_width']*14/17);
		$this->video['youtube']['iphone'] = '<object width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] . '"><param name="movie" value="http://www.youtube.com/v/###VID###"></param><embed src="http://www.youtube.com/v/###VID###" type="application/x-shockwave-flash" width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] .'"></embed></object><br />';
        $this->video['youtube']['target'] = '<a href="http://www.youtube.com/v/###VID###&amp;autoplay=1" title="###THING###" class="floatbox" rel="floatbox" rev="width:' . $this->options['video_width'] . ' height:' . $this->video['youtube']['height'] . ' scrolling:no"><img src="###IMAGE###" class="videoplay" width="' . $this->options['video_preview_width'] . '" height="' . $this->video['youtube']['preview_height'] . '" alt="###THING###" /></a><br />';
		$this->video['youtube']['link']   = "<a title=\"YouTube\" href=\"http://www.youtube.com/watch?v=###VID###\">YouTube ###TXT######THING###</a>";

		$this->video['youtubehq']['height'] = floor($this->options['video_width']*9/15.2);
		$this->video['youtubehq']['preview_height'] = floor($this->options['video_preview_width']*9/15.2);
        $this->video['youtubehq']['iphone'] = '<object width="' . $this->options['video_width'] . '" height="' . $this->video['youtubehq']['height'] . '"><param name="movie" value="http://www.youtube.com/v/###VID###"></param><embed src="http://www.youtube.com/v/###VID###" type="application/x-shockwave-flash" width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] .'"></embed></object><br />';
		$this->video['youtubehq']['target'] = '<a href="http://www.youtube.com/v/###VID###&amp;autoplay=1&amp;ap=%2526fmt%3D22" title="###THING###" class="floatbox" rel="floatbox" rev="width:' . $this->options['video_width'] . ' height:' . $this->video['youtubehq']['height'] . ' scrolling:no"><img src="###IMAGE###" class="videoplay" width="' . $this->options['video_preview_width'] . '" height="' . $this->video['youtube']['preview_height'] . '" alt="###THING###" /></a><br />';
		$this->video['youtubehq']['link']   = "<a title=\"YouTube\" href=\"http://www.youtube.com/watch?v=###VID###&amp;ap=%2526fmt%3D22\">YouTube ###TXT######THING###</a>";

		$this->video['vimeo']['height'] = floor($this->options['video_width'] * 3 / 4);
		$this->video['vimeo']['preview_height'] = floor($this->options['video_preview_width'] * 3 / 4);
		$this->video['vimeo']['target'] = '<a href="http://www.vimeo.com/moogaloop.swf?clip_id=###VID###"title="###THING###" class="floatbox" rel="floatbox" rev="width:' . $this->options['video_width'] . ' height:' . $this->video['youtube']['height'] . ' scrolling:no" alt="###THING###" /><img src="###IMAGE###" class="videoplay" width="' . $this->options['video_preview_width'] . '" height="' . $this->video['youtube']['preview_height'] . '" alt="###THING###" /></a><br />';
		$this->video['vimeo']['link'] = "<a title=\"vimeo\" href=\"http://www.vimeo.com/clip:###VID###\">vimeo ###TXT######THING###</a>";

		$this->video['local']['quicktime']['height'] = floor($this->options['video_width'] * 3 / 4);
		$this->video['local']['quicktime']['preview_height'] = floor($this->options['video_preview_width'] * 3 / 4);
		$this->video['local']['quicktime']['target'] = "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\" codebase=\"http://www.apple.com/qtactivex/qtplugin.cab\" width=\"" .  $this->options['video_width'] . "\" height=\"" . 	$this->video['local']['quicktime']['height'] . "\"><param name=\"src\" value=\"".get_option('siteurl')."###VID###\" /><param name=\"autoplay\" value=\"false\" /><param name=\"pluginspage\" value=\"http://www.apple.com/quicktime/download/\" /><param name=\"controller\" value=\"true\" /><!--[if !IE]> <--><object data=\"".get_option('siteurl')."###VID###\" width=\"" . $this->options['video_width'] . "\" height=\"" . 	$this->video['local']['quicktime']['height'] . "\" type=\"video/quicktime\"><param name=\"pluginurl\" value=\"http://www.apple.com/quicktime/download/\" /><param name=\"controller\" value=\"true\" /><param name=\"autoplay\" value=\"false\" /></object><!--> <![endif]--></object><br />";
		$this->video['local']['flashplayer']['height'] = floor($this->options['video_width'] * 93 / 112);
		$this->video['local']['flashplayer']['target'] =  "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"" . $this->options['video_width'] . "\" height=\"" . $this->video['local']['flashplayer']['height'] . "\"><param value=\"#FFFFFF\" name=\"bgcolor\" /><param name=\"movie\" value=\"".get_option('siteurl')."/wp-content/plugins/floatbox-plus/mediaplayer/player.swf\" /><param value=\"file=".get_option('siteurl')."###VID###&amp;showdigits=true&amp;autostart=false&amp;overstretch=false&amp;showfsbutton=false\" name=\"flashvars\" /><param name=\"wmode\" value=\"transparent\" /><!--[if !IE]> <--><object data=\"".get_option('siteurl')."/wp-content/plugins/floatbox-plus/mediaplayer/player.swf\" type=\"application/x-shockwave-flash\" height=\"" . $this->video['local']['flashplayer']['height'] . "\" width=\"" . $this->options['video_width'] . "\"><param value=\"#FFFFFF\" name=\"bgcolor\"><param value=\"file=".get_option('siteurl')."###VID###&amp;showdigits=true&amp;autostart=false&amp;overstretch=false&amp;showfsbutton=false\" name=\"flashvars\" /><param name=\"wmode\" value=\"transparent\" /></object><!--> <![endif]--></object><br />";
		$this->video['local']['target'] = "<object classid=\"clsid:22D6f312-B0F6-11D0-94AB-0080C74C7E95\" codebase=\"http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,7,1112\" width=\"".GENERAL_WIDTH."\" height=\"".VIDEO_HEIGHT."\" type=\"application/x-oleobject\"><param name=\"filename\" value=\"".get_option('siteurl')."###VID###\" /><param name=\"autostart\" value=\"false\" /><param name=\"showcontrols\" value=\"true\" /><!--[if !IE]> <--><object data=\"".get_option('siteurl')."###VID###\" width=\"".GENERAL_WIDTH."\" height=\"".VIDEO_HEIGHT."\" type=\"application/x-mplayer2\"><param name=\"pluginurl\" value=\"http://www.microsoft.com/Windows/MediaPlayer/\" /><param name=\"ShowControls\" value=\"true\" /><param name=\"ShowStatusBar\" value=\"true\" /><param name=\"ShowDisplay\" value=\"true\" /><param name=\"Autostart\" value=\"0\" /></object><!--> <![endif]--></object><br />";
		$this->video['local']['link'] = "<a title=\"Video File\" href=\"".get_option('siteurl')."###VID###\">Download Video</a>";
	}

    function set_plugin_actions($links, $file) {
        $plugin = plugin_basename(__FILE__);
        if ($file == $plugin && !$this->check_javascript()) {
            return array_merge(
                array(
                    sprintf(
                        '<a href="options-general.php?page=%s">%s</a>',
                        dirname($plugin).'/floatbox-download.php',
                        __('Download floatbox(.js)', 'floatboxplus') . '<br />'
                        )
                    ),
                $links
                );
        }
        return $links;
    }

    // quick and dirty fix for wp2.7/auto update: seems that there is no hook during the update system that can be used
    function backup_before_update() {
        if( preg_match('/upgrade-plugin(.*)plugin=floatbox-plus/i', $_SERVER['QUERY_STRING']) ) {
            $this->uninstall();
        }
    }

    // nagscreen at plugins page, based on the code of cformsII by Oliver Seidel
    function plugin_version_nag($plugin) {
        if (preg_match('/floatbox-plus/i',$plugin)) {
            $checkfile = "http://blog.splash.de/_chk/floatbox-plus.$this->version.chk";
            $this->plugin_version_get($checkfile);
        }
    }
    function plugin_version_get($checkfile, $tr=false) {
        $vcheck = wp_remote_fopen($checkfile);

        if($vcheck) {
            $status = explode('@', $vcheck);
            $theVersion = $status[1];
            $theMessage = $status[3];
            if( $theMessage ) {
                if($tr == true)
                    echo '</tr><tr>';
                $msg = __("Updatenotice for:", "floatboxplus").' <strong>'.$theVersion.'</strong><br />'.$theMessage;
                echo '<td colspan="5" class="plugin-update" style="line-height:1.2em;">'.$msg.'</td>';
            }
            if (version_compare($theVersion, $this->version) == 1) {
                $checkfile = "http://blog.splash.de/_chk/floatbox-plus.$theVersion.chk";
                $this->plugin_version_get($checkfile, true);
            }
        }
    }

    function AdminMenu()
    {
        $hook = add_options_page('FloatBox Plus', (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . @plugins_url('floatbox-plus/icon.png') . '" width="10" height="10" alt="Floatbox Plus - Icon" />' : '') . 'Floatbox Plus', 8, 'floatbox_plus', array(&$this, 'OptionsMenu'));
        if (function_exists('add_contextual_help') === true) {
            add_contextual_help($hook,
                sprintf('<a href="http://trac.splash.de/floatboxplus">%s</a><br /><a href="http://blog.splash.de/plugin/floatbox-plus/">%s</a>',
                    __('Ticketsystem/Wiki', 'floatboxplus'),
                    __('Plugin-Homepage', 'floatboxplus')
                )
            );
        }
    }

    function install()
    {
        //add default options
        if (empty($this->options)) {
            add_option('floatbox_plus', serialize(array(
                        'load_gallery' => true,
                        'show_video' => true,
                        'backup_floatbox' => true,
                        'fb_options' => true,           // floatbox
                        'fb_theme' => 'auto',           // general
                        'fb_doAnimations' => true,      // animations
                        'fb_resizeDuration' => 3.5,
                        'fb_imageFadeDuration' => 3.5,
                        'fb_overlayFadeDuration' => 4,
                        'fb_splitResize' => 'no',
                        'fb_startAtClick' => true,
                        'fb_zoomImageStart' => true,
                        'fb_liveImageResize' => false,
                        'video_showlink' => true,
                        'video_smallink' => true,
						'video_preview_width'=> '300',
                        'video_width' => '300',
                        'video_separator' => '- ',
                        'video_showinfeed' => true
                    )));
        } else {
            // update options for old installs
            $this->update();
        }

        // restore floatbox javascript, if backup exists and not already installed
        $bkp_folder = dirname(__FILE__) . '/../' . $this->bkp_folder;
        if (!$this->check_javascript()) {
            $this->restore_floatbox();
        }

        // delete backup folder
        if( is_writable($bkp_folder) )
        $this->delete_recursive($bkp_folder);

        return true;
    }

    function uninstall()
    {
        // backup floatbox, if it exists
        if( $this->options['backup_floatbox'] && $this->check_javascript() )
        $this->backup_floatbox();

        return true;
    }

	function update()
	{
		// add option, if they does not exist
		if(empty($this->options['video_preview_width']))
			$this->options['video_preview_width'] = $this->options['video_width'];

        // floatbox: general options
		if(empty($this->options['fb_options']))
			$this->options['fb_options'] = false;
		if(empty($this->options['fb_theme']))
			$this->options['fb_theme'] = 'auto';
        // floatbox: animation options
		if(empty($this->options['fb_doAnimations']))
			$this->options['fb_doAnimations'] = true;
		if(empty($this->options['fb_resizeDuration']))
			$this->options['fb_resizeDuration'] = 3.5;
		if(empty($this->options['fb_imageFadeDuration']))
			$this->options['fb_imageFadeDuration'] = 3.5;
		if(empty($this->options['fb_overlayFadeDuration']))
			$this->options['fb_overlayFadeDuration'] = 4;
		if(empty($this->options['fb_splitResize']))
			$this->options['fb_splitResize'] = 'no';
		if(empty($this->options['fb_startAtClick']))
			$this->options['fb_startAtClick'] = true;
		if(empty($this->options['fb_zoomImageStart']))
			$this->options['fb_zoomImageStart'] = true;
		if(empty($this->options['fb_liveImageResize']))
			$this->options['fb_liveImageResize'] = false;

		// update options
		update_option('floatbox_plus', serialize($this->options));
	}

    //backups floatbox javascript
    function backup_floatbox()
    {
        // floatbox folders to copy
        $dirnames = array('floatbox');
        $destination = dirname(__FILE__) . '/../' . $this->bkp_folder;

        if(is_writable(dirname(__FILE__).'/..')) {
            if(!is_dir($destination) ) {
                mkdir($destination, 0777);
            }
        } else {
            return false;
        }

        for($i=0; $i < count($dirnames); $i++) {
            // subfolder to copy
            $folder = $dirnames[$i];

            // source files with foldernames
            $source = dirname(__FILE__);
            $source .= "/";
            $source .= $folder;

            // copy files
            $this->copy_recursive($source, $destination.'/'.$folder);
        }

        return true;
    }

    // restores floatbox javascript
    function restore_floatbox()
    {
        $source = dirname(__FILE__)  . '/../' . $this->bkp_folder;

        if(is_writable(dirname(__FILE__)) && is_dir($source)) {
            $this->copy_recursive($source, dirname(__FILE__));
        }

        return true;
    }

    function copy_recursive($source, $dest) {
        // Simple copy for a file
        if (is_file($source)) {
            $c = copy($source, $dest);
            chmod($dest, 0777);
            return $c;
        }

        // Make destination directory
        if (!is_dir($dest)) {
            $oldumask = umask(0);
            mkdir($dest, 0777);
            umask($oldumask);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == "." || $entry == "..") {
                continue;
            }

            // Deep copy directories
            if ($dest !== "$source/$entry") {
                $this->copy_recursive("$source/$entry", "$dest/$entry");
            }
        }

        // Clean up
        $dir->close();

        return true;
    }

    function delete_recursive($dirname)
    {
        // recursive function to delete
        // all subdirectories and contents:
        if(is_dir($dirname)) $dir_handle=opendir($dirname);

        while($file=readdir($dir_handle)) {
            if( $file != "." && $file != ".." ) {
                if(!is_dir($dirname."/".$file)) {
                    unlink ($dirname."/".$file);
                } else {
                    $this->delete_recursive($dirname."/".$file);
                }
            }
        }

        closedir($dir_handle);
        rmdir($dirname);

        return true;
    }

    function check_javascript() {

        if(!is_dir(dirname(__FILE__).'/floatbox')) {
            return false;
        }

        if(!file_exists(dirname(__FILE__).'/floatbox/floatbox.js')) {
            return false;
        }

        if(!file_exists(dirname(__FILE__).'/floatbox/floatbox.css')) {
            return false;
        }

        if(!is_dir(dirname(__FILE__).'/floatbox/graphics')) {
            return false;
        }

        if(!is_dir(dirname(__FILE__).'/floatbox/languages')) {
            return false;
        }

        return true;
    }

    function change_content($content)
    {
        global $post;

        // RegEx
        $pattern['image'] = "/<a(.*?)href=('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)>/i";
        $pattern['video'][1] = "/\[(youtube|youtubehq|vimeo) ([[:graph:]]+) (nolink)\]/";
        $pattern['video'][2] = "/\[(youtube|youtubehq|vimeo) ([[:graph:]]+) ([[:print:]]+)\]/";
        $pattern['video'][3] = "/\[(youtube|youtubehq|vimeo) ([[:graph:]]+)\]/";

        // makes a set of pictures to a gallery
        $replacement = '<a$1href=$2$3$4$5 class="floatbox" rel="floatbox.%LIGHTID%"$6>';
        $content = preg_replace($pattern['image'], $replacement, $content);
        $content = str_replace("%LIGHTID%", $post->ID, $content);

        //copy title from img-tag to a-href-tag
        // $pattern['title'] = "/<a(.*)[^title=](.*?)><img(.*?)title=('|\")([A-Za-z0-9\/_\.\~\:-]*?)('|\")([^\>]*?)><\/a>/i";
        // <img class="alignright size-medium wp-image-25" title="blog.splash.de" src="http://testdrive.splash.de/wp/wp-content/uploads/2008/09/blogsplashde-300x180.jpg" alt="blogsplashde" height="180" width="300">
        $pattern['title'] = "/<a([^\>]*)><img(.*?)title=\"([^\"]*)\"([^\>]*)><\/a>/ui";
        $replacement = '<a$1 rev="caption:`$3`"><img$2title="$3"$4></a>';
        // $replacement='REPLACEMENT';
        $content = preg_replace($pattern['title'], $replacement, $content);


        if($this->options['show_video']) {
            $content = preg_replace_callback($pattern['video'][1], array(&$this, 'video_callback'), $content);
            $content = preg_replace_callback($pattern['video'][2], array(&$this, 'video_callback'), $content);
            $content = preg_replace_callback($pattern['video'][3], array(&$this, 'video_callback'), $content);
        }

        return $content;
    }

    // video callback logic
    function video_callback($match) {
        $output = '';
        //$output = '<div class="lp_videoimage"><div id="lp_playbutton"><img src="' . get_option('siteurl') . '/wp-content/plugins/floatbox-plus/img/playbutton.png" width="100" height="100" alt="" /></div>';

        // insert plugin link
        if (!is_feed()) {
            switch ($match[1]) {
                case "youtube":
                    if ($this->is_iPhone() == true) {
                        $output .= $this->video['youtube']['iphone'];
                    } else {
                        $output .= $this->video['youtube']['target'];
                    }
                    break;
				case "youtubehq":
					if ($this->is_iPhone() == true) {
						$output .= $this->video['youtubehq']['iphone'];
					} else {
						$output .= $this->video['youtubehq']['target'];
					}
					break;
                case "vimeo": $output .= $this->video['vimeo']['target'];
                    break;
                case "google": $output .= $this->video['google']['target'];
                    break;
                case "myvideo": $output .= $this->video['myvideo']['target'];
                    break;
                case "clipfish": $output .= $this->video['clipfish']['target'];
                    break;
                case "sevenload": $output .= $this->video['sevenload']['target'];
                    break;
                case "revver": $output .= $this->video['revver']['target'];
                    break;
                case "metacafe": $output .= $this->video['metacafe']['target'];
                    break;
                case "yahoo": $output .= $this->video['yahoo']['target'];
                    break;
                case "ifilm": $output .= $this->video['ifilm']['target'];
                    break;
                case "myspace": $output .= $this->video['myspace']['target'];
                    break;
                case "brightcove": $output .= $this->video['brightcove']['target'];
                    break;
                case "aniboom": $output .= $this->video['aniboom']['target'];
                    break;
                case "guba": $output .= $this->video['guba']['target'];
                    break;
                case "gamevideo": $output .= $this->video['gamevideo']['target'];
                    break;
                case "vsocial": $output .= $this->video['vsocial']['target'];
                    break;
                case "dailymotion": $output .= $this->video['dailymotion']['target']; $match[3] = "nolink";
                    break;
                case "garagetv": $output .= $this->video['garage']['target']; $match[3] = "nolink";
                    break;
                case "veoh": $output .= $this->video['veoh']['target'];
                    break;
                case "gametrailers": $output .= $this->video['gametrailers']['target'];
                    break;
                case "local":
                    if (preg_match("%([[:print:]]+).(mov|qt|MOV|QT)$%", $match[2])) {
                        $output .= $this->video['local']['quicktime']['target'];
                        break;

                    } elseif (preg_match("%([[:print:]]+).(wmv|mpg|mpeg|mpe|asf|asx|wax|wmv|wmx|avi|WMV|MPG|MPEG|MPE|ASF|ASX|WAX|WMV|WMX|AVI)$%", $match[2])) {
                        $output .= $this->video['local']['target'];
                        break;

                    } elseif (preg_match("%([[:print:]]+).(swf|flv|SWF|FLV)$%", $match[2])) {
                        $output .= $this->video['local']['flashplayer']['target'];
                        break;
                    }
                    break;

                case "video":
                    if (preg_match("%([[:print:]]+).(mov|qt|MOV|QT)$%", $match[2])) {
                        $output .= $this->video['quicktime']['target'];
                        break;

                    } elseif (preg_match("%([[:print:]]+).(wmv|mpg|mpeg|mpe|asf|asx|wax|wmv|wmx|avi|WMV|MPG|MPEG|MPE|ASF|ASX|WAX|WMV|WMX|AVI)$%", $match[2])) {
                        $output .= $this->video['video']['target'];
                        break;

                    } elseif (preg_match("%([[:print:]]+).(swf|flv|SWF|FLV)$%", $match[2])) {
                        $output .= $this->video['flashplayer']['target'];
                        break;
                    }
                    break;

                default:
                    break;
            }

            if ($this->options['video_showlink'] == true) {
                if ($match[3] != "nolink") {
                    if ($this->options['video_smallink'])
                    $output .= "<small>";

                    switch ($match[1]) {
                        case "youtube": $output .= $this->video['youtube']['link'];
                            break;
                        // TODO: unsure?
						case "youtubehq": $output .= $this->video['youtubehq']['link'];
                            break;
                        case "vimeo": $output .= $this->video['vimeo']['link'];
                            break;
                        case "google": $output .= $this->video['google']['link'];
                            break;
                        case "myvideo": $output .= $this->video['myvideo']['link'];
                            break;
                        case "clipfish": $output .= $this->video['clipfish']['link'];
                            break;
                        case "sevenload": $output .= $this->video['sevenload']['link'];
                            break;
                        case "revver": $output .= $this->video['revver']['link'];
                            break;
                        case "metacafe": $output .= $this->video['metacafe']['link'];
                            break;
                        case "yahoo": $output .= $this->video['yahoo']['link'];
                            break;
                        case "ifilm": $output .= $this->video['ifilm']['link'];
                            break;
                        case "myspace": $output .= $this->video['myspace']['link'];
                            break;
                        case "brightcove": $output .= $this->video['brightcove']['link'];
                            break;
                        case "aniboom": $output .= $this->video['aniboom']['link'];
                            break;
                        case "guba": $output .= $this->video['guba']['link'];
                            break;
                        case "gamevideo": $output .= $this->video['gamevideo']['link'];
                            break;
                        case "vsocial": $output .= $this->video['vsocial']['link'];
                            break;
                        case "veoh": $output .= $this->video['veoh']['link'];
                            break;
                        case "gametrailers": $output .= $this->video['gametrailers']['link'];
                            break;
                        case "local": $output .= $this->video['local']['link'];
                            break;
                        case "video": $output .= $this->video['video']['link'];
                            break;
                        default:
                            break;
                    }

                    if ($this->options['video_smallink'])
                    $output .= "</small>";
                }
            }
        } elseif ($this->options['video_showinfeed'] == true) {
            $output .= __('[There is a video that cannot be displayed in this feed. ', 'floatboxplus').'<a href="'.get_permalink().'">'.__('Visit the blog entry to see the video.]','floatboxplus').'</a>';
        }

        // postprocessing
        // first replace video_separator
        $output = str_replace("###TXT###", $this->options['video_separator'], $output);

        // special handling of Yahoo! Video IDs
        if ($match[1] == "yahoo") {
            $temp = explode(".", $match[2]);
            $match[2] = $temp[1];
            $output = str_replace("###YAHOO###", $temp[0], $output);
        }
        // replace video IDs and text
        $output = str_replace("###VID###", $match[2], $output);
        $output = str_replace("###THING###", $match[3], $output);

        //get video images url and replace
		$output = str_replace("###IMAGE###", $this->get_videopreviewimage($match[1], $match[2]), $output);

        // add HTML comment
        if (!is_feed())
        $output .= "\n<!-- generated by WordPress Plugin FloatBox Plus -->\n";

        //$output .= "</div>";

        return $output;
    }

	function get_videopreviewimage($service, $id) {
		switch($service) {
			case "youtube":
            case "youtubehq":
				//$output = "http://img.youtube.com/vi/" . $id . "/0.jpg";
				$output = "http://img.youtube.com/vi/" . $id . "/hqdefault.jpg";
				break;
			case "vimeo":
				// check if $id is numeric
				if(!is_numeric($id)) {
                    return false;
                }

				$api_link = 'http://vimeo.com/api/clip/' . $id . '.xml';

                // Get preview image from vimeo
				$clip = simplexml_load_file($api_link);
				$output = $clip->clip->thumbnail_large;

				// check response, if nothing in output -> standard image
				if(empty($output))
					return false;
				break;

			default:
					return false;
                break;
		}

        return $output;
    }

    function is_iPhone() {
        $uas = array ( 'iPhone', 'iPod');

        foreach ( $uas as $useragent ) {
            if (eregi($useragent, $_SERVER['HTTP_USER_AGENT'])) {
                return true;
            } else {
                return false;
            }
        }
    }

    function add_header() {
        $path = plugins_url()."/floatbox-plus";

        $script = "\n<!-- FloatBox Plus Plugin -->\n";
        $script .= "<script type=\"text/javascript\">\nfbPageOptions =  {\n";
        $script .= "urlGraphics: '".$path."/floatbox/graphics/',\n";
        $script .= "urlLanguages: '".$path."/floatbox/languages/',\n";
        // floatbox options
        if ($this->options['fb_options'] == true) {
            // general options
            $script .= "theme: '".$this->options['fb_theme']."',\n";
            // animation options
            $script .= "doAnimations: ".$this->boolToString($this->options['fb_doAnimations']).",\n";
            $script .= "resizeDuration: ".$this->options['fb_resizeDuration'].",\n";
            $script .= "imageFadeDuration: ".$this->options['fb_imageFadeDuration'].",\n";
            $script .= "overlayFadeDuration: ".$this->options['fb_overlayFadeDuration'].",\n";
            $script .= "splitResize: '".$this->options['fb_splitResize']."',\n";
            $script .= "startAtClick: ".$this->boolToString($this->options['fb_startAtClick']).",\n";
            $script .= "zoomImageStart: ".$this->boolToString($this->options['fb_zoomImageStart']).",\n";
            $script .= "liveImageResize: ".$this->boolToString($this->options['fb_liveImageResize']).",\n";
        }
        $script .= "};\n</script>\n";
        $script .= "<script type=\"text/javascript\" src=\"$path/floatbox/floatbox.js\"></script>\n";
        $script .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$path/floatbox/floatbox.css\" media=\"screen\" />\n";
        $script .= "<!-- FloatBox Plus Plugin -->\n";

        echo $script;
    }

    function OptionsMenu()
    {

        if (!empty($_POST)) {

            // floatbox: general options
            if($_POST['fb_options'] == 'true') {
                $this->options['fb_options'] = true;
            } else {
                $this->options['fb_options'] = false;
            }
            if(!empty($_POST['fb_theme'])) {
                $this->options['fb_theme'] = $_POST['fb_theme'];
            }
            // floatbox: animation options
            if($_POST['fb_doAnimations'] == 'true') {
                $this->options['fb_doAnimations'] = true;
            } else {
                $this->options['fb_doAnimations'] = false;
            }
            // if(!empty($_POST['fb_resizeDuration']))
                $this->options['fb_resizeDuration'] = $_POST['fb_resizeDuration'];

            // if(!empty($_POST['fb_imageFadeDuration']))
                $this->options['fb_imageFadeDuration'] = $_POST['fb_imageFadeDuration'];

            // if(!empty($_POST['fb_overlayFadeDuration']))
                $this->options['fb_overlayFadeDuration'] = $_POST['fb_overlayFadeDuration'];

            if(!empty($_POST['fb_splitResize']))
                $this->options['fb_splitResize'] = $_POST['fb_splitResize'];

            if($_POST['fb_startAtClick'] == 'true') {
                $this->options['fb_startAtClick'] = true;
            } else {
                $this->options['fb_startAtClick'] = false;
            }
            if($_POST['fb_zoomImageStart'] == 'true') {
                $this->options['fb_zoomImageStart'] = true;
            } else {
                $this->options['fb_zoomImageStart'] = false;
            }
            if($_POST['fb_liveImageResize'] == 'true') {
                $this->options['fb_liveImageResize'] = true;
            } else {
                $this->options['fb_liveImageResize'] = false;
            }

            // option 'load_gallery'
            if($_POST['load_gallery'] == 'true') {
                $this->options['load_gallery'] = true;
            } else {
                $this->options['load_gallery'] = false;
            }

            // option 'show_video'
            if($_POST['show_video'] == 'true') {
                $this->options['show_video'] = true;
            } else {
                $this->options['show_video'] = false;
            }

            // option 'backup_floatbox'
            if($_POST['backup_floatbox'] == 'true') {
                $this->options['backup_floatbox'] = true;
            } else {
                $this->options['backup_floatbox'] = false;
            }

            // option 'video_showlink'
            if($_POST['video_showlink'] == 'true') {
                $this->options['video_showlink'] = true;
            } else {
                $this->options['video_showlink'] = false;
            }

            // option 'video_smallink'
            if($_POST['video_smallink'] == 'true') {
                $this->options['video_smallink'] = true;
            } else {
                $this->options['video_smallink'] = false;
            }

            //option 'video_separator'
            if(!empty($_POST['video_separator'])) {
                $this->options['video_separator'] = $_POST['video_separator'];
            }

			//option 'video_preview_width'
			if(!empty($_POST['video_preview_width'])) {
				$this->options['video_preview_width'] = $_POST['video_preview_width'];
			}

            //option 'video_width'
            if(!empty($_POST['video_width'])) {
                $this->options['video_width'] = $_POST['video_width'];
            }

            // option 'video_showinfeed'
            if($_POST['video_showinfeed'] == 'true') {
                $this->options['video_showinfeed'] = true;
            } else {
                $this->options['video_showinfeed'] = false;
            }

            // update options
            update_option('floatbox_plus', serialize($this->options));

            // echo successfull update
            echo '<div id="message" class="updated fade"><p><strong>' . __('Options saved.', 'floatboxplus') . '</strong></p></div>';
        }

        ?>
<div class="wrap">
    <h2>FloatBox Plus</h2>

            <?php
            // echo error if floatbox js / css isn't copied to plugin dir
            $plugin = plugin_basename(__FILE__);
            if(!$this->check_javascript()) {
                echo '<div id="message" class="error"><p><strong>' . __('FloatBox Javascript isn\'t copied to the plugin directory. See installation instructions for further details <br />or try the new download option: ', 'floatboxplus') . '</strong>';
                printf(
                        '<a href="options-general.php?page=%s">%s</a>',
                        dirname($plugin).'/floatbox-download.php',
                        __('Download floatbox(.js) from randomous.com', 'floatboxplus') . '<br />'
                        );
                echo '</p></div>';
            }
            ?>

    <h3><?php _e('General Settings', 'floatboxplus');  ?></h3>

    <form action="options-general.php?page=floatbox_plus" method="post">
        <table class="form-table">
            <tbody>

                        <?php // Activate Gallery? ?>
                        <?php if( WPV27 == false ) : ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo __('Activate FloatBox for [gallery]?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="load_gallery" size="1">
                            <option value="true" <?php if ($this->options['load_gallery'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['load_gallery'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php echo __('If activated, it shows the wordpress gallery with floatbox', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php // Activate Movies? ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo __('Activate FloatBox for Videos?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="show_video" size="1">
                            <option value="true" <?php if ($this->options['show_video'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['show_video'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php echo __('Implements the video function. ATTENTION: It only works, if you do not have the embedded video plugin activated', 'floatboxplus'); ?>
                    </td>
                </tr>

                        <?php // Create Backup of FloatBox Javascript if plug-in deactivates? ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo __('Backup FloatBox Javascript during Update?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="backup_floatbox" size="1">
                            <option value="true" <?php if ($this->options['backup_floatbox'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['backup_floatbox'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php echo __('Backups the floatbox javascript files for upgrade-reasons. After uprading to a new floatbox-plus version, it is needless to copy the javascript files back in the plugin directory. ', 'floatboxplus'); ?>
                    </td>
                </tr>


            </tbody>
        </table>

        <h3><?php _e('Floatbox Options', 'floatboxplus'); ?></h3>

        <table class="form-table">
            <tbody>
                <?php // activate the floatbox options ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Change the options of floatbox.js?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_options" size="1">
                            <option value="true" <?php if ($this->options['fb_options'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_options'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Overwrite the settings in floatbox.js with the values set here.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // floatbox theme ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Theme', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_theme" size="1">
                            <option value="auto" <?php if ($this->options['fb_theme'] == 'auto' ) { ?>selected="selected"<?php } ?>><?php _e('auto', 'floatboxplus'); ?></option>
                            <option value="black" <?php if ($this->options['fb_theme'] == 'black' ) { ?>selected="selected"<?php } ?>><?php _e('black', 'floatboxplus'); ?></option>
                            <option value="white" <?php if ($this->options['fb_theme'] == 'white' ) { ?>selected="selected"<?php } ?>><?php _e('white', 'floatboxplus'); ?></option>
                            <option value="blue" <?php if ($this->options['fb_theme'] == 'blue' ) { ?>selected="selected"<?php } ?>><?php _e('blue', 'floatboxplus'); ?></option>
                            <option value="yellow" <?php if ($this->options['fb_theme'] == 'yellow' ) { ?>selected="selected"<?php } ?>><?php _e('yellow', 'floatboxplus'); ?></option>
                            <option value="red" <?php if ($this->options['fb_theme'] == 'red' ) { ?>selected="selected"<?php } ?>><?php _e('red', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Color theme. \'Auto\' will use the black theme for images, white for iframe content, and blue for flash and quicktime.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // activate the floatbox options ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('doAnimations', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_doAnimations" size="1">
                            <option value="true" <?php if ($this->options['fb_doAnimations'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_doAnimations'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Shorthand for resizeDuration=0, imageFadeDuration=0 and zoomImageStart=false.', 'floatboxplus'); ?>
                    </td>
                </tr>
                        <?php // resizeDuration ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('resizeDuration', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['fb_resizeDuration'] ?>" name="fb_resizeDuration" id="fb_resizeDuration" size="5" maxlength="3" />
                        <br />
                        <?php _e('Controls the speed at which animated resizing occurs. 0 = no resize animation, 1 is fast, 10 is slooow. These are unit-less numbers, and don\'t equate to a fixed time period. Larger size changes will take longer than smaller size changes.', 'floatboxplus'); ?>
                    </td>
                </tr>
                        <?php // imageFadeDuration ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('imageFadeDuration', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['fb_imageFadeDuration'] ?>" name="fb_imageFadeDuration" id="fb_imageFadeDuration" size="5" maxlength="3" />
                        <br />
                        <?php _e('Controls the speed of the opacity fade-in for images as they come into the display. 0 = no image fade-in, 1 is fast, 10 is slooow. These too are unit-less numbers.', 'floatboxplus'); ?>
                    </td>
                </tr>
                        <?php // fb_overlayFadeDuration ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('overlayFadeDuration', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['fb_overlayFadeDuration'] ?>" name="fb_overlayFadeDuration" id="fb_overlayFadeDuration" size="5" maxlength="3" />
                        <br />
                        <?php _e('Controls the speed of the opacity fade-in and fade-out for the translucent overlay which covers the host page. 0 = no overlay fading in or out, 1 is fast, 10 is slooow.  Unit-less.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // splitResize ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('splitResize', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_splitResize" size="1">
                            <option value="no" <?php if ($this->options['fb_splitResize'] == 'no' ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                            <option value="auto" <?php if ($this->options['fb_splitResize'] == 'auto' ) { ?>selected="selected"<?php } ?>><?php _e('auto', 'floatboxplus'); ?></option>
                            <option value="wh" <?php if ($this->options['fb_splitResize'] == 'wh' ) { ?>selected="selected"<?php } ?>><?php _e('wh', 'floatboxplus'); ?></option>
                            <option value="hw" <?php if ($this->options['fb_splitResize'] == 'hw' ) { ?>selected="selected"<?php } ?>><?php _e('hw', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Default animated resizing of floatbox resizes width, height, top and left simultaneously. The settings other than \'no\' give sequenced animation where the X and Y dimensions are resized seperately. The two options \'wh\' and \'hw\' force either width or height to always go first. The better splitResize option is probably \'auto\'.  This will always do the smallest dimension first. Using a splitResize of auto prevents unaesthetic resize behaviour of initially bloating up in the larger dimension.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // startAtClick ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('startAtClick', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_startAtClick" size="1">
                            <option value="true" <?php if ($this->options['fb_startAtClick'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_startAtClick'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('If true (and resizeDuration is not 0) floatbox will expand out from the clicked anchor and shrink back to that anchor when closed. If false, floatbox will start and end from the center of the screen.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // zoomImageStart ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('zoomImageStart', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_zoomImageStart" size="1">
                            <option value="true" <?php if ($this->options['fb_zoomImageStart'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_zoomImageStart'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('If true (and resizeDuration is not 0) images will expand out from the clicked thumbnail on start and collapse back to the thumbnail on exit.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // liveImageResize ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('liveImageResize', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_liveImageResize" size="1">
                            <option value="true" <?php if ($this->options['fb_liveImageResize'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_liveImageResize'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('If true (and resizeDuration is not 0) images will remain displayed while they are being resized. If false they will be hidden during a resize and the "loading" gif displayed in their place.', 'floatboxplus'); ?>
                    </td>
                </tr>
            </tbody>
         </table>

        <p><?php _e('For more information about the many other options floatbox offers (and which aren\'t integrated yet in the plugin), take a look at the homepage:', 'floatboxplus'); ?> <a href="http://randomous.com/tools/floatbox/">Link</a></p>

        <h3><?php _e('Video Options', 'floatboxplus'); ?></h3>

        <table class="form-table">
            <tbody>
                <?php // Show link under Videos? ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Show Links under videos?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="video_showlink" size="1">
                            <option value="true" <?php if ($this->options['video_showlink'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['video_showlink'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Show a link to the original site of the video', 'floatboxplus'); ?>
                    </td>
                </tr>

                        <?php // Show link under videos in small? ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Show a small Link under the Video?', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <select name="video_smallink" size="1">
                            <option value="true" <?php if ($this->options['video_smallink'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['video_smallink'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('If no, it will show a bigger text', 'floatboxplus'); ?>


                    </td>
                </tr>

                        <?php // Linktext ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('Separator', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['video_separator'] ?>" name="video_separator" id="video_separator" size="5" maxlength="3" />
                        <br />
                        <?php _e('Defines the separator between the service (eg. YouTube) and your comment', 'floatboxplus'); ?>
                    </td>
                </tr>

                        <?php // Show in Feed? ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('Show in Feed?', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <select name="video_showinfeed" size="1">
                            <option value="true" <?php if ($this->options['video_showinfeed'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['video_showinfeed'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('You can choose, if you want to show the video in the feed. Currently, it is only possible to show a link to the video', 'floatboxplus'); ?>
                    </td>
                </tr>

					<?php // Video Preview Image Width ?>
					<tr valign="top">
						<th scope="row">
							<label><?php _e('Video Preview Width', 'floatboxplus'); ?>  (250px - 800px)</label>
						</th>
						<td>
							<input type="text" value="<?php echo $this->options['video_preview_width'] ?>" name="video_preview_width" id="video_preview_width" size="5" maxlength="3" />
							<br />
							<?php _e('Choose the width of the preview images for the videos', 'floatboxplus'); ?>
						</td>
					</tr>
                    
                        <?php // Video Width ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Video Width', 'floatboxplus'); ?>  (250px - 800px)</label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['video_width'] ?>" name="video_width" id="video_width" size="5" maxlength="3" />
                        <br />
                        <?php _e('You can choose, what width the video and image have', 'floatboxplus'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="fb_submit" id="fb_submit" value="1" />
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Update Options »', 'floatboxplus'); ?>" />
        </p>
    </form>

    <p><small>Video Icon from <a href="http://www.famfamfam.com">famfamfam </a>. Special thanks to Thorsten Puzich and his plugin <a href="http://www.puzich.com/wordpress-plugins/lightview">Lightview Plus</a>, whose code i adapted to be used with floatbox instead of lightview.</small></p>

</div>
        <?php
    }

    function mcebutton($buttons) {
        array_push($buttons, "|", "floatboxplus");
        return $buttons;
    }

    function mceplugin($ext_plu) {
        if (is_array($ext_plu) == false) {
            $ext_plu = array();
        }

        $url = get_option('siteurl')."/wp-content/plugins/floatbox-plus/editor_plugin.js";
        $result = array_merge($ext_plu, array("floatboxplus" => $url));
        return $result;
    }

    function mceinit() {
        if (function_exists('load_plugin_textdomain')) load_plugin_textdomain('floatbox-plus', dirname(__FILE__).'/langs');
        if ( 'true' == get_user_option('rich_editing') ) {
            add_filter('mce_external_plugins', array(&$this, 'mceplugin'), 0);
            add_filter("mce_buttons", array(&$this, 'mcebutton'), 0);
        }
    }

    function add_admin_header() {
        echo "<script type='text/javascript' src='".get_option('siteurl')."/wp-content/plugins/floatbox-plus/floatbox-plus.js'></script>\n";
    }

	function direct_image_urls_for_galleries( $link, $id ) {
		if ( is_admin() ) return $link;

		$mimetypes = array( 'image/jpeg', 'image/png', 'image/gif' );

		$post = get_post( $id );

		if ( in_array( $post->post_mime_type, $mimetypes ) )
			return wp_get_attachment_url( $id );
		else
			return $link;
	}

    function boolToString($bool) {
        if ($bool == 0)
            return 'false';
        return 'true';
    }
} // end class

/*
   if function simplexml_load_file is not compiled into php
   use simplexml.class.php
*/
if(!function_exists("simplexml_load_file")) {
	require_once('libs/simplexml.class.php');
    function simplexml_load_file($file) {
        $sx = new simplexml;
        return $sx->xml_load_file($file);
    }
}

//initalize class
if (class_exists('floatbox_plus'))
$floatbox_plus = new floatbox_plus();
