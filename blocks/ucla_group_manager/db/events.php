<?php

$handlers = array(
    'sync_enrolments_finished' => array(
        'handlerfile'     => '/blocks/ucla_group_manager/eventslib.php',
        'handlerfunction' => 'ucla_group_manager_sync_course_event',
        'schedule'        => 'instant'
    )
);
