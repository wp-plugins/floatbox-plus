(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('FloatBoxPlus');

	tinymce.create('tinymce.plugins.FloatBoxPlus', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');

			ed.addCommand('mceFloatBoxPlus', function() {
				floatboxplus_insert();
			});

			// Register example button
			ed.addButton('FloatBoxPlus', {
				title : 'FloatBoxPlus.desc',
				cmd : 'mceFloatBoxPlus',
				image : url + '/floatboxplus-button.png'
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('FloatBoxPlus', n.nodeName == 'IMG');
			});
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
					longname  : 'FloatBoxPlus',
					author 	  : 'Oliver Schaal',
					authorurl : 'http://blog.splash.de',
					infourl   : 'http://blog.splash.de/plugins/floatbox-plus/',
					version   : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('FloatBoxPlus', tinymce.plugins.FloatBoxPlus);
})();