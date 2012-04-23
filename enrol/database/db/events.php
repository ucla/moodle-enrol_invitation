<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/enrol/database/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    )
);
