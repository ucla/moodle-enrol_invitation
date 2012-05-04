<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    )
);
