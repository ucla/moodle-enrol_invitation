<?php

// Handling following events
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Handle syllabus add/update
 * 
 * @param type $data syllabus id
 */
function ucla_syllabus_handler($data) {

    if($syllabus = ucla_syllabus_manager::instance($data)) {
        
        /* outgoing syllabus logic:
         * 
         *  If public syllabus added/updated and no private syllabus exist, 
         *      send public
         *  If public syllabus added/updated and private syllabus exist, 
         *      do not send public (do not send anything)
         *  If private syllabus added/updated and public syllabus exists/does not exist, 
         *      send private
         */
        
        
        if($syllabus instanceof ucla_private_syllabus) {
            // Private syllabus added, we'll send it
            $outgoing = $syllabus;
        } else {
            // We got a public syllabus
        
            // Get all the syllabi
            $manager = new ucla_syllabus_manager($syllabus->courseid);
            $syllabi = $manager->get_syllabi();

            // Check if private syllabus exists
            foreach($syllabi as $si) {
                if($si instanceof ucla_private_syllabus) {
                    // If it does, send nothing
                    return true;
                }
            }
            
            // Public syllabus added, and private syllabus does not exist
            $outgoing = $syllabus;
        }
        
        // Prepare criteria and payload
        list($criteria, $payload) = syllabus_ws_manager::setup_transfer($outgoing);
        // Handle event
        syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
    }

}

/**
 * Event handler for course alert
 * 
 * @param type $data course object
 */
function ucla_course_alert($data) {
    
    if(!is_collab_site($data->id)) {
        // Prepare criteria & payload
        list($criteria, $payload) = syllabus_ws_manager::setup_alert($data);

        // Handle event
        syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_ALERT, $criteria, $payload);        
    }
}