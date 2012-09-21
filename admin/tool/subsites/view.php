<?php
/**
 *  TA Site creator tool.
 *
 **/
require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$courseid = optional_param('course', null, PARAM_INT);

require_login($courseid);

if ($courseid) {
    $targetcontext = get_context_instance(CONTEXT_COURSE, $courseid);
    $courses = $DB->get_records('course', array('id' => $courseid),
        subsite::COURSE_FIELDS);
} else {
    // Hack because not implemented yet
    required_param('course', PARAM_INT);
}

tool_subsites::check_requirements($targetcontext);

$subsiteusers = get_users_by_capability($targetcontext, 
    'tool/subsites:canhavesubsite', 
    subsite::const_fields_prefixed('user', 'u.'));

var_dump($subsiteusers);

// All the possible subsites
$subsites = array();

// Display form with possible users to make sites for.
// Maybe use mForms?
foreach ($courses as $course) {
    foreach ($subsiteusers as $subsiteuser) {
        $subsites[] = new subsite($course, $subsiteuser);
    }
}

var_dump($subsites);
