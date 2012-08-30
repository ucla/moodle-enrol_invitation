YUI.add('moodle-course-dragdrop-ucla', function(Y) {
    var MOVEICON = {'pix':"i/move_2d",'component':'moodle'};
    
    var DRAGRESOURCE_UCLA = function() {
        DRAGRESOURCE_UCLA.superclass.constructor.apply(this, arguments);
    };
    
    Y.extend(DRAGRESOURCE_UCLA, M.course.init_resource_dragdrop, {
        get_drag_handle : function(title, classname, iconclass) {
            var dragicon = {};
            
            if(M.format_ucla.params.noeditingicon) {
                // Use text
                dragicon = Y.Node.create('<span></span>')
                .setStyle('cursor', 'move')
                .setHTML(title)
                .addClass('editing_move_totext')
                .setAttrs({
                    'alt' : title,
                    'title' : M.format_ucla.params.movealt
                });
                            
            } else {
                // Use an image
                dragicon = Y.Node.create('<img />')
                .setStyle('cursor', 'move')
                .setAttrs({
                    'src' : M.util.image_url(MOVEICON.pix, MOVEICON.component),
                    'alt' : title,
                    'title' : M.str.moodle.move
                });
            }

            if (iconclass) {
                dragicon.addClass(iconclass);
            }

            var dragelement = Y.Node.create('<span></span>')
                .addClass(classname)
                .setAttribute('title', title)
            dragelement.appendChild(dragicon);
            return dragelement;
        }
    }, {
        NAME : 'course-dragdrop-section-ucla',
        ATTRS :  {

        }
    });


    M.format_ucla = M.format_ucla || {};
    
    M.format_ucla.init = function (params) {
        M.format_ucla.params = params;
        
        new DRAGRESOURCE_UCLA(M.course.init_params);
    }
    
}, '@VERSION', {requires:['moodle-core-dragdrop', 'moodle-course-dragdrop']});

