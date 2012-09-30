<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    ),
    'mod_created' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'check_mod_parent_visiblity',
        'schedule'        => 'instant'
    ),
    'mod_updated' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'check_mod_parent_visiblity',
        'schedule'        => 'instant'
    ),
);
