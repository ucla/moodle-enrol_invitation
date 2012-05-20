<?php
/*
 * Definition of event triggers related to course management (creating and 
 * destroying). For now we are just defining a trigger for when courses are 
 * created. But in the future we hope to include a trigger for when courses are 
 * deleted.
 */

/* Course Creation:
 *  - ensure that ucla_course_menu block is properly located in the top of the 
 *    left-hand side of the screen
 */

/* Course Deletion: (to do)
 *  - ensure that if a course has a My.UCLA url pointing to it, it should be
 *    cleared
 *  - ensure that entry in ucla_request_classes table is deleted
 */

$handlers = array (
    'course_created' => array (
         'handlerfile'      => '/admin/tool/uclacoursecreator/eventlib.php',
         'handlerfunction'  => 'move_site_menu_block',
         'schedule'         => 'instant'
     ),
    'build_courses_now' => array (
         'handlerfile'      => '/admin/tool/uclacoursecreator/eventlib.php',
         'handlerfunction'  => 'build_courses_now',
         'schedule'         => 'cron'
     ), 
);
