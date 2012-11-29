<?php

/*
 * CCLE-3679
 * 
 * Script to show end-of-course survey on alert block for school of public health courses:
 * 
 * Usage: php ph_survey_alert.php <division> <term>
 * 
 * 
 */


define('CLI_SCRIPT', true);

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

echo "Adding alert notice to Humanities Fall 2012\n";
///
// Select all the course IDs where block will be installed
$sql = "SELECT c.id
    
        FROM {course} c
        JOIN {ucla_request_classes} AS urc ON urc.courseid = c.id
        JOIN {ucla_reg_classinfo} AS rci ON rci.srs = urc.srs AND rci.term = urc.term
        
        WHERE 
            rci.term = :term
        AND rci.division = :division
        AND urc.hostcourse = 1";

// Get records
$records = $DB->get_records_sql($sql, array('term' => '12F', 'division' => 'PH'));


// Put the IDs in an array
$courseidarray = array();

foreach($records as $r) {
    $courseidarray[] = $r->id;
}

// Message we want to display
$text = "# survey alert
As part of our transition to a competencies-based curriculum, we are implementing an online course assessment system called SPHweb. This will replace the scantron course evaluations.  

For Fall quarter 2012, we are asking you to complete end of quarter evaluations for your courses using the online system by Dec. 9. The online end-of-course surveys should be activated on your course websites effective immediately.  Please log on to the system using the link below, then click [MyHome] and use your bol online login to access the system.
>{http://portal.ph.ucla.edu/sphweb/} Survey link";

// Only apply if we have any records to display
if(!empty($courseidarray)) {
    // Create event data
    $data = array(
        'courses' => $courseidarray,
        'entity' => ucla_alert::ENTITY_ITEM,
        'starts' => 'Dec 01 2012 12:00',
        'expires' => 'Dec 09 2012 20:10',
        'text' => $text
    );

    // Trigger event
    events_trigger('ucla_alert_post', $data);

    // Display count and a list of course ids
    $n = count($courseidarray);
    
    echo "There were $n courses alerted.\n";
    echo "IDs: [ ". implode(', ', $courseidarray) . " ]\n";
}

echo "...done\n";

/// END
