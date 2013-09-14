<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');

$sectionreturn = optional_param('sr', null, PARAM_INT);

$public        = optional_param('public', 0, PARAM_INT);
$private       = optional_param('private', 0, PARAM_INT);

$url = new moodle_url('/local/publicprivate/mod.php');
$url->param('sr', $sectionreturn);

$PAGE->set_url($url);

require_login();

/**
 * If optional parameter $public is set, disable public/private protection over
 * the course module instance.
 *
 * @author ebollens
 * @version 20110719
 *
 * @throws PublicPrivate_Course_Exception
 * @throws PublicPrivate_Module_Exception
 */
if ($public and confirm_sesskey()) {

    if (!$cm = get_coursemodule_from_id('', $public, 0, true)) {
        print_error('invalidcoursemodule');
    }

    require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
    $publicprivate_course = new PublicPrivate_Course($cm->course);
    
    if($publicprivate_course->is_activated()) {
        require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
        PublicPrivate_Module::build($cm)->disable();
    } else {
        throw new PublicPrivate_Module_Exception('Illegal action as public/private is not enabled for the course.', 900);
    }

    rebuild_course_cache($cm->course);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    redirect(course_get_url($course, $cm->sectionnum, array('sr' => $sectionreturn)));

/**
 * If optional parameter $private is set, enable public/private protection over
 * the course module instance.
 *
 * @author ebollens
 * @version 20110719
 *
 * @throws PublicPrivate_Course_Exception
 * @throws PublicPrivate_Module_Exception
 */
} else if ($private and confirm_sesskey()) {

    if (!$cm = get_coursemodule_from_id('', $private, 0, true)) {
        print_error('invalidcoursemodule');
    }

    require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
    $publicprivate_course = new PublicPrivate_Course($cm->course);

    if($publicprivate_course->is_activated()) {
        require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
        PublicPrivate_Module::build($cm)->enable();
    } else {
        throw new PublicPrivate_Module_Exception('Illegal action as public/private is not enabled for the course.', 900);
    }

    rebuild_course_cache($cm->course);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    redirect(course_get_url($course, $cm->sectionnum, array('sr' => $sectionreturn)));
} else {
    print_error('unknowaction');
}