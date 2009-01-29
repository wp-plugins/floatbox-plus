<?php
/*
Author: Oliver Schaal
Plugin Name: Floatbox Plus
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/plugins/floatbox-plus
Version: 0.1.1
Description: Used to overlay images on the webpage and to automatically add links to images. Floatbox by <a href="http://randomous.com/tools/floatbox/">Byron McGregor</a> which is licensed under the terms of Creative Commons Attribution 3.0 License (http://creativecommons.org/licenses/by/3.0/) and therefor it isn't included (not GPL compatible). Read installation instructions on <a href="http://blog.splash.de/plugins/floatbox-plus">my website</a> or in the readme.txt. <strong>Floatbox Plus is delivered without floatbox-javascript. Please read the installation instructions on my website/readme.txt</strong>.
*/

global $wp_version;
define('WPV27', version_compare($wp_version, '2.7', '>='));

class floatbox_plus {

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
        (!is_array($this->options)) ? $this->options = unserialize($this->options) : false;

        // install default options
        register_activation_hook(__FILE__, array(&$this, 'install'));

        // uninstall features
        register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));

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


		// define object targets and links
		$this->video['youtube']['height'] = floor($this->options['video_width']*14/17);
		$this->video['youtube']['preview_height'] = floor($this->options['video_preview_width']*14/17);
		$this->video['youtube']['iphone'] = '<object width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] . '"><param name="movie" value="http://www.youtube.com/v/###VID###"></param><embed src="http://www.youtube.com/v/###VID###" type="application/x-shockwave-flash" width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] .'"></embed></object><br />';
        $this->video['youtube']['target'] = '<a href="http://www.youtube.com/v/###VID###&amp;autoplay=1" title="###THING###" class="floatbox" rel="floatbox" rev="width:' . $this->options['video_width'] . ' height:' . $this->video['youtube']['height'] . ' scrolling:no"><img src="###IMAGE###" class="videoplay" width="' . $this->options['video_preview_width'] . '" height="' . $this->video['youtube']['preview_height'] . '" alt="###THING###" /></a><br />';
		$this->video['youtube']['link']   = "<a title=\"YouTube\" href=\"http://www.youtube.com/watch?v=###VID###\">YouTube ###TXT######THING###</a>";

		$this->video['youtubehq']['height'] = floor($this->options['video_width']*9/15.2);
		$this->video['youtubehq']['preview_height'] = floor($this->options['video_preview_width']*9/15.2);
		$this->video['youtubehq']['iphone'] = '<object width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] . '"><param name="movie" value="http://www.youtube.com/v/###VID###"></param><embed src="http://www.youtube.com/v/###VID###" type="application/x-shockwave-flash" width="' . $this->options['video_width'] . '" height="' . $this->video['youtube']['height'] .'"></embed></object><br />';
		$this->video['youtubehq']['target'] = '<a href="http://www.youtube.com/v/###VID###&amp;autoplay=1&amp;ap=%2526fmt%3D22" title="###THING###" class="floatbox" rel="floatbox" rev="width:' . $this->options['video_width'] . ' height:' . $this->video['youtube']['height'] . ' scrolling:no"><img src="###IMAGE###" class="videoplay" width="' . $this->options['video_preview_width'] . '" height="' . $this->video['youtube']['preview_height'] . '" alt="###THING###" /></a><br />';
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


    function AdminMenu()
    {
        $hook = add_options_page('FloatBox Plus', (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . @plugins_url('floatbox-plus/icon.png') . '" width="10" height="10" alt="Floatbox Plus - Icon" />' : '') . 'Floatbox Plus', 8, 'floatbox_plus', array(&$this, 'OptionsMenu'));
        if (function_exists('add_contextual_help') === true) {
            add_contextual_help($hook,
                sprintf('<a href="http://trac.splash.de/floatboxplus">%s</a><br /><a href="http://blog.splash.de/plugin/floatboxplus/">%s</a>',
                    __('Ticketsystem/Wiki', 'floatboxplus'),
                    __('Plugin-Homepage', 'floatboxplus')
                )
            );
        }
    }

    function install()
    {
        //add default options
        if ($this->options == false) {
            add_option('floatbox_plus', serialize(array(
                        'load_gallery' => true,
                        'show_video' => true,
                        'backup_floatbox' => true,
                        'video_showlink' => true,
                        'video_smallink' => true,
						'video_preview_width'=> '300',
                        'video_width' => '300',
                        'video_separator' => '- ',
                        'video_showinfeed' => true
                    )));
        }

		// update options for old installs
		$this->update();

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
        $replacement = '<a$1 title="$3"><img$2title="$3"$4></a>';
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

				// is simplexml available? Get preview image from vimeo
				if(function_exists(simplexml_load_file)) {
					$clip = simplexml_load_file($api_link);
					$output = $clip->clip->thumbnail_large;
				} else {
					// $output = get_option('siteurl') . '/wp-content/plugins/floatbox-plus/img/preview_image.png';
                    return false;
				}

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
        $path = get_option('siteurl')."/wp-content/plugins/floatbox-plus";

        $script = "\n<!-- FloatBox Plus Plugin -->\n";
        $script .= "<script type=\"text/javascript\" src=\"$path/floatbox/floatbox.js\"></script>\n";
        $script .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$path/floatbox/floatbox.css\" media=\"screen\" />\n";
        $script .= "<!-- FloatBox Plus Plugin -->\n";

        echo $script;
    }

    function OptionsMenu()
    {

        if (!empty($_POST)) {

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

            if(!$this->check_javascript()) {
                echo '<div id="message" class="error"><p><strong>' . __('FloatBox Javascript and/or CSS isn\'t copied to the plugin directory. See installation instructions for further details.', 'floatboxplus') . '</strong></p></div>';
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
} // end class

//initalize class
if (class_exists('floatbox_plus'))
$floatbox_plus = new floatbox_plus();
