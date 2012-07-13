<?php
/**
 * enrol/invitation/db/events.php
 *  
 * @package    enrol
 * @subpackage invitation 
 */

$handlers = array(
    // Add site invitation plugin when courses are created. Note that only
    // managers can manage/configure enrollment plugin, so we need to add it
    // automatically for instructors. Instructors can hide or delete plugin.
    'course_created' => array(
        'handlerfile'     => '/enrol/invitation/eventslib.php',
        'handlerfunction' => 'add_site_invitation_plugin',
        'schedule'        => 'instant'
    ),
    'course_restored' => array (
         'handlerfile'      => '/enrol/invitation/eventslib.php',
         'handlerfunction'  => 'add_site_invitation_plugin',
         'schedule'         => 'instant'
     ),    
);
