/**
 *  Skipping the YUI Blocks and going to jQuery.
 *  TODO make all function calls within M.block_ucla_control_panel 
 *      object space.
 **/
M.block_ucla_control_panel = M.block_ucla_control_panel || {};

/**
 *  Makes the provided element a NestedSortable UI element.
 **/
function makeSortable(listid) {
    jQuery(
        function($) {
            $('#' + listid).NestedSortable(
                {
                    accept: M.block_ucla_control_panel.sortableitem,
                    opacity: 0.6,
                    autoScroll: true,
                    helperclass: 'helper',
                    nestingPxSpace: '60',
                    currentNestingClass: 'current-nesting',
                    onChange: function(serialized) {
                        $("#serialized").val(serialized[0].hash);
                    }
                }
            );
        }
    );
}

function getSectionHtmlWrapper(insides) {
    return '<ul id="' + M.block_ucla_control_panel.listid + '" class="' 
        + M.block_ucla_control_panel.sortableclass 
        + '">' + insides + '</ul>';
}

/**
 *  Changes the current datavalues in the NestedSortable object.
 **/
function changeActiveSortable() {
    var listid = M.block_ucla_control_panel.listid;
    var section_html = M.block_ucla_control_panel.sections;
    var sectionId = $('#id_section').val();

    $("#reorder-container").slideUp("slow",
        function() {
            // Destroy previous functionality
            $("#" + listid).NestedSortableDestroy();

            // Refill with existing sections

            var sectionInsides = '';
            if (sectionId != null) {
                sectionInsides = section_html[sectionId];
            }

            $(this).html(getSectionHtmlWrapper(sectionInsides));

            // Jump to multiple element format
            if ($("#id_name").val() == null) {
                $('#ele-new').remove();
                //generateMultipleDraggables();
            } else {
                UpdateNewElementName();
            }

            makeSortable(listid);
            $("#serialized").val($.iNestedSortable.serialize(listid).hash);

            $(this).slideDown("slow");
        }
    );
}

function initiateSortableContent() {
    $('#id_section').change(function() {
        changeActiveSortable();
    });

    changeActiveSortable();
}

function UpdateNewElementName() {
    var value = $("#id_name").val();
    var type = $("#type").val();

    $("#ele-new").html("<b>" + value 
        + "</b> <span class='ele-new-paren'>(Your new " + type 
        + ")</span>" );
}

