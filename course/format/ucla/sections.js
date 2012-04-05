M.format_ucla = M.format_ucla || {};
M.format_ucla.strings = M.format_ucla.strings || {} 

/**
 *  Overwriting init_buttons of core moodle's AJAX functionality.
 **/
section_class.prototype.old_init_buttons =
    section_class.prototype.init_buttons;

section_class.prototype.init_buttons = function() {
    // this is a terrible and manic hack for another terrible and manic hack
    // This will overwrite mk_button for the scope of this function,
    // then once it's done, it will set it back.
    main_class.prototype.old_mk_button = main_class.prototype.mk_button;
    main_class.prototype.mk_button = function(tag, imgSrc, text, 
                                              attributes, imgAttributes) {

        var tagnode = this.old_mk_button(tag, imgSrc, text, attributes,
                                         imgAttributes);

        // Remove the img that is created
        tagnode.removeChild(tagnode.firstChild);

        text_only = document.createTextNode(tagnode.title);

        // THis is to prevent the other clicks from breaking things
        text_only.src = '';
        text_only.alt = '';
        text_only.title = '';
        
        tagnode.appendChild(text_only);

        return tagnode;
    }

    this.old_init_buttons();
   
    // Hack to hide the highlight button...
    var commandContainer = YAHOO.util.Dom.getElementsByClassName('right',
        null, this.getEl())[0];

    // We don't need the highlight button, but for section 0 there are some
    // problems.
    if (commandContainer.childNodes.length > 1) {
        commandContainer.removeChild(commandContainer.childNodes[1]);
    }

    main_class.prototype.mk_button = main_class.prototype.old_mk_button;
    main_class.prototype.old_mk_button = undefined;
}

section_class.prototype.old_toggle_hide = section_class.prototype.toggle_hide;

// This is a bad coding pattern
section_class.prototype.toggle_hide = function(e, target, superficial) {
    this.old_toggle_hide(e, target, superficial);
    this.viewButton.childNodes[0].data = this.viewButton.title;

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
