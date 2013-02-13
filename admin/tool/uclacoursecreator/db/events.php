<?php
/*
 * Definition of event triggers related to course management (creating, building
 * and destroying).
 */

/* Build courses now:
 *  - For DEV/TEST/STAGE environments, trigger a course build at the next cron
 */

/* Course Deletion:
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
    'course_restored' => array (
         'handlerfile'      => '/admin/tool/uclacoursecreator/eventlib.php',
         'handlerfunction'  => 'move_site_menu_block',
         'schedule'         => 'instant'
     ),    
    'build_courses_now' => array (
         'handlerfile'      => '/admin/tool/uclacoursecreator/eventlib.php',
         'handlerfunction'  => 'build_courses_now',
         'schedule'         => 'cron'
     ), 
    'course_deleted' => array (
         'handlerfile'      => '/admin/tool/uclacoursecreator/eventlib.php',
         'handlerfunction'  => 'handle_course_deleted',
         'schedule'         => 'instant'
     ),
);
