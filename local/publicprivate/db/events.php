<?php

/*
 * Event handling
 */

$handlers = array (
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