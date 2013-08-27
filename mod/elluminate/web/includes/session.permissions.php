<?php
/**
 * Check the current context to ensure that the $pageSession can be viewed.
 * 
 * This only checks course login and top-level session permissions.
 * Specific permissions that are required at a per-page level should not be checked here.
 * 
 * Need to be set before including:
 * 
 *  $viewContainer (Elluminate_HTML_Session_Container instance)
 *  $cm  (Moodle Course Module)
 *  
 *  
 * Returns:
 *    $permissions - Session Permissions Object
 *  
 */
$permissions = $ELLUMINATE_CONTAINER['sessionPermissions'];
$permissions->setContext($context);
$permissions->courseModule = $cm;
$permissions->userid = $USER->id;
$permissions->pageSession = $pageSession;
