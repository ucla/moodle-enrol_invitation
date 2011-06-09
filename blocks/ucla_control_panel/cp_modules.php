<?php

require_once(dirname(__FILE__) . '/ucla_cp_module.php');
global $CFG;

// Special section for special people
$temp_cap = 'moodle/course:update';
$modules[] = new ucla_cp_module('ucla_cp_mod_common', null, null, $temp_cap);

$temp_tag = array('ucla_cp_mod_common');

$spec_ops = array('pre' => false, 'post' => true);
$ta_cap = 'moodle/course:enrolreview';

$modules[] = new ucla_cp_module('add_file', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('email_students', new moodle_url('view.php'), 
    $temp_tag, $temp_cap, $spec_ops);
$modules[] = new ucla_cp_module('add_link', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('edit_office_hours', new moodle_url('view.php'), 
    array('ucla_cp_mod_common', 'ucla_cp_mod_other'), null);
$modules[] = new ucla_cp_module('modify_sections', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('modify_modules', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('turn_editing_on', new moodle_url('view.php'), 
    $temp_tag, $temp_cap, $spec_ops);

// Other Functions
$modules[] = new ucla_cp_module('ucla_cp_mod_other');
$temp_tag = array('ucla_cp_mod_other');

$modules[] = new ucla_cp_module('add_activity', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('add_resource', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('edit_profile', new moodle_url(
        $CFG->wwwroot . '/user/edit.php'), 
    $temp_tag, null);

$modules[] = new ucla_cp_module('add_subheading', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('add_text', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('import_classweb', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('create_tasite', new moodle_url('view.php'), 
    $temp_tag, $ta_cap);
$modules[] = new ucla_cp_module('view_roster', new moodle_url('view.php'), 
    $temp_tag, $ta_cap);

// Advanced functions
$modules[] = new ucla_cp_module('ucla_cp_mod_advanced', null, null, $temp_cap);

$temp_tag = array('ucla_cp_mod_advanced');

$modules[] = new ucla_cp_module('assign_roles', new moodle_url('view.php'),
    $temp_tag, $temp_cap);

