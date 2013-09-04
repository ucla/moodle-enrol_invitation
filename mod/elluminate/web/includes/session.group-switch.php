<?php
/**
 * This page can be included to check if a course level group mode force has been
 * set that will require this session to change it's mode
 * 
 * Required to be Set:
 *    $pageSession
 *    
 * Returns:
 *    $pageSession (updated mode if required)
 *    $switcher -> switcher object, used later in the page to display switching ui   
 * 
 */
$switcher = $ELLUMINATE_CONTAINER['groupSwitcher'];
$pageSession = $switcher->checkForRequiredGroupModeChange($pageSession, $cm->course);