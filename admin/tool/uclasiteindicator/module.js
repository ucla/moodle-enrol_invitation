

M.mod_mymod = {};
 
M.mod_mymod.init = function(Y) {

    YUI().use('autocomplete', 'autocomplete-highlighters', 'autocomplete-filters', function (Y) {

        // Add the yui3-skin-sam class to the body so the default
        // AutoComplete widget skin will be applied.
        Y.one('body').addClass('yui3-skin-sam');

        // The following examples demonstrate some of the different
        // result sources AutoComplete supports. You only need to
        // pick one, you don't need them all. Assume the '#ac-input'
        // element id used in this example refers to an <input>
        // element on the page.

        console.log(M.foo);
        
        Y.one('#ac_input').plug(Y.Plugin.AutoComplete, {
            resultHighlighter:  'phraseMatch',
            resultFilters:      'phraseMatch',
            minQueryLength:     3,
            resultListLocator:  'results',
            resultTextLocator:  'text',
            source: 'http://localhost/moodle-dev/moodle/admin/tool/uclasiteindicator/rest.php?q={query}',
            on: {
                select: function(e) {
//                    console.log(e.result.raw.id);

                    var url = 'http://localhost/moodle-dev/moodle/course/view.php?id=' + e.result.raw.id;
                    window.location = url;
                }
            }
        });

    });
 
};