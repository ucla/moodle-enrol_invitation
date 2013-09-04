<?php
/**
 * Initialize the Session View Container
 * 
 * Required to be set on load:
 * 
 * $pageSession
 * $cm
 * $USER
 * 
 * Will Return: 
 * 
 * $sessionView
 * 
 */
$pageView = $ELLUMINATE_CONTAINER['sessionView'];
$pageView->courseModuleId = $cm->id;
$pageView->sessionKey = sesskey();
$pageView->courseModuleId = $cm->id;
$pageView->pageSession = $pageSession;