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

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

$sql = "SELECT t1.courseid FROM mdl_ucla_request_classes t1, mdl_ucla_reg_classinfo t2 where t1.term = t2.term and t1.srs = t2.srs and t2.division = 'PH' and t2.term = '12F'";

$courseidarray = $DB->get_records_sql($sql, array('division'=>'PH', 'term'=>$CFG->currentterm));

$text = "# As part of our transition to a competencies-based curriculum, we are implementing an online course assessment system called SPHweb. This will replace the scantron course evaluations.  For Fall quarter 2012, we are asking you to complete end of quarter evaluations for your courses using the online system by Dec. 9. The online end-of-course surveys should be activated on your course websites effective immediately.  Please log on to the system using the this 
>{http://portal.ph.ucla.edu/sphweb/
} survey link, then click [MyHome] and use your bol online login to access the system.";

// How is this data going to look like?
$o = array(
    'courses' => $courseidarray,
    'entity' => ucla_alert::ENTITY_ITEM,
    'starts' => 'Dec 01 2012 12:00',
    'expires' => 'Dec 09 2012 20:10',
    'text' => $text
);
events_trigger('ucla_alert_post', $o);
echo 'trigered event: ';
/// END
