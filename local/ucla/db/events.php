<?php

$handlers = array(
    'course_creator_finished' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'ucla_sync_built_courses',
        'schedule'        => 'instant'
    ),
    'mod_created' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'local_ucla_handle_mod',
        'schedule'        => 'instant'
    ),
    'mod_updated' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'local_ucla_handle_mod',
        'schedule'        => 'instant'
    ),
    'assessable_file_uploaded' => array(
        'handlerfile'     => '/local/ucla/eventslib.php',
        'handlerfunction' => 'delete_repo_keys',
        'schedule'        => 'instant'
    ),
);
