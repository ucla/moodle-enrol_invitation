/**
 *  Skipping the YUI Blocks and going to jQuery.
 *  ML and Underscores CLASH!
 **/
// Make sure that the rearrange block exists (or not)
M.block_ucla_rearrange = M.block_ucla_rearrange || {};
M.block_ucla_easyupload = M.block_ucla_easyupload || {};

/**
 *  Changes the current datavalues in the NestedSortable object.
 **/
M.block_ucla_easyupload.change_active_sortable = function() {
    var sectionId = $(this).val();

    $("#reorder-container").slideUp("slow",
        function() {
            // Destroy previous functionality
            M.block_ucla_rearrange.destroy_nested_sortable();

            // Refill with existing sections
            var sectionInsides = '';
            if (sectionId != null) {
                sectionInsides = M.block_ucla_rearrange.sections[sectionId];
            }

            // Replace all the HTML content for the section
            $(targetjq).html(sectionInsides);

            M.block_ucla_easyupload.update_new_element_name();

            M.block_ucla_rearrange.create_nested_sortable();

            // Run an intial set of the serialized value.
            M.block_ucla_rearrange.assign_serialized(
                M.block_ucla_rearrange.serialize_list()
            );

            $(this).slideDown("slow");
        }
    );
};

/**
 *  Hook for initialization of functionality.
 **/
M.block_ucla_easyupload.initiate_sortable_content() {
    var hookfn = M.block_ucla_easyupload.change_active_sortable;

    // Assign the event hook
    $('#id_section').change(hookfn);

    // Run the event
    hookfn();
}

/**
 *  Update the element name. TODO see if there is any better way
 **/
M.block_ucla_easyupload.update_new_element_name = function() {
    var value = $("#id_name").val();
    var type = $("#type").val();

    $("#ele-new").html(
          "<b>" + value + "</b>"
        + "<span class='ele-new-paren'>"
        + "(Your new " + type + ")"
        + "</span>" 
    );
}

