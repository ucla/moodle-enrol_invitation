<?php

// Listening to following events
$handlers = array (
   'ucla_format_notices' => array(
        'handlerfile'      => '/blocks/ucla_copyright_status/eventlib.php',
        'handlerfunction'  => 'handle_ucla_copyright_status_notice',
        'schedule'         => 'instant',    // instant for message passing
        'internal'         => 1,
    ),
);