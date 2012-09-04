
M.ucla_search = {};
M.ucla_search_browseby = {};

function loadAdvancedSearch(inputId, restUrl, searchUrl, resultLimit, showList, collabFilter) {
   
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
        // Flag to avoid picking up 'menu' clicks
        var menuCheck = false;

        function searchParams(filter) {
            
            var collab = '';
            var course = '';
            
            if(filter == null) {
                collab = collabCheck.get('checked') ? '&collab=1' : '&collab=0';
                course = courseCheck.get('checked') ? '&course=1' : '&course=0';
            } else if (filter == true) {
                collab = '&collab=1';
                course = '&course=0';
            } else {
                collab = '&collab=0';
                course = '&course=1';
            }
            
            return (collab + course);
        }
        
        // Set javascript check condition
        Y.one('body').addClass('yui3-skin-sam-autocomplete');

        Y.one(inputId).plug(Y.Plugin.AutoComplete, {
            resultFormatter:    myFormatter,
            resultHighlighter:  'phraseMatch',
            minQueryLength:     3,
            maxResults:         resultLimit + 1,
            scrollIntoView:     true,
            resultListLocator:  'results',
            resultTextLocator:  'text',
            alwaysShowList:     showList,
            queryDelay:         200,
            requestTemplate:    function(query) {
                // Form query
                return '?q=' + query + searchParams(collabFilter) + '&limit=' + resultLimit;
            },
            source:             restUrl,
            on: {
                select: function(e) {
                    // Redirect to site
                    menuCheck = true;
                    window.location = e.result.raw.url;
                }
            }
        }).on('keydown', function(e) {
            // Do general search 
            if(e.keyCode == 13 && !menuCheck) {
                var url = searchUrl + '?search=' + this.get('value') + searchParams(collabFilter);
                window.location = url;
            }
        });

    });
}

M.ucla_search.init = function(Y) {

    // Params
    restUrl = arguments[1];     // Search result query URL
    searchUrl = arguments[2];   // General search URL
    resultLimit = arguments[3]; // Search limit
    showList = arguments[4];    // Allow search list to always be visible
    
    loadAdvancedSearch('#advanced-search', restUrl, searchUrl, resultLimit, showList, null);

};

M.ucla_search_browseby.init = function(Y) {

    // Params
    restUrl = arguments[1];     // Search result query URL
    searchUrl = arguments[2];   // General search URL
    resultLimit = arguments[3]; // Search limit
    showList = arguments[4];    // Allow search list to always be visible
    collabFilter = arguments[5];
    
    loadAdvancedSearch('#advanced-search-browseby', restUrl, searchUrl, resultLimit, showList, collabFilter)
};