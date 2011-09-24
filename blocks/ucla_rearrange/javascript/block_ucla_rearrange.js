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
                    M.block_ucla_rearrange.serialize_target(this.id);
                });
            } else {
                buildtarget.NestedSortable(M.block_ucla_rearrange.ns_config);
                // For single ones... I need to do something...
                // This means that there is a #id 
                var targetid = M.block_ucla_rearrange.targetjq.substring(1);

                M.block_ucla_rearrange.serialize_target(targetid);
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
    return set;
};

/**
 *  Assigns a serialized hash (or what is hopefully a serialized object)
 *  to the serialized field.
 **/
M.block_ucla_rearrange.assign_serialized = function(data) {
    $.each(data, function() {
        var jqtarget;

        if (!this.id.match(/[0-9]/)) {
            jqtarget = M.block_ucla_rearrange.serializedjq;
        } else {
            var idsplit = this.id.split('-');
            var sectnum = idsplit[idsplit.length - 1];

            jqtarget = '#serialized-' + sectnum;
        }

        $(jqtarget).val(this.hash);
    });
}

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
}

/**
 *  Custom spec-ed out function for initalziation of rearrange.
 **/
M.block_ucla_rearrange.initialize_rearrange_tool = function() {
    $(M.block_ucla_rearrange.containerjq).html(M.block_ucla_rearrange.sections);

    M.block_ucla_rearrange.create_sortable();
    M.block_ucla_rearrange.create_nested_sortable();
    
    var initialserialized = [];
}

/**
 *  Configuration used when building a nested-sortable.
 **/
M.block_ucla_rearrange.ns_config = {
    accept: M.block_ucla_rearrange.nestedsortableitem,
    helperclass: M.block_ucla_rearrange.nestedhelperclass,
    opacity: 0.6,
    autoScroll: true,
    nestingPxSpace: '40',
    currentNestingClass: 'current-nesting',
    noNestingClass: M.block_ucla_rearrange.nonnesting,
    onChange: M.block_ucla_rearrange.assign_serialized,
    fit: true
};
