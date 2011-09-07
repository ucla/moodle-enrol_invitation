/**
 *  Skipping the YUI Blocks and going to jQuery.
 **/
function makeSortable(list_id) {
    jQuery(
        function($) {
            $('#' + list_id).NestedSortable(
                {
                    accept: 'page-item',
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

var default_section_html =
    '<ul id="thelist" class="page-list"></ul>';

function change_active_sortable(section_id) {
    $("#reorder-container").slideUp("slow",
        function() {
            // Destroy previous functionality
            $("#thelist").NestedSortableDestroy();

            // Refill with existing sections
            if (section_id == null) {
                $("#reorder-container").html(default_section_html);
            } else {
                $("#reorder-container").html(section_html[section_id]);
            }

            // Jump to multiple element format
            if ($("#id_itemname").val() == null) {
                $('#ele-new').remove();
                generateMultipleDraggables();
            } else {
                update_new_ele_name();
            }

            makeSortable("thelist");

            $("#serialized").val($.iNestedSortable.serialize("thelist").hash);

            $("#reorder-container").slideDown("slow");
        }
    );
}

function initiate_sortable_content() {
    $("#thelist").NestedSortableDestroy();
    var selected_id = $("#section-chooser").val();

    $("#reorder-container").html(section_html[selected_id]);

    update_new_ele_name();
    makeSortable("thelist");
}

function update_new_ele_name() {
    var value;
    value = $("#id_itemname").val();

    var type = $("#type").val();
    $("#ele-new").html("<b>" + value + "</b> <span class='ele-new-paren'>(Your new " + type + ")</span>" );
}

