(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('floatboxplus');

	tinymce.create('tinymce.plugins.floatboxplusPlugin', {
		init : function(ed, url) {
			var t = this;
			t.editor = ed;
			ed.addCommand('mce_floatboxplus', t._floatboxplus, t);
			ed.addButton('floatboxplus',{
				title : 'floatboxplus.desc',
				cmd : 'mce_floatboxplus',
				image : url + '/img/floatboxplus-button.png'
			});
		},

		getInfo : function() {
			return {
				longname : 'Floatbox Plus for Wordpress',
				author : 'Oliver Schaal',
				authorurl : 'http://blog.splash.de',
				infourl : 'http://blog.splash.de/plugins/floatbox-plus/',
				version : '0.1.0'
			};
		},

		// Private methods
		_floatboxplus : function() { // open a popup window
			floatboxplus_insert();
			return true;
		}
	});

	// Register plugin
	tinymce.PluginManager.add('floatboxplus', tinymce.plugins.floatboxplusPlugin);
})();