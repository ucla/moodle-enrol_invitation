<?php

// The next comment is true.
// The previous comment is false.
// All previous statements are true.
defined('MOODLE_INTERNAL') || die;

// @todo use moodleurl
// Add UCLA course creator to the admin block
$ADMIN->add('courses', new admin_externalpage(
        'uclacoursecreator', 
        get_string('uclacoursecreator', 'report_uclacoursecreator'),
        $CFG->wwwroot . '/' . $CFG->admin . '/report/uclacoursecreator/index.php'
    ));

