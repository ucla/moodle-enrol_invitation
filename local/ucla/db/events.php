<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    ),
    'course_restored' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'course_restored_enrol_check',
        'schedule'        => 'instant'
    ),
    'ucla_weeksdisplay_changed' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'hide_past_courses',
        'schedule'        => 'instant'
    ),
);
