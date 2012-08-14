/**
 * @author NetSpot Pty Ltd
 */

(function() {
	var each = tinymce.each;

	tinymce.PluginManager.requireLangPack('wimba');

	tinymce.create('tinymce.plugins.WimbaPlugin', {
		init : function(ed, url) {
			var t = this;
			
			t.editor = ed;
			t.url = url;

			// Register commands
			ed.addCommand('mceWimba', function() {
				ed.windowManager.open({
					file : url + '/wimba.php',
					width : 320 + parseInt(ed.getLang('wimba.delta_width', 0)),
					height : 135 + parseInt(ed.getLang('wimba.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('wimba', {
                    title : 'Voice Authoring', 
                    image : url + '/img/icon.gif',
                    cmd : 'mceWimba'});

		},

		_parse : function(s) {
			return tinymce.util.JSON.parse('{' + s + '}');
		},

		getInfo : function() {
			return {
				longname : 'Blackboard Collaborate',
				author : 'NetSpot Pty Ltd',
				version : "1.0"
			};
		}

	});

	// Register plugin
	tinymce.PluginManager.add('wimba', tinymce.plugins.WimbaPlugin);
})();
