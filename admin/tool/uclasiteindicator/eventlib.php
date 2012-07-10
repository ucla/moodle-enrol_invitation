<?php

require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

/**
 * Event handler for course deletion
 * 
 * @param type $course 
 */
function delete_indicator($course) {
    global $OUTPUT;
    
    if($indicator = siteindicator_site::load($course->id)) {
        $indicator->delete();
        echo $OUTPUT->notification(get_string('deleted').' - '.get_string('del_msg', 'tool_uclasiteindicator'), 'notifysuccess');
    }
}