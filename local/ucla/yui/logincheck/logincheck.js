/* 
 * YUI module to prevent form submission
 * 
 * This will attach itself to all '.mform input[type="submit"]' buttons and hijack
 * the 'click' event.  Will then poll the server to see if user is still logged in.
 * 
 * If user is logged in, sumission continues, else
 * form submission is halted and the user is given option to login 
 * 
 */

YUI.add('moodle-local_ucla-logincheck', function(Y) {
    
    var SITE = M.cfg.wwwroot;
    var USERID = null;
    var MESSAGES = {
        LOGIN:          M.util.get_string('longincheck_login', 'local_ucla'),
        IDFAIL:         M.util.get_string('logincheck_idfail', 'local_ucla'),
        NETWORKFAIL:    M.util.get_string('logincheck_networkfail', 'local_ucla'),
        SUCCESS:        M.util.get_string('logincheck_success', 'local_ucla')
    }
    var PAGE = {
        LOGIN: '/login/index.php',
        LOGINCHECK: '/local/ucla/logincheck.php'
    }
    
    // Load our namespace
    M.local_ucla = M.local_ucla || {};
    
    // Attach script
    M.local_ucla.logincheck = {
        init: function(config) {
            USERID = config.userid;

            // Create array of modules to check
            var modules = ['.path-mod-forum', '.path-mod-wiki', '.path-mod-assign', '.path-mod-assignment',
                            '.path-mod-page', '.path-mod-choice', '.path-mod-questionnaire'];

            var flag = false;

            // Check if we are in one of the modules if one is found, break.
            Y.Array.some(modules, function(mod){
                if(Y.one(mod)){
                    flag = true;
                    return true;
                }
            });

            if(flag) {

                // Grab all forms on the page..
                var forms = Y.all('.mform');

                // Attach event handlers to all forms
                forms.each(function(form) {

                    // Grab all the submit buttons
                    var buttons = form.all('input[type="submit"]')

                    // Grab hidden sesskey field
                    var formsesskey = form.one('input[name="sesskey"]');
                    
                    // Hijack the 'click' handler for buttons
                    buttons.on('click', function(e) {

                        // Caputure default event...
                        e.preventDefault();

                        // Poll the site to see if user is logged in..
                        Y.io(SITE.concat(PAGE.LOGINCHECK), {
                            method: 'GET',
                            on: {
                                success: function(id, result) {
                                    var json = Y.JSON.parse(result.responseText);

                                    // Check status
                                    if(json.status) {

                                        // If logged in, check the logged in sesskey with the form field sesskey,
                                        // and change the form field to the new sesskey if they differ. 
                                        if(json.sesskey != formsesskey.getAttribute('value')) {
                                            formsesskey.set('value', json.sesskey); 
                                        }
                                        // Now, continue click
                                        // First remove the event handler
                                        e.target.detachAll();
                                        // Then simulate click for default behavior
                                        e.target.simulate('click');
                                    } else {
                                        // User is not logged in... 

                                        if(!form.hasClass('attention')) {
                                            // Create alertbox with login button

                                            // Build alertbox node
                                            var alertbox = Y.Node.create('<div><p>' + MESSAGES.LOGIN + '</p></div>');
                                            alertbox.addClass('alert').addClass('alert-warning');

                                            // Add login button
                                            var button = Y.Node.create('<button>Login</button>');
                                            button.addClass('btn').addClass('btn-success').on('click', function(e) {
                                                e.preventDefault();
                                                // Pop up window invoke..
                                                M.local_ucla.logincheck.popupwin('user-pop-login', form.getAttribute('id'));
                                            });
                                            // Attach button 
                                            alertbox.append(button);

                                            // Display alertbox
                                            form.append(alertbox);

                                            // Draw attention to the form
                                            form.addClass('attention');                     
                                        }

                                    }
                                },
                            failure: function() {
                                    // Create network failure alert
                                    var failalert = Y.Node.create('<div>' + MESSAGES.NETWORKFAIL + '</div>');
                                    failalert.addClass('alert').addClass('alert-danger');
                                    form.append(failalert);
                                }
                            }
                        });
                    });
                });
            }
    
        },
        popupwin: function(windowname, formid) {
            // Pop up window vars.. 
            var url = SITE.concat(PAGE.LOGIN);
            var name = windowname;
            var params = 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1, width=500,height=500,left=200,top=200';
            var popup = window.open(url, name, params);

            // Poll every second to close window as soon as user logs in...
            var timer = Y.later(1000, Y, function() {

                // If user's closed window, stop timer
                if (popup.closed) {
                    timer.cancel();
                    return;
                }

                // Poll session var to see if it's active
                Y.io(SITE.concat(PAGE.LOGINCHECK), {
                method: 'GET',
                on: {
                    success: function(id, result) {
                            var json = Y.JSON.parse(result.responseText);
                            // If user is logged in.. close the popup
                            if(json.status) {

                                // Grab the form
                                var form = Y.one('#' + formid);

                                // Check that userid is the same
                                if (json.userid == USERID) {
                                    // Reset the session key or form will be rejected
                                    form.one('input[name="sesskey"]').set('value', json.sesskey);
                                    // Y.one('#' + formid + ' input[name="sesskey"]').set('value', json.sesskey);

                                    // Close the pop-up
                                    timer.cancel();
                                    popup.close();

                                    //  Delete the popup notification
                                    form.all('div.alert').remove(true);
                                    form.removeClass('attention');

                                    // Add a 'success' notification
                                    var successalert = Y.Node.create('<div>' + MESSAGES.SUCCESS + '</div>');
                                    successalert.addClass('alert').addClass('alert-success');
                                    form.append(successalert);
                                } else {
                                    // Not the same USER, so disable form
                                    form.one('.alert').removeClass('alert-warning')
                                                      .addClass('alert-danger')
                                                      .setHTML(MESSAGES.IDFAIL);

                                    // Disable submit
                                    form.all('input[type="submit"]').set('disabled', 'disabled');
                                }

                            }
                        }
                    }
                });


            }, '', true);

            // Close window after 30 secs?
            Y.later(30000, Y, function () {
                timer.cancel();
                popup.close();
            });
        }
    }
    
}, '@VERSION@', {
    requires: ['node', 'io', 'json', 'event', 'node-event-simulate']
});
