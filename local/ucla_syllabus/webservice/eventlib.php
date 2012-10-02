<?php

// Handling following events
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Handle syllabus add/update
 * 
 * @param type $data 
 */
function ucla_syllabus_handler($data) {

    if($syllabus = ucla_syllabus_manager::instance($data)) {
        // Prepare criteria and payload
        list($criteria, $payload) = syllabus_ws_manager::setup($syllabus);
        // Handle event
        syllabus_ws_manager::handle($criteria, $payload);
    }

}
