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
    
    var PREFERENCES_UCLA = {
        STRINGS : {}
    };
    
    var PUBLICPRIVATE = function() {
        PUBLICPRIVATE.superclass.constructor.apply(this, arguments);
    }

    Y.extend(PUBLICPRIVATE, Y.Base, {
        initializer : function(config) {
            // Set event listeners
            Y.delegate('click', this.toggle_publicprivate, CSS.PAGECONTENT, CSS.COMMANDSPAN + ' ' + CSS.PUBLICPRIVATE_PUBLIC, this);
            Y.delegate('click', this.toggle_publicprivate, CSS.PAGECONTENT, CSS.COMMANDSPAN + ' ' + CSS.PUBLICPRIVATE_PRIVATE, this);

            // Let moodle know we exist
            M.course.coursebase.register_module(this);
        },
        toggle_publicprivate : function(e) {
            e.preventDefault();

            var button = e.target;
            var resource = e.target.ancestor(CSS.MODINDENTDIV);
            var element   = e.target.ancestor(CSS.ACTIVITYLI);

            var label = resource.one(CSS.GROUPINGSPAN);
            var field = '';
            var text = '';
            var pix = '';

            if(label) {
                label.remove();
                field = 'public';
                text = PREFERENCES_UCLA.STRINGS.makeprivate;
                pix = this.get('publicpix');
            } else {
                var newnode = Y.Node.create('<span></span>');
                newnode.setHTML('(' + PREFERENCES_UCLA.STRINGS.privatematerial + ')')
                        .setAttribute('class', 'groupinglabel');
                resource.one(CSS.COMMANDSPAN).insert(newnode, 'before');
                text = PREFERENCES_UCLA.STRINGS.makepublic;
                field = 'private';
                pix = this.get('privatepix');
            }

            if(PREFERENCES_UCLA.noeditingicon) {
                button.set('text', text);
            } else {
                // Sometimes we get the event from the link, and sometimes 
                // from the image.  We want to make sure we manipulate the image
                if(button.get('src') == null) {
                    button = button.one('img');
                }

                // swap button image
                button.setAttrs({
                    'src' : M.util.image_url(pix, this.get('component')),
                    'alt' : text,
                    'title' : text
                });
            }

            // Prepare ajax data
            var data = {
                'class' : 'resource',
                'field' : field,
                'id'    : element.get('id').replace(CSS.MODULEIDPREFIX, '')
            };

            var spinner = M.util.add_spinner(Y, element.one(CSS.SPINNERCOMMANDSPAN));

//            M.course.coursebase.invoke_function('send_request', data, spinner);
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

            var uri = M.cfg.wwwroot + this.get('ajaxurl');

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
            //@todo: add public private icons to dropped files
        }
    }, {
        NAME : 'course-publicprivate-toolbox',
        ATTRS : {
            courseid : {
                'value' : 0
            },
            ajaxurl : {
                'value' : ''
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
        
        PREFERENCES_UCLA.STRINGS.makeprivate = params.makeprivate;
        PREFERENCES_UCLA.STRINGS.makepublic = params.makepublic;
        PREFERENCES_UCLA.STRINGS.privatematerial = params.privatematerial;
        PREFERENCES_UCLA.noeditingicon = params.noeditingicon;
        
        return new PUBLICPRIVATE(params);
    }
    
},
'@VERSION@', {
    requires : ['node', 'io', 'moodle-course-coursebase']
}
);
