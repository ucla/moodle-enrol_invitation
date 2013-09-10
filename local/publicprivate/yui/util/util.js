YUI.add('moodle-local_publicprivate-util', function(Y) {

    var CSS = {
        ACTIVITYLI : 'li.activity',
        COMMANDSPAN : 'span.commands',
        GROUPINGSPAN : 'span.groupinglabel',
        MODINDENTDIV : 'div.mod-indent',
        PAGECONTENT : 'div#page-content',
        PUBLICPRIVATE_PRIVATE : 'a.editing_makeprivate',
        PUBLICPRIVATE_PUBLIC : 'a.editing_makepublic',
        SPINNERCOMMANDSPAN : 'span.commands',
        MODULEIDPREFIX : 'module-',
    };
    
    var PUBLICPRIVATE = function() {
        PUBLICPRIVATE.superclass.constructor.apply(this, arguments);
    }

    Y.extend(PUBLICPRIVATE, Y.Base, {
        initializer : function(config) {
            // Set event listeners
            Y.delegate('click', this.toggle, CSS.PAGECONTENT, CSS.COMMANDSPAN + ' a.publicprivate', this);

            // Let moodle know we exist
            M.course.coursebase.register_module(this);
        },
        toggle : function(e) {
            e.preventDefault();
    
            var mod = e.target.ancestor(CSS.ACTIVITYLI);
            
            var field = '';
            var public = mod.one('.activityinstance .groupinglabel');
            
            if (public) {
                public.remove();
                field = 'public';

                // Swap icon
                mod.one('.publicprivate').setAttrs({
                    'title' : M.util.get_string('publicprivatemakeprivate', 'local_publicprivate'),
                }).one('img').setAttrs({
                    'src' : M.util.image_url(this.get('publicpix'), this.get('component')),
                    'alt' : M.util.get_string('publicprivatemakeprivate', 'local_publicprivate')
                })
                
            } else {
                // Add label
                mod.one('.activityinstance').insert(
                    Y.Node.create('<span class="groupinglabel">(' + M.util.get_string('publicprivategroupingname', 'local_publicprivate') + ')</span>')
                );
                
                // Swap icon
                mod.one('.publicprivate').setAttrs({
                    'title' : M.util.get_string('publicprivatemakepublic', 'local_publicprivate'),
                }).one('img').setAttrs({
                    'src' : M.util.image_url(this.get('privatepix'), this.get('component')),
                    'alt' : M.util.get_string('publicprivatemakepublic', 'local_publicprivate')
                });
                
                field = 'private'; 
            }
            
            // Prepare ajax data
            var data = {
                'class' : 'resource',
                'field' : field,
                'id'    : mod.get('id').replace(CSS.MODULEIDPREFIX, '')
            };
            
            // Get spinner
            var spinner = M.util.add_spinner(Y, mod.one(CSS.SPINNERCOMMANDSPAN));
            
            // Send request
            this.send_request(data, spinner);
        },
        send_request : function(data, statusspinner) {
            // Default data structure
            if (!data) {
                data = {};
            }

            data.sesskey = M.cfg.sesskey;
            data.courseId = this.get('courseid');

            var uri = M.cfg.wwwroot + '/local/publicprivate/rest.php';

            // Define the configuration to send with the request
            var responsetext = [];
            var config = {
                method: 'POST',
                data: data,
                on: {
                    success: function(tid, response) {
                        try {
                            responsetext = Y.JSON.parse(response.responseText);
                            if (responsetext.error) {
                                new M.core.ajaxException(responsetext);
                            }
                        } catch (e) {}
                        if (statusspinner) {
                            window.setTimeout(function(e) {
                                statusspinner.hide();
                            }, 400);
                        }
                    },
                    failure : function(tid, response) {
                        if (statusspinner) {
                            statusspinner.hide();
                        }
                        new M.core.ajaxException(response);
                    }
                },
                context: this,
                sync: true
            }

            if (statusspinner) {
                statusspinner.show();
            }

            // Send the request
            Y.io(uri, config);
            return responsetext;
        },
        setup_for_resource : function(baseselector) {
            // Insert the PP icon node after a file is dragged + dropped
            var href = M.cfg.wwwroot + 
                    '/local/publicprivate/mod.php?' + 
                    M.cfg.sesskey + '&public=' + 
                    baseselector.replace(CSS.MODULEIDPREFIX, '');
            
            // Generate pp icon node
            // NOTE: because we delegate events, we don't need to attach a handler
            Y.one(baseselector + ' ' + CSS.COMMANDSPAN).insert(
                Y.Node.create(
                    '<a class="editing_makepublic publicprivate" ' +
                        'href="' + href + '"' +
                        'title="' + M.util.get_string('publicprivatemakepublic', 'local_publicprivate') + '">' +
                        '<img class="iconsmall" ' +
                            'src="' + M.util.image_url(this.get('privatepix'), this.get('component')) + '"' +
                            'alt="' + M.util.get_string('publicprivatemakepublic', 'local_publicprivate') + '"/>' +
                    '</a>'
                )
            );

        }
    }, {
        NAME : 'course-publicprivate-toolbox',
        ATTRS : {
            courseid : {
                'value' : 0
            },
            component : {
                'value' : 'core'
            },
            privatepix : {
                'value' : 't/locked'
            },
            publicpix : {
                'value' : 't/lock'
            }
        }
    });
    
    M.local_publicprivate = M.local_publicprivate || {};
    
    M.local_publicprivate.init = function (params) {
        // Load module
        return new PUBLICPRIVATE(params);
    }
    
},
'@VERSION@', {
    requires : ['node', 'io', 'moodle-course-coursebase']
}
);
