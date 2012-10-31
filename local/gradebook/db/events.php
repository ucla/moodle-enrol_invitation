<?php

$handlers = array (
    'ucla_grade_grade_updated' => array (
        'handlerfile'      => '/local/gradebook/eventlib.php',
        'handlerfunction'  => 'ucla_grade_grade_updated',
        'schedule'         => 'instant',
        'internal'         => 0,
    ),

    'ucla_grade_item_updated' => array (
        'handlerfile'      => '/local/gradebook/eventlib.php',
        'handlerfunction'  => 'ucla_grade_item_updated',
        'schedule'         => 'instant',
        'internal'         => 0,
    ),
);