<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    ),
    'mod_created' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'delete_repo_keys',
        'schedule'        => 'instant'
    ),
    'mod_updated' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'delete_repo_keys',
        'schedule'        => 'instant'
    ),
    'assessable_file_uploaded' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'delete_repo_keys',
        'schedule'        => 'instant'
    ),
    'course_restored' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'course_restored_enrol_check',
        'schedule'        => 'instant'
    ),
);
