YUI.add('moodle-course-dragdrop-ucla', function(Y) {
    var PREFERENCES_UCLA = {
        PUBLICPRIVATE : {
            'privatepix' : 't/private',
            'publicpix' : 't/public',
            'component' : 'core'
        },
        STRINGS : {}
    };
    var CSS = {
        ACTIVITYLI : 'li.activity',
        COMMANDSPAN : 'span.commands',
        EDITINGMOVE : 'editing_move',
        ICONCLASS : 'iconsmall',
        GROUPINGSPAN : 'span.groupinglabel',
        MODINDENTDIV : 'div.mod-indent',
        PUBLICPRIVATE_PRIVATE : 'a.editing_makeprivate',
        PUBLICPRIVATE_PUBLIC : 'a.editing_makepublic',
        SECTIONLI : 'li.section',
        SPINNERCOMMANDSPAN : 'span.commands'
    };
    
    /// Module overrides
    var DRAGRESOURCE_UCLA = function() {
        DRAGRESOURCE_UCLA.superclass.constructor.apply(this, arguments);
    };
    Y.extend(DRAGRESOURCE_UCLA, M.course.init_resource_dragdrop);
    
    var RESOURCETOOLBOX_UCLA = function() {
        RESOURCETOOLBOX_UCLA.superclass.constructor.apply(this, arguments);
    };
    Y.extend(RESOURCETOOLBOX_UCLA, M.course.init_resource_toolbox);

    var SECTIONTOOLBOX_UCLA = function() {
        SECTIONTOOLBOX_UCLA.superclass.constructor.apply(this, arguments);
    }
    Y.extend(SECTIONTOOLBOX_UCLA, M.course.init_section_toolbox);

    
    /**
     * Convert the move icon to text
     */
    DRAGRESOURCE_UCLA.prototype.get_drag_handle = function(title, classname, iconclass) {
        var h = DRAGRESOURCE_UCLA.superclass.get_drag_handle.apply(this, [title, classname, iconclass]);
        
        if(PREFERENCES_UCLA.noeditingicon) {
            h.one('img').remove();
            var dragicon = Y.Node.create('<span></span>')
                    .setStyle('cursor', 'move')
                    .setHTML(title)
                    .addClass('editing_move_totext')
                    .setAttrs({
                        'alt' : title,
                        'title' : PREFERENCES_UCLA.STRINGS.movealt
                    });
            h.appendChild(dragicon);    
        }
        
        return h;
    }
    
    /**
     * Replace text on drag & drop
     */
    DRAGRESOURCE_UCLA.prototype.setup_for_resource = function(baseselector) {
        DRAGRESOURCE_UCLA.superclass.setup_for_resource.apply(this, [baseselector]);
        // Elements to replace
        if(PREFERENCES_UCLA.noeditingicon) {
            var items = [
                'a.editing_title',
                'a.editing_moveright',
                'a.editing_update',
                'a.editing_duplicate',
                'a.editing_hide',
                'a.editing_show',
                'a.editing_makepublic',
                'a.editing_makeprivate',
                'a.editing_delete'
            ];

            Y.Node.all(baseselector).each(function(resourcesnode) {
                // Replace with text
                Y.Array.each(items, function(item) {
                    var elem = resourcesnode.one(item);
                    if(elem) {
                        var img = elem.one('img');
                        img.setAttribute('style', 'display:none');
                        var text = elem.get('title');
                        elem.set('text', text);
                        elem.insert(img);
                    }
                });


            }, this);
        }
    }
    
    /**
     * Update the show/hide text for resources
     */
    RESOURCETOOLBOX_UCLA.prototype.toggle_hide_resource_ui = function(button) {

        var value = RESOURCETOOLBOX_UCLA.superclass.toggle_hide_resource_ui.apply(this, [button]);
        
        if(PREFERENCES_UCLA.noeditingicon) {
            var i = button.one('img');

            if(value) {
                button.set('text', M.util.get_string('hide', 'moodle'));
            } else {
                button.set('text', M.util.get_string('show', 'moodle'))
            }

            button.insert(i);    
        }
        
        return value;
    }
    
    /**
     * Update the unindent text
     */
    RESOURCETOOLBOX_UCLA.prototype.add_moveleft = function(target) {
        RESOURCETOOLBOX_UCLA.superclass.add_moveleft.apply(this, [target]);
        
        if(PREFERENCES_UCLA.noeditingicon) {
            var r = Y.all('a.editing_moveleft');
            r.each(function(rnode) { 
                rnode.setHTML(M.util.get_string('moveleft', 'moodle')) ;
            });
        }
        
    }
    
    /**
     * Handle public private button toggle (for both icon & text)
     */
    RESOURCETOOLBOX_UCLA.prototype.toggle_publicprivate = function(e) {
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
            pix = PREFERENCES_UCLA.PUBLICPRIVATE.publicpix;
        } else {
            var newnode = Y.Node.create('<span></span>');
            newnode.setHTML('(' + PREFERENCES_UCLA.STRINGS.privatematerial + ')')
                    .setAttribute('class', 'groupinglabel');
            resource.one(CSS.COMMANDSPAN).insert(newnode, 'before');
            text = PREFERENCES_UCLA.STRINGS.makepublic;
            field = 'private';
            pix = PREFERENCES_UCLA.PUBLICPRIVATE.privatepix;
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
                'src' : M.util.image_url(pix, PREFERENCES_UCLA.PUBLICPRIVATE.component),
                'alt' : text,
                'title' : text
            });
        }

        // Prepare ajax data
        var data = {
            'class' : 'resource',
            'field' : field,
            'id'    : RESOURCETOOLBOX_UCLA.superclass.get_element_id.apply(this, [element])
        };
        
        var spinner = M.util.add_spinner(Y, element.one(CSS.SPINNERCOMMANDSPAN));
        
        // Send request
        RESOURCETOOLBOX_UCLA.superclass.send_request.apply(this, [data, spinner]);
    }
    
    /**
     * Hook public private click events
     */
    RESOURCETOOLBOX_UCLA.prototype._setup_for_resource = function(toolboxtarget) {
        RESOURCETOOLBOX_UCLA.superclass._setup_for_resource.apply(this, [toolboxtarget]);

        // Show/Hide public private
        RESOURCETOOLBOX_UCLA.superclass.replace_button.apply(this, [toolboxtarget, CSS.COMMANDSPAN + ' ' + CSS.PUBLICPRIVATE_PUBLIC, this.toggle_publicprivate]);
        RESOURCETOOLBOX_UCLA.superclass.replace_button.apply(this, [toolboxtarget, CSS.COMMANDSPAN + ' ' + CSS.PUBLICPRIVATE_PRIVATE, this.toggle_publicprivate]);
    }


    /** 
     * Toggle show/hide section text
     */
    SECTIONTOOLBOX_UCLA.prototype.toggle_hide_section = function(e) {
        SECTIONTOOLBOX_UCLA.superclass.toggle_hide_section.apply(this, [e]);

        if(PREFERENCES_UCLA.noeditingicon) {
            var a = e.target;
            // Preserver the image
            var i = a.one('img');

            if(a.get('text') == M.util.get_string('hidefromothers', 'format_ucla')) {
                a.set('text', M.util.get_string('showfromothers', 'format_ucla'));
            } else {
                a.set('text', M.util.get_string('hidefromothers', 'format_ucla'));
            }
            a.insert(i);    
        }
    }
    
    /// Redefine this function because we're not reaching parent class
    SECTIONTOOLBOX_UCLA.prototype.toggle_hide_resource_ui = function(button) {
        // Call our function instead
        return RESOURCETOOLBOX_UCLA.prototype.toggle_hide_resource_ui(button);
        
    }
    

    /// Our UCLA object
    M.format_ucla = M.format_ucla || {};
    
    M.format_ucla.init = function (params) {
        PREFERENCES_UCLA.STRINGS.movealt = params.movealt;
        PREFERENCES_UCLA.noeditingicon = params.noeditingicon;
        
        new DRAGRESOURCE_UCLA(M.course.init_params);
    }
        
    M.format_ucla.init_resource_toolbox = function (params) {
        PREFERENCES_UCLA.STRINGS.makeprivate = params.makeprivate;
        PREFERENCES_UCLA.STRINGS.makepublic = params.makepublic;
        PREFERENCES_UCLA.STRINGS.privatematerial = params.privatematerial;
        PREFERENCES_UCLA.noeditingicon = params.noeditingicon;
        
        return new RESOURCETOOLBOX_UCLA(M.course.init_resource_toolbox_config);
    }
    
    M.format_ucla.init_toolbox = function (params) {
        PREFERENCES_UCLA.noeditingicon = params.noeditingicon;
        
        return new SECTIONTOOLBOX_UCLA(M.course.init_section_toolbox_config);
    }

    
}, '@VERSION@', {requires:['moodle-course-toolboxes', 'moodle-core-dragdrop', 'moodle-course-dragdrop']});
