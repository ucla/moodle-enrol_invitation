<?php
/*
 * Definition of event triggers related to site menu block
 */

/* Course Creation/Restore:
 *  - ensure that ucla_course_menu block is properly located in the top of the 
 *    left-hand side of the screen
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
);
