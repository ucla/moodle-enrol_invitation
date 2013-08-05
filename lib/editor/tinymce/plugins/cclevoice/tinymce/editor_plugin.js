/**
 * @author Jun Wan
 */

(function() {
    // Do not load language pack in moodle plugins.

    tinymce.create('tinymce.plugins.CCLEvoicePlugin', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {
            /** added */
            var formtextareaid = tinyMCE.activeEditor.id.substr(3);
            var itemidname = '';
            var formtextareatmp = formtextareaid.split("_");
            if (formtextareatmp.length == 2 && !isNaN(formtextareatmp[1])) {
                itemidname = formtextareatmp[0] + '[' + formtextareatmp[1] + '][itemid]';
            }
            else {
                itemidname = formtextareaid + '[itemid]';
            }
            var itemid = window.top.document.getElementsByName(itemidname).item(0);
            if (itemid!=null)
            {
                itemid = itemid.value;
            }
            /** end **/

            // Register commands.
            ed.addCommand('mceCCLEvoice', function() {
                ed.windowManager.open({

                    file : ed.getParam("moodle_plugin_base") + 'cclevoice/cclevoice.php?itemid='+itemid,
                    width : 540,
                    height : 380,
                    inline : 1
                }, {
                    plugin_url : url, // Plugin absolute URL
                    some_custom_arg : 'custom arg' // Custom argument
                });
            });

            // Register cclevoice button
            ed.addButton('cclevoice', {
                title : 'cclevoice.desc',
                cmd : 'mceCCLEvoice',
                image : url + '/img/cclevoice.png'
            });

            // Add a node change handler, selects the button in the UI when a image is selected
            ed.onNodeChange.add(function(ed, cm, n) {
                var p, c;
                c = cm.get('cclevoice');
                if (!c) {
                    // Button not used.
                    return;
                }
                p = ed.dom.getParent(n, 'SPAN');

                c.setActive(p && ed.dom.hasClass(p, 'cclevoice'));

                if (p && ed.dom.hasClass(p, 'cclevoice') || ed.selection.getContent()) {
                    c.setDisabled(false);
                } else {
                    c.setDisabled(true);
                }
                c.setDisabled(false);
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
                longname : 'cclevoice plugin',
                author : 'Jun Wan',
                authorurl : 'http://ccle.ucla.edu',
                infourl : 'http://docs.moodle.org/en/TinyMCE',
                version : "1.0"
            };
        }
    });

    // Register plugin.
    tinymce.PluginManager.add('cclevoice', tinymce.plugins.CCLEvoicePlugin);
})();
