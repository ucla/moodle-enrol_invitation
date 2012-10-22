<?php

// Handling following events
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Handle syllabus add/update
 * 
 * @param type $data syllabus id
 */
function ucla_syllabus_updated($data) {

    if($syllabus = ucla_syllabus_manager::instance($data)) {
        global $DB;
        
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
        
            $course = $DB->get_record('course', array('id' => $syllabus->courseid));
            
            // Get all the syllabi
            $manager = new ucla_syllabus_manager($course);
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
 * Handle deletion of syllabus
 * 
 * @param type $data 
 */
function ucla_syllabus_deleted($data) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $data->courseid));

    // Get all the syllabi
    $manager = new ucla_syllabus_manager($course);
    $syllabi = $manager->get_syllabi();
    
    switch(intval($data->access_type)) {
        case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
            
            /**
             * Case where syllabus is private
             * 
             * If no public syllabus exists, POST delete
             * If public syllabus exists, POST public syllabus 
             */
            
            if(empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
                list($criteria, $payload) = syllabus_ws_manager::setup_delete($data);
                syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
            } else {
                $public_syllabus = array_shift($syllabi);
                
                // Pass it on to another handler...
                ucla_syllabus_updated($public_syllabus->id);
            }
            
            break;
        case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
        case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
            
            /**
             * Case where syllabus is public
             * 
             * If no private syllabus exists, POST delete
             * Else do nothing.. 
             */
            
            if(empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
                list($criteria, $payload) = syllabus_ws_manager::setup_delete($data);
                syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
            }
            // Else do nothing
            break;
    }
    
    return true;
}

/**
 * Event handler for course alert.  This handles crosslisted courses by 
 * sending the 
 * 
 * @param type $data course object
 */
function ucla_course_alert($data) {
    
    if(!is_collab_site($data->id)) {
        
        // If a course is crosslisted, we want to send multiple alerts
        $courses = ucla_get_course_info($data->id);
        
        // Do for all coures found
        foreach($courses as $course) {
            // Prepare criteria & payload
            list($criteria, $payload) = syllabus_ws_manager::setup_alert($course);
            // Handle event
            syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_ALERT, $criteria, $payload);        
        }       
    }
}