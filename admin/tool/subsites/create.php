<?php
/**
 *  Create sub-sites.
 *
 **/
require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$supercourse = required_param('course', PARAM_INT);
$upgradeuser = required_param('user', PARAM_INT);

require_login($supercourse);

$coursecontext = get_context_instance(CONTEXT_COURSE, $supercourse);

tool_subsites::check_requirements($coursecontext);

$course = $DB->get_record('course', array('id' => $supercourse), 
    subsite::COURSE_FIELDS, MUST_EXIST);

$user = $DB->get_record('user', array('id' => $upgradeuser), 
    subsite::USER_FIELDS,  MUST_EXIST);

// create course
$subsite = new subsite($course, $user);

if (!$subsite->exists()) {
    $subsite->create();
} else {
    throw new tool_subsite_exception('alreadyexists');
}

// TODO something with groups, most likely clone them over

// Display confirmation & exit
