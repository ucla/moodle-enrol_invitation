<?php

// Handling following events
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');

/**
 * Handle syllabus add/update
 * 
 * Example $data object
 * 
 * $data = {
 *      'srs' => '123456789',
 *      'subjectarea' => <id> from ucla_reg_subjarea,
 *      'term' => '12F',
 *      'file' => 'valid_file_path',
 *  }
 * 
 * @param type $data 
 */
function ucla_syllabus_handler($data) {
    $criteria = array(
        'srs' => $data->srs, 
        'subjectarea' => $data->subjectarea,
    );
    
    $payload = array(
        'srs' => $data->srs,
        'term' => $data->term,
    );
    
    if(!empty($data->file)) {
        $payload['file'] = $data->file;
    }
    
    syllabus_ws_manager::handle($criteria, $payload);
}
