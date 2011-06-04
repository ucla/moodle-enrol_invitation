<?php

require_once(dirname(__FILE__) . '/ucla_cp_module.php');

$modules[] = new ucla_cp_module('ucla_cp_mod_common');

$temp_tag = array('ucla_cp_mod_common');
$temp_cap = 'moodle/course:update';

$ta_cap = 'moodle/course:enrolreview';

$modules[] = new ucla_cp_module('add_file', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('email_students', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('add_link', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('office_hours', new moodle_url('view.php'), 
    $temp_tag, $ta_cap);
$modules[] = new ucla_cp_module('modify_sections', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('rearrange', new moodle_url('view.php'), 
    $temp_tag, $temp_cap, array('pre' => false, 'post' => false));
$modules[] = new ucla_cp_module('turn_editing_on', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);

$modules[] = new ucla_cp_module('ucla_cp_mod_other');
$temp_tag = array('ucla_cp_mod_other');

$modules[] = new ucla_cp_module('add_activity', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
$modules[] = new ucla_cp_module('add_resource', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);
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
