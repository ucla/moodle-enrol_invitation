// Our namespace
M.format_ucla = M.format_ucla || {};
// Strings in our namespace
M.format_ucla.strings = M.format_ucla.strings || {};

/**
 *  Overriding factory method to produce buttons, making them spit out only
 *  text instead of icons.
 **/
M.format_ucla.mk_button_override = function(tag, imgSrc, text, 
                                           attributes, imgAttributes) {

    var tagnode = this.old_mk_button(tag, imgSrc, text, attributes,
                                     imgAttributes);

    var ismovebutton = (attributes != null && attributes[0][1] == 'cursor:move');
    /** try to keeep things consistent, no icons at all
    if (ismovebutton) {
        return tagnode;
    }
    //*/

    var altstr = M.format_ucla.strings['movealt'];

    iconnode = tagnode.childNodes[0];

    if (ismovebutton) {
        iconnode.alt = altstr;
    }

    // Don't do anything if we want icons
    if (M.format_ucla.no_editing_icons != undefined 
            && !M.format_ucla.no_editing_icons) {
        if (ismovebutton) {
            tagnode.title = altstr;
        }
        return tagnode;            
    }


    iconnode = tagnode.childNodes[0];

    iconnode.className = iconnode.className || '';
    iconnode.className += ' no-editing-icons';

    // Remove the img that is created
    //tagnode.removeChild(tagnode.firstChild);

    text_only = document.createTextNode(tagnode.title);
    text_div = document.createElement('span');
    text_div.appendChild(text_only);

    // This is to prevent the other clicks from breaking things
    text_div.src = '';
    text_div.alt = '';
    text_div.title = '';

    if (ismovebutton) {
        tagnode.title = altstr;
    }
    
    tagnode.insertBefore(text_div, iconnode);

    return tagnode;
}

/**
 *  This will override a button-factory's method during the call
 *  of the provided 
 *  @param overriddenfn
 **/
M.format_ucla.mk_button_caller_wrapper = function(overriddenfn) {
    // this is a terrible and manic hack for another terrible and manic hack
    // This will overwrite mk_button for the scope of this function,
    // then once it's done, it will set it back.
    main_class.prototype.old_mk_button = main_class.prototype.mk_button;
    main_class.prototype.mk_button = M.format_ucla.mk_button_override;
    overriddenfn.apply(this);
    main_class.prototype.mk_button = main_class.prototype.old_mk_button;
    main_class.prototype.old_mk_button = undefined;
}

/**
 *  Takes an object and creates a reference to it in the object's
 *  own namespace "moodle", replaces the original function in 
 *  object's namespace with newfn
 *  @param  obj The object to manipulate
 *  @param  fn  The function to replace (string)
 *  @param  newfn   The new function to put into the obj
 **/
M.format_ucla.override_moodle_fn = function(obj, fn, newfn) {
    obj.moodle = obj.moodle || {};
    obj.moodle[fn] = obj.prototype[fn];

    obj.prototype[fn] = newfn;
}

/**
 *  section_class override
 **/
M.format_ucla.section_class = M.format_ucla.section_class || {};

/**
 *  Alters the section buttons and replaces them with text.
 **/
M.format_ucla.section_class.init_button_post = function() {
    // Hack to hide the highlight button...
    var commandContainer = YAHOO.util.Dom.getElementsByClassName('right',
        null, this.getEl())[0];

    // We don't need the highlight button, but for section 0 there are some
    // problems.
    if (commandContainer.childNodes.length > 1) {
        commandContainer.removeChild(commandContainer.childNodes[1]);
    }
}

/**
 *  Overrides the global section_class function to use text instead of
 *  icons.
 **/
M.format_ucla.override_moodle_fn(section_class, 'init_buttons',
    function() {
        M.format_ucla.mk_button_caller_wrapper.apply(
            this, [section_class.moodle.init_buttons]
        );

        M.format_ucla.section_class.init_button_post.apply(this);
    });

/**
 *  Alters behavior of the hide link to properly handle text-toggling.
 **/
M.format_ucla.section_class.toggle_hide = function(e, target, 
                                                   superficial) {
    section_class.moodle.toggle_hide.apply(this, [e, target, superficial]);
    this.viewButton.childNodes[0].innerHTML = this.viewButton.title;

    var header = YAHOO.util.Dom.getElementsByClassName('header', 
        'h2', this.getEl())[0];

    var hiddenspan = YAHOO.util.Dom.getFirstChildBy(header,
        function(node) {
            if (node.nodeName == 'SPAN') {
                return true;
            }

            return false;
        });

    if (!this.hidden) {
        if (hiddenspan != null) {
            header.removeChild(hiddenspan);
        }
    } else {
        if (hiddenspan == null) {
            var hiddenspan = document.createElement('span');
            YAHOO.util.Dom.addClass(hiddenspan, 'hidden');
            hiddenspan.innerHTML = M.format_ucla.strings['hidden'];
            header.appendChild(hiddenspan);
        }
    }
}

/**
 *  Alter behavior of global object.
 **/
M.format_ucla.override_moodle_fn(section_class, 'toggle_hide',
    M.format_ucla.section_class.toggle_hide);

/**
 *  Resource_class override
 **/
M.format_ucla.resource_class = M.format_ucla.resource_class || {};

M.format_ucla.resource_class.init_buttons = function() {
    M.format_ucla.mk_button_caller_wrapper.apply(
            this, [resource_class.moodle.init_buttons]
        );
};

M.format_ucla.override_moodle_fn(resource_class, 'init_buttons',
    M.format_ucla.resource_class.init_buttons);

M.format_ucla.resource_class.indent_right = function() {
    M.format_ucla.mk_button_caller_wrapper.apply(
            this, [resource_class.moodle.indent_right]
        );
};

M.format_ucla.override_moodle_fn(resource_class, 'indent_right',
    M.format_ucla.resource_class['indent_right']);

M.format_ucla.resource_class.toggle_hide = function(t, e, s, f) {
    // Call the parent
    resource_class.moodle.toggle_hide.apply(this, arguments);

    // Overwrite with alt
    this.viewButton.childNodes[0].innerHTML= this.viewButton.title;
};

M.format_ucla.override_moodle_fn(resource_class, 'toggle_hide',
    M.format_ucla.resource_class.toggle_hide);

M.format_ucla.resource_class.toggle_groupmode = function() {
    resource_class.moodle.toggle_groupmode.apply(this, arguments);

    this.groupButton.childNodes[0].innerHTML = this.groupButton.title;
}

M.format_ucla.override_moodle_fn(resource_class, 'toggle_groupmode',
    M.format_ucla.resource_class.toggle_groupmode);

M.format_ucla.resource_class.toggle_publicprivate = function() {
    resource_class.moodle.toggle_publicprivate.apply(this, arguments);

    this.publicprivateButton.childNodes[0].innerHTML = this.publicprivateButton.title;
}

M.format_ucla.override_moodle_fn(resource_class, 'toggle_publicprivate',
    M.format_ucla.resource_class.toggle_publicprivate);
