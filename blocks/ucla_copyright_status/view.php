<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB;

require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/blocks/ucla_copyright_status/lib.php');
$courseid = required_param('courseid', PARAM_INT); // course ID
$action = optional_param('action', null, PARAM_TEXT); 

if (! $course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}       

require_login($course);

if ($action == 'edit'){
	$data = data_submitted();
	unset($data->action);
	update_copyright_status($data);
}
    
$context = get_context_instance(CONTEXT_COURSE, $courseid, MUST_EXIST);

init_copyright_page($course, $courseid, $context);

set_editing_mode_button();

echo $OUTPUT->header();

$filter = optional_param('filter_copyright', $CFG->sitedefaultlicense, PARAM_TEXT); 
display_copyright_status_contents($courseid, isset($filter)?$filter:'all');


echo $OUTPUT->footer();


