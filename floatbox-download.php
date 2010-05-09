<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// do not change anything here ;)
$_floatbox['dirname'] = 'floatbox';
$_floatbox['destinationdir'] = 'floatbox-plus/floatbox/';
$_floatbox['download_url'] = 'http://randomous.com/floatbox/floatbox_401.zip';

function fbp_download($feedback = '') {
    global $wp_filesystem, $_floatbox;

    if ( !empty($feedback) )
	add_filter('update_feedback', $feedback);

    // Is a filesystem accessor setup?
    if ( ! $wp_filesystem || ! is_object($wp_filesystem) )
        WP_Filesystem();

    if ( ! is_object($wp_filesystem) )
        return new WP_Error('fs_unavailable', __('Could not access filesystem.', 'floatboxplus'));

    if ( $wp_filesystem->errors->get_error_code() )
        return new WP_Error('fs_error', __('Filesystem error', 'floatboxplus'), $wp_filesystem->errors);

    //Get the base plugin folder
    $plugins_dir = $wp_filesystem->wp_plugins_dir();
    if ( empty($plugins_dir) )
        return new WP_Error('fs_no_plugins_dir', __('Unable to locate WordPress Plugin directory.', 'floatboxplus'));

    //And the same for the Content directory.
    $content_dir = $wp_filesystem->wp_content_dir();
    if( empty($content_dir) )
        return new WP_Error('fs_no_content_dir', __('Unable to locate WordPress Content directory (wp - content).', 'floatboxplus'));

    $plugins_dir = trailingslashit( $plugins_dir );
    $content_dir = trailingslashit( $content_dir );

    // Download the package
    $download_url = $_floatbox['download_url'];

    apply_filters('update_feedback', sprintf(__('Downloading floatbox from %s', 'floatboxplus'), $download_url));
    $download_file = download_url($download_url);

    if ( is_wp_error($download_file) )
        return new WP_Error('download_failed', __('Download failed', 'floatboxplus'), $download_file->get_error_message());

    $working_dir = $content_dir . 'upgrade/'. $_floatbox['dirname'];

    // Clean up working directory
    if ( $wp_filesystem->is_dir($working_dir) )
        $wp_filesystem->delete($working_dir, true);

    apply_filters('update_feedback', __('Unpacking the update', 'floatboxplus'));
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

    /* TODO: aktuell uninteressant, nur bei updates sollte es vll. wieder aktiviert werden ?
	if ( ! $deleted ) {
		$wp_filesystem->delete($working_dir, true);
		return new WP_Error('delete_failed', __('Could not remove the old plugin'));
	}
    */

    apply_filters('update_feedback', __('Installing the latest version', 'floatboxplus'));
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
    echo __('floatbox(.js) already installed...', 'floatboxplus')."<br>\n";
} else {
    if ($_POST['go'] == 'true') {

        $result = fbp_download('show_message');
        if ( is_wp_error($result) ) {
            show_message($result);
            show_message(sprintf(__('Download failed, please follow the manual installation instructions: %s.', 'floatboxplus'),'<a href="http://blog.splash.de/2009/01/29/floatbox-plus/">'.__('Link','floatboxplus').'</a>') );
        } else {
            show_message(__('Download successfull.'));
            // show_message($result);
        }
    } else {
        // show form...
        ?>
        <form action="options-general.php?page=<?php echo dirname(plugin_basename(__FILE__)).'/floatbox-download.php'; ?>" method="post">
            <h3>License Terms of Floatbox</h3>
            <p class="submit">
            Floatbox: Copyright © 2008-2009, Byron McGregor<br />
<br />
<a href="http://randomous.com/floatbox/">Floatbox</a> is protected by copyright and is released under the <a href="http://creativecommons.org/licenses/by-nc-nd/3.0/">Creative Commons Attribution-Noncommercial-No Derivative Works 3.0 Unported License</a>.<br />
<br />
All use of floatbox on production commercial web sites requires a license purchase and registration on a per domain basis. A commercial site is any web site that facilitates the selling of a product or service, that generates revenue, that advertises, markets, promotes or provides information for a commercial or professional organization or undertaking, or that is used as an Intranet service for a commercial organization. Use of floatbox by government agencies, higher educational institutions, and political, religious, labor or fraternal organizations requires commercial registration. If a fee has been or will be paid for a site's content creation or authoring, a commercial license is required for that site.<br />
<br />
Floatbox is free for use on non-commercial sites such as personal hobby sites and sites associated with public service non-profit organizations. It is also free for all development and test instances of web sites. Candidates for free use may request a license key through the randomous.com web site. Eligibility for a free license key will be determined at the sole discretion of the floatbox copyright holder.<br />
<br />
Unrestricted permission is granted, and a license key is not needed, for development, test and evaluation use.<br />
<br />
To purchase/register or to request a free non-commercial license please follow the instructions on this <a href="http://randomous.com/floatbox/register">page</a>.
            </p>
            <p class="submit">
            <label>I agree to the <a href="http://creativecommons.org/licenses/by/3.0/" target="_blank">license</a> of <a href="http://randomous.com/tools/floatbox/" target="_blank">floatbox</a> by Byron McGregor and want to download/install floatbox now?</label>
            <select name="go">
                <option value="false"><?php _e('no', 'floatboxplus'); ?></option>
                <option value="true"><?php _e('yes', 'floatboxplus'); ?></option>
            </select>
            </p>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e('submit »', 'floatboxplus'); ?>" />
            </p>

        </form>
        <?php
    }
}
    ?>
</div>
