
M.ucla_search = {};

M.ucla_search.init = function(Y) {

    // Params
    rest_url = arguments[1];
    result_limit = arguments[2];
    
    YUI().use('autocomplete', 'autocomplete-highlighters', 'autocomplete-filters', 'io', function (Y) {

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
        
        var collabCheck = Y.one('#as-collab-check');

        Y.one('body').addClass('yui3-skin-sam');
        
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
                return '?q=' + query + collab + '&limit=' + result_limit;
            },
            source:             rest_url,
            on: {
                select: function(e) {
                    // Redirect to site
                    window.location = e.result.raw.url;
                },
                hoveredItemChange: function(e) {
//                    console.log(e.newVal);
//                    if(e.newVal) {
//                        e.newVal.one('div .as-search-result-summary').setStyle('display','block');
//                    }
//                    
//                    if(e.prevVal) {
//                        e.prevVal.one('div .as-search-result-summary').setStyle('display', 'none');
//                    }
                }
            }
        });

    });
 
};