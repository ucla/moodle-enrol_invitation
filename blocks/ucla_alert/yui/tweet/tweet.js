YUI.add('moodle-block_ucla_alert-tweet', function(Y) {
    
    var ModulenameNAME = 'block_ucla_alert_tweet';
    
    var TWEET = function() {
        TWEET.superclass.constructor.apply(this, arguments);
    };
    
    Y.extend(TWEET, Y.Base, {
        initializer : function(config) { // 'config' contains the parameter values
            // Grab all the twitter classes we're going to replace
            Y.all('.box-twitter-link').each(this.tweets);
        },
        
        tweets : function(node) {
            var text = '';
            var date = '';

            var username = node.get('text').substring(1);
            var query = 'SELECT * FROM twitter.user.status WHERE screen_name = "' + username + '"';

            Y.YQL(query, function(r) {

                var results_set = r.query.results;
                
                // This will only have one result
                Y.each(results_set, function(obj) {
                    text = obj.text;
                    date = obj.created_at;
                });
                
                // If we didn't get anything, there's nothing to do
                if(text == '') {
                    return;
                }
                
                // Get rendered tweet html
                Y.io('rest.php', {
                    method: 'POST',
                    data: 'render=' + JSON.stringify({
                            'text' : text,
                            'date' : date,
                            'username' : node.get('text'),
                            'type' : 'tweet'
                        }),
                    on: {
                        success: function (id, result) {
                            var data = JSON.parse(result.responseText);
                            
                            if(data.status) {
                                var newNode = Y.Node.create(data.data);
                                node.replace(newNode);
                            } else {
                                console.log(data);
                            }
                        },
                        failure: function (id, result) {
                            console.log('failure...');
                            console.log(result.responseText);
                        }
                    }
                });

            }); // END YQL
        }
    }, {
        NAME : ModulenameNAME, //module name is something mandatory. 
                                // It should be in lower case without space 
                                // as YUI use it for name space sometimes.
        ATTRS : {
                 aparam : {}
        } // Attributes are the parameters sent when the $PAGE->requires->yui_module calls the module. 
          // Here you can declare default values or run functions on the parameter. 
          // The param names must be the same as the ones declared 
          // in the $PAGE->requires->yui_module call.
    });
    
    M.ucla_alert_tweet = M.ucla_alert_tweet || {}; // This line use existing name path if it exists, ortherwise create a new one. 
                                                 // This is to avoid to overwrite previously loaded module with same name.
    M.ucla_alert_tweet.init_tweets = function(config) { // 'config' contains the parameter values
        return new TWEET(config); // 'config' contains the parameter values
    }
  }, '@VERSION@', {
      requires:['base','yql', 'io']
});