/**
 *  YUI and jQuery, together forever (TM)
 *  General rearrange and NestedSortable API should be here.
 **/

M.block_ucla_rearrange = M.block_ucla_rearrange || {};

/** These are not necessary, these should be handled in the PHP caller
    of this file. But as a precaution, they are here. **/

// This is the item to look for as the accepts: field in iNS
M.block_ucla_rearrange.nestedsortableitem 
    = M.block_ucla_rearrange.nestedsortableitem || 'ns-list-item';

// This is the CSS class to use as a the object that shows up
M.block_ucla_rearrange.nestedhelperclass 
    = M.block_ucla_rearrange.nestedhelperclass || 'ns-helper';

// This is the serialized field to look for
M.block_ucla_rearrange.serializedjq = M.block_ucla_rearrange.serializedjq
    || '#serialized';

// This is the item to apply Sortable() to
M.block_ucla_rearrange.sectionlist 
    = M.block_ucla_rearrange.sectionlist || 's-list';

// This is the item to look for in sortable accepts: field
M.block_ucla_rearrange.sortableitem 
    = M.block_ucla_rearrange.sortableitem || 's-list-item';

// This also is most likely not necessary
M.block_ucla_rearrange.sortablehelperclass
    = M.block_ucla_rearrange.sortablehelperclass || 's-helper';

/** 
 *  Other fields that need to be declared outside of this script:
 *
 *  targetjq - the jQuery query that we will run NestedSortable upon.
 *  sections - the JSON HTML representation of sections.
 **/

/**
 *  Activeates the nested sortable functionality to a given
 *  DOM node, provided its ID field.
 *  Also adds a callback function to a DOM node with ID 'serialized'
 **/
M.block_ucla_rearrange.create_nested_sortable = function() {
    jQuery(
        function($) {
            if ($(M.block_ucla_rearrange.serializedjq) == false) {
                alert('Improperly set page! Needs a id="serialized" Node!');
                return false;
            }

            if (M.block_ucla_rearrange.targetjq == undefined) {
                alert('Improperly set up NestedSortable parameters! ' 
                    + 'Need to set up targetjq!');
                return false;
            }

       
            // This has a special nesting case
            var buildtarget = $(M.block_ucla_rearrange.targetjq);

            if (buildtarget.length > 1) {
                buildtarget.each(function() {
                    if (this.id == undefined || this.id.length == 0) {
                        return;
                    }

                    var thisjq = '#' + this.id;

                    $(thisjq).NestedSortable(M.block_ucla_rearrange.ns_config);
                    
                    M.block_ucla_rearrange.serialize_target(this.id)
                });
            } else {
                buildtarget.NestedSortable(M.block_ucla_rearrange.ns_config);
                // For single ones... I need to do something...
                // This means that there is a #id 
                var targetid = M.block_ucla_rearrange.targetjq.substring(1);

                M.block_ucla_rearrange.serialize_target(targetid)
            }
        }
    );
};

/**
 *  Deactivates the nested sortable functionality for the provided DOM node.
 **/
M.block_ucla_rearrange.destroy_nested_sortable = function() {
    jQuery(
        function($) {

            var buildtarget = $(M.block_ucla_rearrange.targetjq);

            if (buildtarget.length > 1) {
                buildtarget.each(function() {
                    $('#' + this.id).NestedSortableDestroy();
                });
            } else {
                buildtarget.NestedSortableDestroy();
            }
        }
    );
};

/**
 *  Takes the current setup for targetted nested sortables and makes the 
 *  serialized hash.
 **/
M.block_ucla_rearrange.serialize_target = function(target) {
    var set = [];

    set[0] = $.iNestedSortable.serialize(target);
    set[0].id = target;

    M.block_ucla_rearrange.assign_serialized(set);
};

/**
 *  Assigns a serialized hash (or what is hopefully a serialized object)
 *  to the serialized field.
 *  TODO: Fix design, this shouldn't know where to put the data
 **/
M.block_ucla_rearrange.assign_serialized = function(data) {
    $.each(data, function() {
        var jqtarget;

        // THis is a hack
        if (!this.id.match(/[0-9]/)) {
            jqtarget = M.block_ucla_rearrange.serializedjq;
        } else {
            var idsplit = this.id.split('-');
            var sectnum = idsplit[idsplit.length - 1];

            jqtarget = '#serialized-' + sectnum;
        }

        $(jqtarget).val(this.hash);
    });
};

/**     START NON-NESTED SORTABLES      **/

/**
 *  Makes a sortable only section.
 *  This part is not necessarily designed for any other page than the 
 *  rearrange page.
 **/
M.block_ucla_rearrange.create_sortable = function() {
    var tarjet = M.block_ucla_rearrange.sectionlist;
    var sectiontarjq = '#' + tarjet;
    $(sectiontarjq).Sortable(
        {
            accept: M.block_ucla_rearrange.sortableitem,
            helperclass: M.block_ucla_rearrange.sortablehelperclass,
            opacity: 0.5,
            fit: true,
            onChange: M.block_ucla_rearrange.assign_serialized
        }
    );

    M.block_ucla_rearrange.serialize_target(tarjet);
};

/**
 *  Assign event handler for the Expand/Collapse clickers.
 **/
