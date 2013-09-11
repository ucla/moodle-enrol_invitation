YUI.add('moodle-block_ucla_search-search', function(Y) {

    var RESULTLIMIT = 10;
    
    var UCLASEARCH = function() {
        UCLASEARCH.superclass.constructor.apply(this, arguments);
    }

    Y.extend(UCLASEARCH, Y.Base, {
        resturl: M.cfg.wwwroot + '/blocks/ucla_search/rest.php',
        
        initializer : function(config) {
            
            var inputid = '#ucla-search';
            var showlist = false;
            var searchname = this.get('name');

            var collabcheck = Y.one('.ucla-search.' + searchname + ' input[name="collab"]');
            var coursecheck = Y.one('.ucla-search.' + searchname + ' input[name="course"]');
            
            if (searchname === 'block-search') {
                Y.one('.block-search #ucla-search').setAttribute('id', 'ucla-search-block');
                
                Y.one('.ucla-search.' + searchname + ' .input-group-btn').remove();
                Y.one('.ucla-search.' + searchname + ' .input-group').setStyle('display', 'block');

                inputid = '#ucla-search-block';
                showlist = true;
            }
            
            var template = '<div class="search-result">' + 
                                '<div class="shortname">' +
                                    '{shortname}' +
                                '</div>' +
                                '<div class="fullname">' +
                                    '{fullname}' +
                                '</div>' +
                                '<div class="summary">' +
                                    '{summary}' +
                                '</div>' +
                            '</div>';
                    
            var formatter =  function(query, results) {
                return Y.Array.map(results, function(result) {
                    var out = result.raw;

                    return Y.Lang.sub(template, {
                        shortname : out.shortname,
                        fullname : result.highlighted,
                        summary: out.summary
                    });
                });
            };
            
            var params = function() {
                var collab = '&collab=1';
                var course = '&course=1';
                var limit = '&limit=' + RESULTLIMIT
                
                if (searchname === 'block-search') {
                    collab = collabcheck.get('checked') ? '&collab=1' : '&collab=0';
                    course = coursecheck.get('checked') ? '&course=1' : '&course=0';
                } else if (searchname === 'collab-search') {
                    collab = '&collab=1';
                    course = '&course=0';
                } else if (searchname === 'course-search') {
                    collab = '&collab=0';
                    course = '&course=1';
                }

                return (collab + course + limit);
            };

            console.log(inputid);
            
            Y.one(inputid).plug(Y.Plugin.AutoComplete, {
                resultFormatter:    formatter,
                alwaysShowList:     showlist,
                maxResults:         RESULTLIMIT + 1,
                resultHighlighter:  'phraseMatch',
                minQueryLength:     3,
                scrollIntoView:     true,
                resultListLocator:  'results',
                resultTextLocator:  'text',
                queryDelay:         200,
                
                requestTemplate:    function(query) {
                    return '?q=' + query + params();
                },
                        
                source:             this.resturl,
                
                on: {
                    select: function(e) {
                        window.location = e.result.raw.url;
                    }
                }
            });
        }
        
    }, {
        NAME : 'ucla-search',
        ATTRS : {

            name : {
                'value' : 'block-search'
            }
        }
    });
    
    M.ucla_search = M.ucla_search || {};
    
    M.ucla_search.init = function (params) {
        return new UCLASEARCH(params);
    }
    
},
'@VERSION@', {
    requires : ['autocomplete', 'autocomplete-highlighters', 'autocomplete-filters']
}
);