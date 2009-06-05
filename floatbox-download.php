<?php

// Do not change anything here ;)
$_floatbox['dirname'] = 'floatbox';
$_floatbox['destinationdir'] = 'floatbox-plus/floatbox/';
$_floatbox['download_url'] = 'http://randomous.com/tools/floatbox/floatbox_324.zip';

function fbp_download($feedback = '') {
	global $wp_filesystem, $_floatbox;

    if ( !empty($feedback) )
		add_filter('update_feedback', $feedback);

	// Is a filesystem accessor setup?
	if ( ! $wp_filesystem || ! is_object($wp_filesystem) )
		WP_Filesystem();

	if ( ! is_object($wp_filesystem) )
		return new WP_Error('fs_unavailable', __('Could not access filesystem.'));

	if ( $wp_filesystem->errors->get_error_code() )
		return new WP_Error('fs_error', __('Filesystem error'), $wp_filesystem->errors);

	//Get the base plugin folder
	$plugins_dir = $wp_filesystem->wp_plugins_dir();
	if ( empty($plugins_dir) )
		return new WP_Error('fs_no_plugins_dir', __('Unable to locate WordPress Plugin directory.'));

	//And the same for the Content directory.
	$content_dir = $wp_filesystem->wp_content_dir();
	if( empty($content_dir) )
		return new WP_Error('fs_no_content_dir', __('Unable to locate WordPress Content directory (wp - content).'));

	$plugins_dir = trailingslashit( $plugins_dir );
	$content_dir = trailingslashit( $content_dir );

	// Download the package
    $download_url = $_floatbox['download_url'];

    apply_filters('update_feedback', sprintf(__('Downloading update from %s'), $download_url));
	$download_file = download_url($download_url);

	if ( is_wp_error($download_file) )
		return new WP_Error('download_failed', __('Download failed.'), $download_file->get_error_message());

	$working_dir = $content_dir . 'upgrade/'. $_floatbox['dirname'];

	// Clean up working directory
	if ( $wp_filesystem->is_dir($working_dir) )
		$wp_filesystem->delete($working_dir, true);

	apply_filters('update_feedback', __('Unpacking the update'));
	// Unzip package to working directory
	$result = unzip_file($download_file, $working_dir);

    // Once extracted, delete the package
	unlink($download_file);

	if ( is_wp_error($result) ) {
		$wp_filesystem->delete($working_dir, true);
		return $result;
	}

    $plugin = $_floatbox['destinationdir'];
	// Remove the existing plugin.
	// apply_filters('update_feedback', __('Removing the old version of the floatbox'));
	$this_plugin_dir = trailingslashit( $plugins_dir . $plugin );

	// If plugin is in its own directory, recursively delete the directory.
	if ( strpos($plugin, '/') && $this_plugin_dir != $plugins_dir ) //base check on if plugin includes directory seperator AND that its not the root plugin folder
		$deleted = $wp_filesystem->delete($this_plugin_dir, true);
	else
		$deleted = $wp_filesystem->delete($plugins_dir . $plugin);

    /* aktuell uninteressant, nur bei updates sollte es vll. wieder aktiviert werden ?
	if ( ! $deleted ) {
		$wp_filesystem->delete($working_dir, true);
		return new WP_Error('delete_failed', __('Could not remove the old plugin'));
	}
    */

	apply_filters('update_feedback', __('Installing the latest version'));
	// Copy new version of plugin into place.
	$result = copy_dir($working_dir, dirname($this_plugin_dir));

	if ( is_wp_error($result) ) {
		$wp_filesystem->delete($working_dir, true);
		return $result;
	}

    //Get a list of the directories in the working directory before we delete it, We need to know the new folder for the plugin
	$filelist = array_keys( $wp_filesystem->dirlist($working_dir) );

	// Remove working directory
	$wp_filesystem->delete($working_dir, true);

	if( empty($filelist) )
		return false; //We couldnt find any files in the working dir, therefor no plugin installed? Failsafe backup.

	$folder = $filelist[0];
	$plugin = get_plugins('/' . $folder); //Ensure to pass with leading slash
	$pluginfiles = array_keys($plugin); //Assume the requested plugin is the first in the list

	return  $folder . '/' . $pluginfiles[0];
}

?>
<div class="wrap">
    <h2>FloatBox Plus</h2>
    <?php
if ($floatbox_plus->check_javascript()) {
    echo __("floatbox(.js) already installed...", "floatboxplus");
} else {
    echo __("Let's try to download floatbox...<br />", "floatboxplus");

    if ($_POST['go'] == 'true') {

        $result = fbp_download('show_message');
        if ( is_wp_error($result) ) {
            show_message($result);
            show_message( __('Download failed, please follow the manual installation instructions on my <a href="http://blog.splash.de/2009/01/29/floatbox-plus/">website</a>.') );
        } else {
            show_message(__('Download successfull.'));
            // show_message($result);
        }
    } else {
        // show form...
        ?>
        <h3>Download disabled</h3>
        <p>Due to changes in the licensing the download is actually disabled. Sorry</p>
        <p>I'll try to reenable this possibility (as soon as possible).</p>
        <p></p>
        <p>For more information/download of floatbox check out the website of <a href="http://randomous.com/floatbox/home">floatbox</a>.</p>
<!--
        <form action="options-general.php?page=<?php echo dirname(plugin_basename(__FILE__)).'/floatbox-download.php'; ?>" method="post">
            <h3>License Terms</h3>
            <p class="submit">
            <a href="http://randomous.com/tools/floatbox/" target="_blank">Floatbox</a> is protected by copyright and is publicly released as free software under the <a href="http://creativecommons.org/licenses/by/3.0/" target="_blank">Creative Commons Attribution 3.0 License</a>.<br />
You are free to use and modify floatbox but you must retain the attribution and license information in the comment block at the top of the floatbox javascript files.
You are encouraged to attribute and link back to this source (http://randomous.com/tools/floatbox/) from sites that use floatbox.
If you are using floatbox commercially, either by getting paid to develop sites or by using it on a promotional or revenue generating site,
please consider making a fair <a href="http://randomous.com/tools/floatbox/donate.html" target="_blank">donation</a> towards the considerable development effort and ongoing support time that keeps floatbox alive.
            </p>
            <p class="submit">
            <label><?php echo __('I agree to the <a href="http://creativecommons.org/licenses/by/3.0/" target="_blank">license</a> of <a href="http://randomous.com/tools/floatbox/" target="_blank">floatbox</a> by Byron McGregor and want to download/install floatbox now?', 'floatboxplus'); ?></label>
            <select name="go">
                <option value="false"><?php _e('no', 'floatboxplus'); ?></option>
                <option value="true"><?php _e('yes', 'floatboxplus'); ?></option>
            </select>
            </p>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e('submit »', 'floatboxplus'); ?>" />
            </p>

        </form>
-->
        <?php
    }
}
    ?>
</div>
