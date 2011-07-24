<?php
/*
Plugin Name: Floatbox Plus
Plugin URI: http://blog.splash.de/plugins/floatbox-plus
Author: Oliver Schaal
Author URI: http://blog.splash.de/
Website link: http://blog.splash.de/
Version: 1.4.4
Description: Seamless integration of Floatbox (jscript similar to Lightview/Lightbox/Shadowbox/Fancybox/Thickbox) to create nice overlay display images/videos without the need to change html. Cause the license of Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> is not GPL compatible, it isn't bundled with the plugin. Please read the instructions for manual installation on <a href="http://blog.splash.de/plugins/floatbox-plus">my website</a> or in the readme.txt.
*/

/*  Copyright 2009  Oliver Schaal  (email : maverick@unrealextreme.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// TODO: logoless player: ?modestbranding=1
// http://youtube-global.blogspot.com/2011/06/next-step-in-embedded-videos-hd-preview.html */


if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

global $wp_version;
define('FBP_URLPATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ).'/' );
define('FBP_PATH', WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ).'/' );
define('FBP_WPV27', version_compare($wp_version, '2.7', '>='));
define('FBP_WPV28', version_compare($wp_version, '2.8', '>='));
// define('WPV29', version_compare($wp_version, '2.9', '>='));
define('FBP_PHP5', version_compare(PHP_VERSION, '5.0.0', '>='));

class floatbox_plus {

