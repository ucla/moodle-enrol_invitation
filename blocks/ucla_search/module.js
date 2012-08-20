
M.ucla_search = {};

M.ucla_search.init = function(Y) {

    // Params
    rest_url = arguments[1];
    result_limit = arguments[2];
    
    YUI().use('autocomplete', 'autocomplete-highlighters', 'autocomplete-filters', function (Y) {

        var template = 
            '<div class="as-search-result">' + 
                '<div class="as-search-result-shortname">' +
                    '{shortname}' +
                '</div>' +
                '<div class="as-search-result-fullname">' +
                    '{fullname}' +
                '</div>' +
                '<div class="as-search-result-summary">' +
                    '{summary}' +
                '</div>' +
            '</div>';

        function myFormatter(query, results) {
            return Y.Array.map(results, function(result) {
                var out = result.raw;
                
                return Y.Lang.sub(template, {
                    shortname : out.shortname,
                    fullname : result.highlighted,
                    summary: out.summary
                });
            });
        }
        
        // Pick up checkboxes
        var collabCheck = Y.one('#as-collab-check');
        var courseCheck = Y.one('#as-course-check');

        // Set javascript check condition
        Y.one('body').addClass('yui3-skin-sam-autocomplete');
        
        Y.one('#advanced-search').plug(Y.Plugin.AutoComplete, {
            resultFormatter:    myFormatter,
            resultHighlighter:  'phraseMatch',
            minQueryLength:     3,
            maxResults:         11,
            resultListLocator:  'results',
            resultTextLocator:  'text',
            alwaysShowList:     true,
            queryDelay:         200,
            requestTemplate:    function(query) {
                // Form query
                var collab = collabCheck.get('checked') ? '&collab=1' : '&collab=0';
                var course = courseCheck.get('checked') ? '&course=1' : '&course=0';
                return '?q=' + query + collab + course + '&limit=' + result_limit;
            },
            source:             rest_url,
            on: {
                select: function(e) {
                    // Redirect to site
                    window.location = e.result.raw.url;
                }
            }
        });

    });
 
};