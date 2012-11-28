<?php

/*
 * Event handling
 */

$handlers = array (
    'course_created' => array (
        'handlerfile'      => '/local/publicprivate/lib.php',
        'handlerfunction'  => 'handle_course_created',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'course_updated' => array (
        'handlerfile'      => '/local/publicprivate/lib.php',
        'handlerfunction'  => 'handle_course_updated',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'mod_created' => array (
        'handlerfile'      => '/local/publicprivate/lib.php',
        'handlerfunction'  => 'handle_mod',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
 
    'mod_updated' => array (
        'handlerfile'      => '/local/publicprivate/lib.php',
        'handlerfunction'  => 'handle_mod',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);