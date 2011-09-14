/**
 *  YUI and jQuery, together forever (TM)
 *  General rearrange and NestedSortable API should be here.
 **/

M.block_ucla_rearrange = M.block_ucla_rearrange || {};

// Fallback defaults
M.block_ucla_rearrange.sortableitem = M.block_ucla_rearrange.sortableitem 
    || 'ns-list-item';

M.block_ucla_rearrange.helperclass = M.block_ucla_rearrange.helperclass 
    || 'ns-helper';

M.block_ucla_rearrange.serializedjq = M.block_ucla_rearrange.serializedjq
    || '#serialized';

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
                    + 'Need to set up serializedjq!');
            }

            $(M.block_ucla_rearrange.targetjq).NestedSortable(
                {
                    accept: M.block_ucla_rearrange.sortableitem,
                    opacity: 0.6,
                    autoScroll: true,
                    helperclass: M.block_ucla_rearrange.helperclass,
                    nestingPxSpace: '60',
                    currentNestingClass: 'current-nesting',
                    onChange: M.block_ucla_rearrange.assign_serialized
                }
            );
        }
    );
};

/**
 *  Deactivates the nested sortable functionality for the provided DOM node.
 **/
M.block_ucla_rearrange.destroy_nested_sortable = function() {
    jQuery(
        function($) {
            $(M.block_ucla_rearrange.targetjq).NestedSortableDestroy();
        }
    );
};

/**
 *  Takes the current setup for targetted nested sortables and makes the 
 *  serialized hash.
 **/
M.block_ucla_rearrange.serialize_list = function() {
    return $.iNestedSortable.serialize(M.block_ucla_rearrange.targetjq);
};

/**
 *  Assigns a serialized hash (or what is hopefully a serialized object)
 *  to the serialized field.
 **/
M.block_ucla_rearrange.assign_serialized = function(serialized) {
    $(M.block_ucla_rearrange.serializedjq).val(serialized.hash);
}

