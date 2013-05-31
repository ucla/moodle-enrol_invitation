<?php  

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_myucla.php';

$id                = required_param('id', PARAM_INT); // course id
$groupid           = optional_param('groupid', 0, PARAM_INT);
$itemids           = required_param('itemids', PARAM_RAW);
$export_feedback   = optional_param('export_feedback', 0, PARAM_BOOL);
$updatedgradesonly = optional_param('updatedgradesonly', false, PARAM_BOOL);
$displaytype       = optional_param('displaytype', $CFG->grade_export_displaytype, PARAM_INT);
$decimalpoints     = optional_param('decimalpoints', $CFG->grade_export_decimalpoints, PARAM_INT);
$filetype          = optional_param('filetype', 'csv', PARAM_FILE);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/myucla:view', $context);

// START UCLA MOD: CCLE-3980 - Add logging to Gradebook & Export to MyUCLA format pages
$url = '/export/myucla/export.php?id=' . $course->id;
add_to_log($course->id, 'grade', 'export myucla', $url);
// END UCLA MOD: CCLE-3980

// print all the exported data here
$export = new grade_export_myucla($course, $groupid, $itemids, $export_feedback, $updatedgradesonly, $displaytype, $decimalpoints, $filetype);
$export->print_grades();