M.block_ucla_rearrange.create_expandables = function() {
    $('#' + M.block_ucla_rearrange.sectionlist + ' li div').click(function() {
        M.block_ucla_rearrange.toggle_section($(this));
        M.block_ucla_rearrange.verify_allsection_button();
    });
};

/**
 *  Toggles a section to be hidden or not.
 **/
M.block_ucla_rearrange.toggle_section = function(sectionjqo) {
    sectionjqo.children('ul').slideToggle();

    var ectext = sectionjqo.children('.sectiontitle').children('div');
    M.block_ucla_rearrange.toggle_onoff_text(ectext,
        M.block_ucla_rearrange.expandtext, 
        M.block_ucla_rearrange.collapsetext);

};

/**
 *  Toggles between 2 possible texts.
 **/
M.block_ucla_rearrange.toggle_onoff_text = function(
        jqobj, selectOn, selectOff, fn) {
    if (fn == undefined) {
        fn = 'text';
    }

    var jqt = eval('jqobj.' + fn + '()');
    
    if (jqt == selectOn) {
        eval('jqobj.' + fn + '(selectOff)');
    } else {
        eval('jqobj.' + fn + '(selectOn)');
    }
};

/**
 *  Creates and assigns event handlers for expanding and closing all sections.
 **/
M.block_ucla_rearrange.create_expand_all = function() {
    $(M.block_ucla_rearrange.expandalljq).each(function() {
        $(this).click(function() {
            // Collapse or expand all
            M.block_ucla_rearrange.event_expand_all();

            // Change the text
            M.block_ucla_rearrange.toggle_all_text();
        });
    });
};

/**
 *  Toggle the text of the "Expand/Collapse all"
 **/
M.block_ucla_rearrange.toggle_all_text = function() {
    $(M.block_ucla_rearrange.expandalljq).each(function() {
        M.block_ucla_rearrange.toggle_onoff_text(
            $(this),
            M.block_ucla_rearrange.expandalltext,
            M.block_ucla_rearrange.collapsealltext,
            'val'
        );
    });
};

/**
 *  Checks and changes the expand/collapse all button if the button 
 *  no longer does naything.
 **/
M.block_ucla_rearrange.verify_allsection_button = function() {
    var allSame = null;

    $('.sectiontitle div').each(function() {
        var thisText = $(this).text();
        if (allSame == null) {
            allSame = thisText;
        } else if (allSame != thisText) {
            allSame = false;
        }
    });

    if (allSame != null && allSame != false
            && !M.block_ucla_rearrange.compare_section_toggle(allSame)) { 
        M.block_ucla_rearrange.toggle_all_text();
    }
};

/**
 *  Parses and goes through each expandable section and unexpands it.
 **/
M.block_ucla_rearrange.event_expand_all = function() {
    var sectionsToChange = [];

    $('.sectiontitle div').each(function() {
        var thisText = $(this).text();

        if (M.block_ucla_rearrange.compare_section_toggle(thisText)) {
            // TODO fix this because this kind of SUCKS
            sectionsToChange.push($(this).parent().parent());
        }
    });

    for (var jqr in sectionsToChange) {
        M.block_ucla_rearrange.toggle_section($(sectionsToChange[jqr]));
    }
};

/**
 *  Checks if a certain section needs to be expanded/collapsed
 *      when Expand/Collapse all is clicked.
 **/
M.block_ucla_rearrange.compare_section_toggle = function(text) {
    var current = $(M.block_ucla_rearrange.expandalljq).val();

    if (current == M.block_ucla_rearrange.expandalltext
            && text == M.block_ucla_rearrange.expandtext) {
        return true;
    } else if (current == M.block_ucla_rearrange.collapsealltext
            && text == M.block_ucla_rearrange.collapsetext) {
        return true;
    }

    return false;
};

/**
 *  Custom spec-ed out function for initalziation of rearrange.
 **/
M.block_ucla_rearrange.initialize_rearrange_tool = function() {
    $(M.block_ucla_rearrange.containerjq).html(M.block_ucla_rearrange.sections);

    M.block_ucla_rearrange.create_sortable();

    M.block_ucla_rearrange.create_nested_sortable();
    M.block_ucla_rearrange.create_expandables();
    M.block_ucla_rearrange.create_expand_all();
};

/**
 *  Configuration used when building a nested-sortable.
 **/
M.block_ucla_rearrange.build_ns_config = function() {
    M.block_ucla_rearrange.ns_config = {
        accept: M.block_ucla_rearrange.nestedsortableitem,
        helperclass: M.block_ucla_rearrange.nestedhelperclass,
        opacity: 0.6,
        autoScroll: true,
        nestingPxSpace: '32',
        currentNestingClass: 'current-nesting',
        noNestingClass: M.block_ucla_rearrange.nonnesting,
        onChange: M.block_ucla_rearrange.assign_serialized,
        fit: true
    };
};

/**
 * Warning message for unsaved work
 **/
M.block_ucla_rearrange.not_saved = true;
M.block_ucla_rearrange.assignwindow = function()
{
    window.onbeforeunload = function() {
        if (M.block_ucla_rearrange.not_saved)
            return "Your work has not been saved.";
    }
};

