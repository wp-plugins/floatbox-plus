<?php

class FloatBoxMCE {

	var $pluginname = "FloatBoxPlus";

	function __construct()  {
		// Modify the version when FloatBoxMCE plugins are changed.
		add_filter('tiny_mce_version', array (&$this, 'changeFloatBoxMCEVersion') );

		// init process for button control
		add_action('init', array (&$this, 'addButtons') );
	}

	function addButtons() {

		// Don't bother doing this stuff if the current user lacks permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {

		// add the button for wp2.5 in a new way
			add_filter("mce_external_plugins", array (&$this, "addFloatBoxMCEPlugin" ), 5);
			add_filter('mce_buttons', array (&$this, 'registerButton' ), 5);
		}
	}

	// used to insert button in wordpress 2.5x editor
	function registerButton($buttons) {

		array_push($buttons, "separator", $this->pluginname );

		return $buttons;
	}

	// Load the FloatBoxMCE plugin : editor_plugin.js (wp2.5)
	function addFloatBoxMCEPlugin($plugin_array) {

		$plugin_array[$this->pluginname] =  FBP_URLPATH.'tinymce/editor_plugin.js';

		return $plugin_array;
	}

	function changeFloatBoxMCEVersion($version) {
		return ++$version;
	}

}

?>