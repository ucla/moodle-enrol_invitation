<?php  

define('NO_MOODLE_COOKIES', true); // session not used here
require_once '../../../config.php';

$id = required_param('id', PARAM_INT); // course id
if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_user_key_login('grade/export', $id); // we want different keys for each course

if (empty($CFG->gradepublishing)) {
    print_error('gradepubdisable');
}

$context = get_context_instance(CONTEXT_COURSE, $id);
require_capability('gradeexport/myucla:publish', $context);

// use the same page parameters as export.php and append &key=sdhakjsahdksahdkjsahksadjksahdkjsadhksa
require 'export.php';

