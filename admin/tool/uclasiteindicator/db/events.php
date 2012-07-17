<?php

$handlers = array (
    'course_deleted' => array (
         'handlerfile'      => '/admin/tool/uclasiteindicator/eventlib.php',
         'handlerfunction'  => 'delete_indicator',
         'schedule'         => 'instant'
     )
);
