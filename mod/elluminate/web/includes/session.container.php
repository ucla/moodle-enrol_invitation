<?php
/**
 * Initialize the Session View Container
 * 
 * TODO: this should be able to return the view object specific to the page as well
 * 
 * Required to be set on load:
 * 
 * $pageSession
 * $cm
 * $context
 * 
 * Will Return: 
 * 
 * $viewContainer
 * 
 */
$viewContainer = Elluminate_HTML_Session_Container::loadView($pageSession, $cm, $USER, $context);
$sessionView = $viewContainer->getSessionView();