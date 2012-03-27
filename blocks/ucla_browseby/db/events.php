<?php

$basic_handle = array(
    'handlerfile'     => '/blocks/ucla_browseby/eventlib.php',
    'schedule'        => 'instant'
);

// Event-based reactions
$handlers = array();

$basic_handle['handlerfunction'] = 'browseby_sync_courses';
$handlers['course_creator_finished'] = $basic_handle;

$basic_handle['handlerfunction'] = 'browseby_sync_deleted';
$handlers['course_requests_deleted'] = $basic_handle;
