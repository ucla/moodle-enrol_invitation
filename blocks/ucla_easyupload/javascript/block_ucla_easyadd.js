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

    var sectionId = $('#id_section').val();

    $("#reorder-container").slideUp("slow",
        function() {
            // Destroy previous functionality
            M.block_ucla_rearrange.destroy_nested_sortable();

            // Refill with existing sections
            var sectionInsides = '';
            if (sectionId != null) {
                sectionInsides = M.block_ucla_rearrange.sections[sectionId];

                sectionInsides = sectionInsides 
                    + M.block_ucla_rearrange.empty_item;
            } else {
                alert('faulty section spec');
            }

            // Replace all the HTML content for the section
            var targetjqo = $(M.block_ucla_rearrange.targetjq);
            targetjqo.html(sectionInsides);

            M.block_ucla_easyupload.update_new_element_name();

            M.block_ucla_rearrange.create_nested_sortable();

            M.block_ucla_rearrange.serialize_target(targetjqo.get(0).id); 

            $(this).slideDown("slow");
        }
    );
};

/**
 *  Hook for initialization of functionality.
 **/
M.block_ucla_easyupload.initiate_sortable_content = function() {
    var hookfn = M.block_ucla_easyupload.change_active_sortable;

    // This is a special case for subheadings...
    M.block_ucla_easyupload.displayname_field = 
        '#id_' + $('#id_default_displayname_field').val();

    // Assign the event hook
    $('#id_section').change(hookfn);
    $(M.block_ucla_easyupload.displayname_field).change(
        M.block_ucla_easyupload.update_new_element_name
    );

    // Run the event
    hookfn();
};

/**
 *  Update the element name. TODO see if there is any better way
 **/
M.block_ucla_easyupload.update_new_element_name = function() {
    var value = $(M.block_ucla_easyupload.displayname_field).val();
    var type = $("#id_type").val();

    $("#ele-new").html(
          "<b>" + value + "</b> "
        + "<span class='ele-new-paren'>"
        + "(Your new " + type + ")"
        + "</span>" 
    );
};