    // version
    var $version;

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
            load_plugin_textdomain('floatboxplus', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // set version
        $this->version = $this->get_version();

        // get options
        $this->options = get_option('floatbox_plus');
        (!is_array($this->options) && !empty($this->options)) ? $this->options = unserialize($this->options) : $this->options = false;

        // install default options
        register_activation_hook(__FILE__, array(&$this, 'install'));

        // uninstall features
        register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));

        // quick and dirty fix for wp 2.7
        if (FBP_WPV27 == true) {
            add_action('admin_head', array(&$this, 'backup_before_update'), 10, 2);
        }

	// more setup links
	add_filter('plugin_row_meta', array(&$this, 'register_plugin_links'), 10, 2);

        // nagscreen at plugins page
        add_action( 'after_plugin_row', array(&$this, 'plugin_version_nag') );

        // add wp-filter
        add_filter('the_content', array(&$this, 'change_content'), 150);

        //add wp-action
        add_action('wp_head', array(&$this, 'add_header'));
        add_action('admin_menu', array(&$this, 'AdminMenu'));

        if (function_exists('wp_enqueue_scripts')) {
            add_action('wp_enqueue_scripts', array(&$this, 'enqueueJS'));
            add_action('wp_enqueue_scripts', array(&$this, 'enqueueStyle'));
        }

        //add wp-shortcodes
        if($this->options['load_gallery'] && FBP_WPV28 == true)
        add_filter('attachment_link', array(&$this, 'direct_image_urls_for_galleries'), 10, 2);

        // add MCE Editor Button
        if($this->options['show_video']) {
            //add_action('init', array(&$this, 'mceinit'));
            /* Add the button to FloatBoxMCE */
            include_once (dirname(__FILE__) . '/tinymce/tinymce.php');
            if (class_exists("FloatBoxMCE")) {
                $floatboxmce_button = new FloatBoxMCE ();
            }
            if (FBP_WPV28) {
                add_action('admin_enqueue_scripts', array(&$this, 'enqueueAdmin'));
            } else {
                add_action('admin_print_scripts', array(&$this, 'add_admin_header'));
            }
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

        $this->video['default']['target'] = '<a href="###EMBEDURL###" title="###VIDEOTITLE###" rev="group:%LIGHTID% width:###WIDTH### height:###HEIGHT### scrolling:no caption:`###VIDEOTITLE###`" class="floatbox" rel="floatbox.%LIGHTID%"><span class="fbp_videopreview" style="width: ###PREVIEWWIDTH###px;"><img src="###IMAGE###" width="###PREVIEWWIDTH###" height="###PREVIEWHEIGHT###" alt="###TITLE###" />###PLAYBUTTON###</span></a>';

        $this->video['default']['feed']   = '<img src="###IMAGE###" width="###PREVIEWWIDTH###" height="###PREVIEWHEIGHT###" alt="###TITLE###" />';
        $this->video['default']['link']   = "<a title=\"###VIDEOTITLE###\" href=\"###LINK### \">###PROVIDER### ###SEPERATOR######TITLE###</a>";

        $this->video['youtube']['iphone'] = '<object width="###WIDTH###" height="###HEIGHT###"><param name="movie" value="http://www.youtube.com/v/###VIDEOID###"></param><embed src="http://www.youtube.com/v/###VIDEOID###" type="application/x-shockwave-flash" width="###WIDTH###" height="###HEIGHT###"></embed></object>';

    }

    function register_plugin_links($links, $file) {
            $base = plugin_basename(__FILE__);
            if ($file == $base) {
                    $links[] = '<a href="options-general.php?page=' . $base .'">' . __('Settings','floatboxplus') . '</a>';
                    $links[] = '<a href="http://board.splash.de/forumdisplay.php?f=103">' . __('Support','floatboxplus') . '</a>';
                    $links[] = '<a href="http://twitter.com/cybiox9">' . __('Twitter','floatboxplus') . '</a>';
            }
            return $links;
    }

    // Returns the plugin version
    function get_version() {
            if(!function_exists('get_plugin_data')) {
                    if(file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
                            require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //2.3+
                    } elseif (file_exists(ABSPATH . 'wp-admin/admin-functions.php')) {
                            require_once(ABSPATH . 'wp-admin/admin-functions.php'); //2.1
                    } else {
                            return "ERROR: couldn't get version";
                    }
            }
            $data = get_plugin_data(__FILE__, false, false);

            return $data['Version'];
    }

    function set_plugin_actions($links, $file) {
        $plugin = plugin_basename(__FILE__);
        if ($file == $plugin && !$this->check_javascript()) {
            return array_merge(
                array(
                    sprintf(
                        '%s',
                        __('You need to download and install floatbox(.js), see installation instructions.', 'floatboxplus') . '<br />'
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
        // $hook = add_options_page('FloatBox Plus', (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . @plugins_url('floatbox-plus/icon.png') . '" width="10" height="10" alt="Floatbox Plus - Icon" />' : '') . 'Floatbox Plus', 8, 'floatbox_plus', array(&$this, 'OptionsMenu'));
        $hook = add_options_page('Floatbox Plus',
            (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . @plugins_url('floatbox-plus/icon.png') . '" width="10" height="10" alt="Floatbox Plus - Icon" /> ' : '') . 'Floatbox Plus',
            9,
            'floatbox-plus/'.basename(__FILE__),
            array(&$this,
                'OptionsMenu'
            )
        );
        if (function_exists('add_contextual_help') === true) {
            add_contextual_help($hook,
                sprintf('<a href="http://trac.splash.de/floatboxplus">%s</a><br /><a href="http://blog.splash.de/plugin/floatbox-plus/">%s</a>',
                    __('Ticketsystem/Wiki', 'floatboxplus'),
                    __('Plugin-Homepage', 'floatboxplus')
                )
            );
        }
        //add link to downloadpage, only if floatbox isn't installed
        /*
        if (!$this->check_javascript()) {
            add_options_page('Floatbox Download',
                (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . @plugins_url('floatbox-plus/icon.png') . '" width="10" height="10" alt="Floatbox Plus - Icon" />' : '') . 'Floatbox Download',
                9,
                'floatbox-plus/floatbox-download.php',
                ''
            );
        }
        */
    }

    function install()
    {
        //add default options
        $default = array(
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
                        'video_preview_playimage' => true,
                        'video_width' => '500',
                        'video_separator' => '- ',
                        'video_showinfeed' => true,
                        'floatbox_350' => true,
                        'video_debug' => false
                    );

        if(!is_array($this->options)) {
                $this->options = array();
        }

        foreach($default as $k => $v) {
                if(empty($this->options[$k])) {
                        $this->options[$k] = $v;
                }
        }

        // set options
        update_option('floatbox_plus', serialize($this->options));

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
        if(empty($this->options['youtube_fullscreen']))
		$this->options['youtube_fullscreen'] = false;
        if(empty($this->options['video_preview_playimage']))
		$this->options['video_preview_playimage'] = true;

        // floatbox: general options
        if(empty($this->options['fb_options']))
                $this->options['fb_options'] = false;
        if(empty($this->options['fb_theme']))
                $this->options['fb_theme'] = 'auto';
        if(empty($this->options['fb_preloadAll']))
                $this->options['fb_preloadAll'] = true;
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
        if(empty($this->options['floatbox_350']))
                $this->options['floatbox_350'] = true;

        if(empty($this->options['video_debug']))
                $this->options['video_debug'] = true;

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

        // images
        $pattern['image'] = "/<a(.*?)href=('|\")([A-Za-z0-9\?=,%\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)>/i";

        if (!$this->options['floatbox_350']) {
            $replacement = '<a$1href=$2$3$4$5 class="floatbox" rel="floatbox.%LIGHTID%"$6>';
            $content = preg_replace($pattern['image'], $replacement, $content);
            $pattern['title'] = "/<a([^\>]*)><img(.*?)title=\"([^\"]*)\"([^\>]*)><\/a>/ui";
            $replacement = '<a$1 rev="caption:`$3`"><img$2title="$3"$4></a>';
        } else {
            $replacement = '<a$1href=$2$3$4$5 class="floatbox" rev="group:%LIGHTID%"$6>';
            $content = preg_replace($pattern['image'], $replacement, $content);
            $pattern['title'] = "/<a(.*?)rev=\"([^\"]*)\"([^\>]*)><img(.*?)title=\"([^\"]*)\"([^\>]*)><\/a>/ui";
            $replacement = '<a$1rev="$2 caption:`$5`"$3><img$4title="$5"$6></a>';
        }
        $content = preg_replace($pattern['title'], $replacement, $content);

        // videos
        if($this->options['show_video']) {
            $pattern['video'][1] = "/\[(youtube|youtubehq|vimeo|bliptv|video) ([[:graph:]]+) (nolink)\]/";
            $pattern['video'][2] = "/\[(youtube|youtubehq|vimeo|bliptv|video) ([[:graph:]]+) ([[:print:]]+)\]/";
            $pattern['video'][3] = "/\[(youtube|youtubehq|vimeo|bliptv|video) ([[:graph:]]+)\]/";
            $content = preg_replace_callback($pattern['video'][1], array(&$this, 'video_callback'), $content);
            $content = preg_replace_callback($pattern['video'][2], array(&$this, 'video_callback'), $content);
            $content = preg_replace_callback($pattern['video'][3], array(&$this, 'video_callback'), $content);
        }

        $content = str_replace("%LIGHTID%", $post->ID, $content);
        return $content;
    }

    // video callback logic
    function video_callback($match) {
            $output = '';
            // insert plugin link
            if (!is_feed()) {
                    switch ($match[1]) {
                            case "youtube":
                            case "youtubehq":
                                    if ($this->is_mobile() == true) {
                                            $output .= $this->video['youtube']['iphone'];
                                    } else {
                                            $output .= $this->video['default']['target'];
                                    }
                                    break;
                            case "vimeo": $output .= $this->video['default']['target']; break;
                            case "bliptv": $output .= $this->video['default']['target']; break;
                            case "google": $output .= $this->video['google']['target']; break;
                            case "sevenload": $output .= $this->video['sevenload']['target']; break;
                            case "revver": $output .= $this->video['revver']['target']; break;
                            case "metacafe": $output .= $this->video['metacafe']['target']; break;
                            case "myspace": $output .= $this->video['myspace']['target']; break;
                            case "brightcove": $output .= $this->video['brightcove']['target']; break;
                            case "aniboom": $output .= $this->video['aniboom']['target']; break;
                            case "guba": $output .= $this->video['guba']['target']; break;
                            case "gamevideo": $output .= $this->video['gamevideo']['target']; break;
                            case "vsocial": $output .= $this->video['vsocial']['target']; break;
                            case "garagetv": $output .= $this->video['garage']['target']; $match[3] = "nolink"; break;
                            case "veoh": $output .= $this->video['veoh']['target']; break;
                            case "video":
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

                            default:
                                    break;
                    }

                    if ($this->options['video_showlink'] == true) {
                            if ($match[3] != "nolink") {
                                    if ($this->options['video_smallink'])
                                            $output .= "<small>";

                                    switch ($match[1]) {
                                            case "youtube": $output .= $this->video['default']['link']; break;
                                            case "youtubehq": $output .= $this->video['default']['link']; break;
                                            case "vimeo": $output .= $this->video['default']['link']; break;
                                            case "bliptv": $output .= $this->video['default']['link']; break;
                                            case "google": $output .= $this->video['google']['link']; break;
                                            case "sevenload": $output .= $this->video['sevenload']['link']; break;
                                            case "revver": $output .= $this->video['revver']['link']; break;
                                            case "metacafe": $output .= $this->video['metacafe']['link']; break;
                                            case "myspace": $output .= $this->video['myspace']['link']; break;
                                            case "brightcove": $output .= $this->video['brightcove']['link']; break;
                                            case "aniboom": $output .= $this->video['aniboom']['link']; break;
                                            case "guba": $output .= $this->video['guba']['link']; break;
                                            case "vsocial": $output .= $this->video['vsocial']['link']; break;
                                            case "veoh": $output .= $this->video['veoh']['link']; break;
                                            case "video": $output .= $this->video['video']['link']; break;
                                            default: break;
                                    }

                                    if ($this->options['video_smallink'])
                                            $output .= "</small>";
                            }
                    }
            } elseif ($this->options['video_showinfeed'] == true) {
                    $output .= $this->video['default']['feed']."<br />\n".'[ '.__('There is a video that cannot be displayed in this feed.', 'floatboxplus').' <a href="'.get_permalink().'">'.__('Visit the blog entry to see the video.','floatboxplus').' ]</a>';
            }

            // postprocessing
            // first replace video_separator
            $output = str_replace("###SEPERATOR###", $this->options['video_separator'], $output);

            // replace video IDs and text
            if ($match[3] != "nolink") {
                    $output = str_replace("###TITLE###", $match[3], $output);
            } else {
                    $output = str_replace("###TITLE###", '', $output);
            }
            $output = str_replace("###VIDEOID###", $match[2], $output);


            // replace palceholder with videodata
            $videodata = $this->get_cached_videodata($match[1], $match[2]);
            $output = str_replace("###IMAGE###", $videodata['thumbnail'], $output); // Thumbnail
            $output = str_replace("###EMBEDURL###", $videodata['embedurl'], $output); // Embed URL
            $output = str_replace("###LINK###", $videodata['link'], $output); // Link
            $output = str_replace("###VIDEOTITLE###", $videodata['title'], $output); // Video Title
            $output = str_replace("###PROVIDER###", $videodata['provider'], $output);
            if(!empty($videodata['mediatype'])) {
                    $output = str_replace("###MEDIATYPE###", $videodata['mediatype'], $output);
            } else {
                    $output = str_replace("###MEDIATYPE###", 'flash', $output);
            }

            if(!empty($videodata['height']) && !empty($videodata['width'])) {
                $_height=floor($this->options['video_width'] / $videodata['width'] * $videodata['height']);
                $_previewheight=floor($this->options['video_preview_width'] / $videodata['width'] * $videodata['height']);
                $_previewtop=($_previewheight - 100)/2;
                $_previewleft=($this->options['video_preview_width']-100)/2;

                $output = str_replace("###WIDTH###", $this->options['video_width'], $output); // Width
                $output = str_replace("###HEIGHT###", floor($this->options['video_width'] / $videodata['width'] * $videodata['height']), $output); // Height
                $output = str_replace("###PREVIEWWIDTH###", $this->options['video_preview_width'], $output); // Preview Width
                $output = str_replace("###PREVIEWHEIGHT###", floor($this->options['video_preview_width'] / $videodata['width'] * $videodata['height']), $output); // Preview Height
                $output = str_replace("###LEFT###", $_previewleft, $output); // left
                $output = str_replace("###TOP###", $_previewtop, $output); // top

                if ($this->options['video_preview_playimage']) {
                    $output = str_replace("###PLAYBUTTON###", '<img class="fbp_videopreviewbutton" src="'.plugins_url('/floatbox-plus/img/playbutton.png').'" alt="play" style="left: '.$_previewleft.'px; top:'.$_previewtop.'px" />', $output); // play-button
                } else {
                    $output = str_replace("###PLAYBUTTON###", '<span class="fbp_videopreviewplay" style="left: '.$_previewleft.'px; top:'.($_previewtop+23).'px"> ▶ </span>', $output); // play-button
                }
            }



            // add HTML comment
            $output .= "\n<!-- generated by WordPress Plugin Floatbox Plus $this->version -->\n";

            // got errors during receiving videodata? Show nice placeholder
            if ($videodata['available'] == false) {
                            $output = sprintf('<img src="'. @plugins_url('floatbox-plus/img/novideo.png') .'" width="%s" height="%s" alt="'. __('Video not available', 'floatboxplus') .'" /><br />',
                                                    $this->options['video_preview_width'],
                                                    floor($this->options['video_preview_width'] / 640 * 360)
                                                    );
            }

            // show debug informations under the videos
            if($this->options['video_debug'] == true ) {
                    $debug = sprintf('<div style="background-color:#FFC0CB; border:1px solid silver; color:#110000; margin:0 0 1.5em; overflow:auto; padding: 3px;">
                                                            <strong>Provider:</strong> %s <br />
                                                            <strong>Title:</strong> %s <br />
                                                            <strong>Thumbnail URL:</strong> %s <br />
                                                            <strong>Embed URL:</strong> %s <br />
                                                            <strong>Link:</strong> %s <br />
                                                            <strong>Width:</strong> %s px<br />
                                                            <strong>Height:</strong> %s px<br />
                                                            <strong>Got data @:</strong> %s<br />
                                                    </div>',
                                                    $videodata['provider'],
                                                    $videodata['title'],
                                                    $videodata['thumbnail'],
                                                    $videodata['embedurl'],
                                                    $videodata['link'],
                                                    $videodata['width'],
                                                    $videodata['height'],
                                                    date('d.n.Y H:i:s', $videodata['timestamp'])
                                                    );

                    $output .= $debug;
            }

            return $output;
    }

    // get the video data out of the cache
    function get_cached_videodata($service, $id) {
            $videodata = get_post_meta($GLOBALS['post']->ID, '_fbp', true);

            // if no cached data available or data is older than 24 hours, refresh/get data from video provider
            if(empty($videodata[$service][$id]) || $videodata[$service][$id]['timestamp'] + (60 * 60 * 24) < time() ) {
                    $videodata[$service][$id] = $this->get_videodata($service, $id);
                    update_post_meta($GLOBALS['post']->ID, '_fbp', $videodata);
            }

            return $videodata[$service][$id];
    }

    // puts the video data into cache
    function get_videodata($service, $id) {
            switch($service) {
                    case "youtube":
                    case "youtubehq":
                            $api      = sprintf('http://gdata.youtube.com/feeds/api/videos/%s', $id);
                            $xml      = @simplexml_load_string(wp_remote_fopen($api));

                            if (is_object($xml)) {
                                    $media    = $xml->children('http://search.yahoo.com/mrss/');

                                    if($media->group->thumbnail) {
                                            $attribs  = $media->group->thumbnail[3]->attributes();

                                            $output['available']    = true;
                                            $output['provider']     = 'YouTube';
                                            $output['mediatype']	= 'flash';
                                            $output['title']	    = (string) $media->group->title;
                                            $output['embedurl']	    = sprintf('http://www.youtube.com/v/%s', $id);
                                            $output['thumbnail']    = (string) $attribs['url'];
                                            $output['width']        = (int) $attribs['width'];
                                            $output['height']       = (int) $attribs['height'];
                                            $output['link']         = sprintf('http://www.youtube.com/watch?v=%s', $id);

                                            // add autoplay
                                            $output['embedurl'] = sprintf('%s&amp;autoplay=1', $output['embedurl']);

                                            if($service == 'youtubehq')
                                                    $output['embedurl'] = sprintf('%s&amp;ap=%%2526&amp;fmt%%3D22&amp;hd=1', $output['embedurl']);
                                    } else {
                                            $output['available'] = false;
                                    }

                            } else {
                                    $output['available'] = false;
                            }
                            $output['timestamp'] = time();

                            break;
                    case "vimeo":
                            // check if $id is numeric
                            if(!is_numeric($id)) {
                                    $output['available'] = false;
                                    return $output;
                            }

                            // Get preview image from vimeo
                            $api    = sprintf('http://vimeo.com/api/v2/video/%s.xml', $id);
                            $video  = @simplexml_load_string(wp_remote_fopen($api));

                            $outout = array();
                            $output['available']    = true;
                            $output['provider']     = 'Vimeo';
                            $output['title']        = (string) $video->video->title;
                            $output['embedurl']	    = (string) sprintf('http://player.vimeo.com/video/%s', $id);
                            $output['mediatype']	= 'iframe';
                            $output['thumbnail']    = (string) $video->video->thumbnail_large;
                            $output['width']        = (int) $video->video->width;
                            $output['height']       = (int) $video->video->height;
                            $output['link']         = sprintf('http://www.vimeo.com/%s', $id);
                            $output['timestamp'] = time();

                            // add autoplay
                            $output['embedurl'] = sprintf('%s?autoplay=1', $output['embedurl']);

                            // check response
                            if(empty($output) || empty($output['width']) || empty($output['height']) || empty($output['thumbnail']) ) {
                                    $output['available'] = false;
                                    return $output;
                            }

                            break;
                    case "bliptv":
                            // require SimplePie
                            require_once(ABSPATH . WPINC . '/feed.php');
                            $api = sprintf('http://www.blip.tv/file/%s?skin=rss', $id);
                            $namespace['media'] = 'http://search.yahoo.com/mrss/';
                            $namespace['blip']  = 'http://blip.tv/dtd/blip/1.0';

                            // fetch feed
                            $rss = fetch_feed($api);

                            if(is_wp_error($rss)) {
                                    $output['available'] == false;
                                    return $output;
                            }

                            // get items
                            $item = $rss->get_item();

                            // get media items
                            $mediaGroup     = $item->get_item_tags($namespace['media'], 'group');
                            $mediaContent   = $mediaGroup[0]['child'][$namespace['media']]['content'];

                            // get blip items
                            $blipThumbnail = $item->get_item_tags($namespace['blip'], 'thumbnail_src');
                            $blipEmbedURL  = $item->get_item_tags($namespace['blip'], 'embedUrl');

                            $output['available']    = true;
                            $output['provider']     = 'Blip.TV';
                            $output['title']        = (string) $rss->get_title();
                            $output['embedurl']     = (string) $blipEmbedURL[0]['data'];
                            $output['mediatype']	= 'flash';
                            $output['thumbnail']    = (string) sprintf('http://a.images.blip.tv/%s', $blipThumbnail[0]['data']);
                            $output['height']       = (int) $mediaContent[count($mediaContent)-1]['attribs']['']['height'];
                            $output['width']        = (int) $mediaContent[count($mediaContent)-1]['attribs']['']['width'];
                            $output['link']         = (string) $item->get_link();
                            $output['timestamp'] = time();

                            // add autoplay
                            $output['embedurl'] = sprintf('%s?autoStart=true', $output['embedurl']);

                            // check response
                            if(empty($output)) {
                                    $output['available'] = false;
                                    return $output;
                            }


                            break;
                    case "video":
                            break;
                    default: break;
            }
            return $output;
    }

    function is_mobile() {
            $uas = array ( 'iPhone', 'iPod', 'iPad', 'Android');

            foreach ( $uas as $useragent ) {
                    $pattern = sprintf('/%s/', $useragent);
                    if ( (bool) preg_match($pattern, $_SERVER['HTTP_USER_AGENT'])) {
                            return true;
                    }
            }
            return false;
    }

    function add_header() {
        $path = plugins_url()."/floatbox-plus";

        $script = "\n<!-- Floatbox Plus Plugin $this->version -->\n";
        if ($this->options['fb_licenseKey'] != 0 || $this->options['fb_options'] == true || !$this->options['floatbox_350']) {
            $script .= "<script type=\"text/javascript\">\nfbPageOptions = {\n";
            // license key
            if (!empty($this->options['fb_licenseKey'])) {
                $script .= "licenseKey: '".$this->options['fb_licenseKey']."'";
                if (!$this->options['floatbox_350'] || $this->options['fb_options'] == true) {
                    $script .= ",\n";
                } else {
                    $script .= "\n";
                }
            }
            // floatbox options
            if ($this->options['fb_options'] == true) {
                // general options
                $script .= "theme: '".$this->options['fb_theme']."',\n";
                // animation options
                $script .= "doAnimations: ".$this->boolToString($this->options['fb_doAnimations']).",\n";
                $script .= "preloadAll: ".$this->boolToString($this->options['fb_preloadAll']).",\n";
                $script .= "resizeDuration: ".$this->options['fb_resizeDuration'].",\n";
                $script .= "imageFadeDuration: ".$this->options['fb_imageFadeDuration'].",\n";
                $script .= "overlayFadeDuration: ".$this->options['fb_overlayFadeDuration'].",\n";
                $script .= "splitResize: '".$this->options['fb_splitResize']."',\n";
                $script .= "startAtClick: ".$this->boolToString($this->options['fb_startAtClick']).",\n";
                $script .= "zoomImageStart: ".$this->boolToString($this->options['fb_zoomImageStart']).",\n";
                $script .= "liveImageResize: ".$this->boolToString($this->options['fb_liveImageResize']);
                if (!$this->options['floatbox_350']) {
                    $script .= ",\n";
                } else {
                    $script .= "\n";
                }
            }
            // path, won't be necessary for floatbox 3.50
            // * [FIX] urlLanguages and urlGraphics aren't needed anymore
            if (!$this->options['floatbox_350']) {
                $script .= "urlGraphics: '".$path."/floatbox/graphics/',\n";
                $script .= "urlLanguages: '".$path."/floatbox/languages/'\n";
            }
            $script .= "};\n</script>\n";
        }
        if (FBP_WPV28 == false) {
            $script .= "<script type=\"text/javascript\" src=\"$path/floatbox/floatbox.js\"></script>\n";
            $script .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$path/floatbox/floatbox.css\" media=\"screen\" />\n";
            $script .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$path/floatbox-play.css\" media=\"screen\" />\n";
        }
        $script .= "<!-- FloatBox Plus Plugin $this->version -->\n";

        echo $script;
    }

    function enqueueJS(){
        wp_enqueue_script('floatbox', plugins_url('/floatbox-plus/floatbox/floatbox.js'), null , $this->version, true);
        wp_enqueue_script('floatbox-options', plugins_url('/floatbox-plus/floatbox/options.js'), null , $this->version, true);
    }

    function enqueueStyle(){
        wp_enqueue_style('floatbox', plugins_url('/floatbox-plus/floatbox/floatbox.css'), false, $this->version, 'screen');
        wp_enqueue_style('floatbox-play', plugins_url('/floatbox-plus/floatbox-play.css'), false, $this->version, 'screen');
    }

    function OptionsMenu()
    {       
        
        $_localversion = "3.51";
        if(file_exists(dirname(__FILE__).'/floatbox/floatbox.js')) {
            $_dump = file_get_contents (dirname(__FILE__).'/floatbox/floatbox.js', NULL, NULL, 83, 32);
            preg_match('/Floatbox v([0-9.]+)/i', $_dump, $_matches);
            $_localversion = $_matches[1];
            $_dump = file_get_contents ('http://randomous.com/floatbox/download');
            preg_match('/Latest version is ([0-9.]+)/i', $_dump, $_matches);
            $_remoteversion = $_matches[1];
            if (version_compare($_remoteversion, $_localversion) > 0)
            echo '<div id="message" class="updated fade"><p><strong>' . __("You're using floatbox version: ", 'floatboxplus') . $_localversion . ', ' . __("latest version is: ", 'floatboxplus') . '<a href="http://randomous.com/floatbox/download">' . $_remoteversion . '</a> (please update)</strong></p></div>';
        }

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

            if($_POST['fb_preloadAll'] == 'true') {
                $this->options['fb_preloadAll'] = true;
            } else {
                $this->options['fb_preloadAll'] = false;
            }
            // youtube -> fullscreen
            if($_POST['youtube_fullscreen'] == 'true') {
                $this->options['youtube_fullscreen'] = true;
            } else {
                $this->options['youtube_fullscreen'] = false;
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

            // play-button
            if($_POST['video_preview_playimage'] == 'true') {
                $this->options['video_preview_playimage'] = true;
            } else {
                $this->options['video_preview_playimage'] = false;
            }

            // option 'floatbox_350'
            if (version_compare($_localversion, '3.50') >= 0) {
                $this->options['floatbox_350'] = true;
            } else {
                $this->options['floatbox_350'] = false;
            }

            // option 'fb_licenseKey'
            // if(!empty($_POST['fb_licenseKey']))
                    $this->options['fb_licenseKey'] = $_POST['fb_licenseKey'];

            // option 'video_debug'
            if($_POST['video_debug'] == 'true') {
                    $this->options['video_debug'] = true;
            } else {
                    $this->options['video_debug'] = false;
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
                echo '<div id="message" class="error"><p><strong>' . __('Floatbox Javascript isn\'t copied to the plugin directory. See installation instructions for further details. ', 'floatboxplus') . '</strong>';
                /*
                printf(
                        '<a href="options-general.php?page=%s">%s</a>',
                        dirname($plugin).'/floatbox-download.php',
                        __('Download floatbox(.js) from randomous.com', 'floatboxplus') . '<br />'
                        );
                */
                echo '</p></div>';
            }
            ?>

    <h3><?php _e('General Settings', 'floatboxplus');  ?></h3>

    <form action="options-general.php?page=<?php echo dirname($plugin).'/'.basename(__FILE__); ?>" method="post">
        <table class="form-table">
            <tbody>

                        <?php // Activate Gallery? ?>
                        <?php if( FBP_WPV27 == false ) : ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo __('Activate Floatbox for [gallery]?', 'floatboxplus')?></label>
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
                        <label><?php echo __('Activate Floatbox for Videos?', 'floatboxplus')?></label>
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
                        <label><?php echo __('Backup Floatbox Javascript during Update?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="backup_floatbox" size="1">
                            <option value="true" <?php if ($this->options['backup_floatbox'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['backup_floatbox'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php echo __('Backups the Floatbox javascript files for upgrade-reasons. After upgrading to a new floatbox-plus version, it is needless to copy the javascript files back in the plugin directory. ', 'floatboxplus'); ?>
                    </td>
                </tr>


            </tbody>
        </table>

        <?php if ($this->options['floatbox_350']): ?>

        <h3><?php _e('Floatbox LicenseKey', 'floatboxplus'); ?></h3>

        <table class="form-table">
            <tbody>

                <?php // LicenseKey ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('LicenseKey', 'floatboxplus'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $this->options['fb_licenseKey'] ?>" name="fb_licenseKey" id="fb_licenseKey" size="5" maxlength="35" />
                        <br />
                        <?php _e('LicenseKey for floatbox 3.52 and higher.', 'floatboxplus'); ?>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php endif; ?>

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
                <?php // preloadAll ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('preloadAll', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="fb_preloadAll" size="1">
                            <option value="true" <?php if ($this->options['fb_preloadAll'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['fb_preloadAll'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('If true, floatbox will aggressively preload all images that are referenced by floatboxed anchors.', 'floatboxplus'); ?>
                    </td>
                </tr>
                <?php // doAnimations ?>
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
                        <?php _e('Default animated resizing of Floatbox resizes width, height, top and left simultaneously. The settings other than \'no\' give sequenced animation where the X and Y dimensions are resized seperately. The two options \'wh\' and \'hw\' force either width or height to always go first. The better splitResize option is probably \'auto\'.  This will always do the smallest dimension first. Using a splitResize of auto prevents unaesthetic resize behaviour of initially bloating up in the larger dimension.', 'floatboxplus'); ?>
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
                        <?php _e('If true (and resizeDuration is not 0) Floatbox will expand out from the clicked anchor and shrink back to that anchor when closed. If false, Floatbox will start and end from the center of the screen.', 'floatboxplus'); ?>
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

        <p>
            <?php printf(__("If you need to change options not mentioned here, you can change the following file %s (the configurator (%s) delievered with floatbox may help to find the options you're interessted in)."), FBP_PATH.'floatbox/options.js', '<a target="_blank" href="'.FBP_URLPATH.'floatbox/configurator.html">link</a>'); ?>
            <?php printf(__("To edit options.js you can try to use the Plugin-Editor of WordPress (only works with the right permissions set): %s"), '<a target="_blank" href="'.get_bloginfo( 'wpurl' ).'/wp-admin/plugin-editor.php?file='.plugin_basename( dirname(__FILE__) ).'/floatbox/options.js">link</a>'); ?>
        </p>

        <p><?php _e('For more information about the many other options Floatbox offers (and which aren\'t integrated yet in the plugin), take a look at the homepage:', 'floatboxplus'); ?> <a href="http://randomous.com/tools/floatbox/">Link</a></p>

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

<!--
                <?php // Fullscreen ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('YouTube: Allow fullscreen?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="youtube_fullscreen" size="1">
                            <option value="true" <?php if ($this->options['youtube_fullscreen'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['youtube_fullscreen'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Allow youtube videos to be shown in fullscreen mode (works only in floatbox 3.50 and higher)', 'floatboxplus'); ?>
                    </td>
                </tr>
-->

                <?php // play-button image vs text ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Show play-button as image?', 'floatboxplus')?></label>
                    </th>
                    <td>
                        <select name="video_preview_playimage" size="1">
                            <option value="true" <?php if ($this->options['video_preview_playimage'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                            <option value="false" <?php if ($this->options['video_preview_playimage'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                        </select>

                        <br />
                        <?php _e('Show the play-button as overlayimage, otherwise the textversion will be used', 'floatboxplus'); ?>
                    </td>
                </tr>

                <?php // Video Debug ?>
                <tr valign="top">
                        <th scope="row">
                                <label><?php echo _e('Show Video Debug Infos', 'lightviewplus'); ?></label>
                        </th>
                        <td>
                                <select name="video_debug" size="1">
                                <option value="true" <?php if ($this->options['video_debug'] == true ) { ?>selected="selected"<?php } ?>><?php _e('yes', 'floatboxplus'); ?></option>
                                <option value="false" <?php if ($this->options['video_debug'] == false ) { ?>selected="selected"<?php } ?>><?php _e('no', 'floatboxplus'); ?></option>
                                </select>

                                <br />
                                <?php _e('Shows video informations, like embed url or image url of the video. Only for debug!', 'lightviewplus'); ?>
                        </td>
                </tr>

            </tbody>
        </table>
        <input type="hidden" name="fb_submit" id="fb_submit" value="1" />
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Update Options »', 'floatboxplus'); ?>" />
        </p>
    </form>

    <p><small>Video Icon from <a href="http://www.famfamfam.com">famfamfam </a>. Special thanks to Thorsten Puzich and his plugin <a href="http://www.puzich.com/wordpress-plugins/lightview">Lightview Plus</a>, whose code i adapted to be used with Floatbox instead of lightview.</small></p>

</div>
        <?php
    }

    function add_admin_header() {
        echo "<script type='text/javascript' src='".WP_PLUGIN_URL."/floatbox-plus/tinymce/floatbox-plus.js'></script>\n";
    }

    function enqueueAdmin($hook_suffix) {
        // print '<!-- enqueueAdmin: '.$hook_suffix.' -->';
        $fbp_admin_pages = array('post-new.php', 'post.php', 'page-new.php', 'page.php');
        if(in_array($hook_suffix, $fbp_admin_pages)) {
            wp_enqueue_script('wp-polls-admin', plugins_url('/floatbox-plus/tinymce/floatbox-plus.js'), null , $this->version, true);
        }
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
   if function simplexml_load_string is not compiled into php
   use simplexml.class.php
*/
if(!function_exists("simplexml_load_string")) {
	require_once('libs/simplexml.class.php');

	function simplexml_load_string($file)
	{
		$sx = new simplexml;
		return $sx->xml_load_string($file);
	}
}

//initalize class
if (class_exists('floatbox_plus'))
    $floatbox_plus = new floatbox_plus();
