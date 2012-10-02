<?php

// Listening to following events
$handlers = array (
    'ucla_syllabus_added' => array (
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_handler',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),

    'ucla_syllabus_updated' => array (
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_handler',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),
    
    'course_created' => array(
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_course_alert',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),
);