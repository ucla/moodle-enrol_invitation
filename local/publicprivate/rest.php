<?php


if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');

// Initialise ALL the incoming parameters here, up front.
$courseid   = required_param('courseId', PARAM_INT);
$class      = required_param('class', PARAM_ALPHA);
$field      = required_param('field', PARAM_ALPHA);
$id         = required_param('id', PARAM_INT);

$PAGE->set_url('/local/publicprivate/rest.php', array('courseId'=>$courseid,'class'=>$class));

//NOTE: when making any changes here please make sure it is using the same access control as course/mod.php !!

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if ($class === 'resource') {
    $cm = get_coursemodule_from_id(null, $id, $course->id, false, MUST_EXIST);
    require_login($course, false, $cm);
    require_sesskey();

//    $modcontext = context_module::instance($cm->id);

    echo $OUTPUT->header(); // send headers

    switch ($field) {

        case 'public':

            require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
            $publicprivate_course = new PublicPrivate_Course($cm->course);

            if($publicprivate_course->is_activated()) {
                require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
                PublicPrivate_Module::build($cm)->disable();
            }
                        
            break;

        case 'private':

            require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
            $publicprivate_course = new PublicPrivate_Course($cm->course);

            if($publicprivate_course->is_activated()) {
                require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
                PublicPrivate_Module::build($cm)->enable();
            }
            break;
    }
}


