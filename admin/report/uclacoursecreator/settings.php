<?php

// Cannot open script independently
defined('MOODLE_INTERNAL') || die;

// Add UCLA course creator to the admin block
$ADMIN->add('courses', new admin_externalpage(
        'uclacoursecreator', 
        get_string('uclacoursecreator', 'report_uclacoursecreator'),
        $CFG->wwwroot . '/' . $CFG->admin . '/report/uclacoursecreator/index.php'
        // Specify a capability to view this page here
    ));

