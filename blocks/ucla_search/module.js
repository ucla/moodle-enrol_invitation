/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */




M.ucla_search = {};

M.ucla_search.init = function(Y) {

    // Params
    rest_url = arguments[1];
    course_url = arguments[2];
    
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
        
        function myformatter(query, results) {
            return Y.Array.map(results, function(result) {
                var foos = result.raw;
                
                return Y.Lang.sub(template, {
                    shortname : foos.shortname,
                    fullname : result.highlighted,
                    summary: foos.summary
                });
            });
        }

        Y.one('body').addClass('yui3-skin-sam');
        
        Y.one('#advanced-search').plug(Y.Plugin.AutoComplete, {
            resultFormatter:    myformatter,
            resultHighlighter:  'phraseMatch',
//            resultFilters:      'phraseMatch',
            minQueryLength:     3,
            maxResults:         11,
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
        
        Y.all('.as-search-result').on('hover', function(e) {
            console.log(this.get('class'));
        })
        // Default
//        Y.one('#advanced-search').plug(Y.Plugin.AutoComplete, {
//            resultHighlighter:  'phraseMatch',
//            resultFilters:      'phraseMatch',
//            minQueryLength:     3,
//            maxResults:         10,
//            resultListLocator:  'results',
//            resultTextLocator:  'text',
//            source: 'http://localhost/moodle-dev/moodle/blocks/ucla_search/rest.php' + '?q={query}',
//            on: {
//                select: function(e) {
//                    var url = course_url + e.result.raw.id;
//                    window.location = url;
//                }
//            }
//        });

    });
 
};