<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('server', new admin_externalpage('ucla_syllabus_webservice', 
            'Syllabus web service', 
            "$CFG->wwwroot/local/ucla_syllabus/webservice/index.php"
            )
        );
