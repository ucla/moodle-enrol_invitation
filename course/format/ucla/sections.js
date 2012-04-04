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

    main_class.prototype.mk_button = main_class.prototype.old_mk_button;
    main_class.prototype.old_mk_button = undefined;
}

section_class.prototype.old_toggle_hide = section_class.prototype.toggle_hide;

// This is a bad coding pattern
section_class.prototype.toggle_hide = function(e, target, superficial) {
    this.old_toggle_hide(e, target, superficial);
    this.viewButton.childNodes[0].data = this.viewButton.title;
}
