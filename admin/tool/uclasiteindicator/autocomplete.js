

M.collab_autocomplete = {};

M.collab_autocomplete.init = function(Y) {

    // Params
    rest_url = arguments[1];
    course_url = arguments[2];
    
    YUI().use('autocomplete', 'autocomplete-highlighters', 'autocomplete-filters', function (Y) {

        Y.one('body').addClass('yui3-skin-sam');

        Y.one('#ac_input').plug(Y.Plugin.AutoComplete, {
            resultHighlighter:  'phraseMatch',
            resultFilters:      'phraseMatch',
            minQueryLength:     3,
            maxResults:         20,
            resultListLocator:  'results',
            resultTextLocator:  'text',
            source: rest_url + '?q={query}',
            on: {
                select: function(e) {
                    var url = course_url + e.result.raw.id;
                    window.location = url;
                }
            }
        });

    });
 
};