<?php
/**
 * Standard Moodle Page Header to output top and side navigation
 * 
 * REQUIRED INPUT:
 *    $pageSession - Elluminate_Session Object
 *    $pageTitle - Title for Page (displayed in browser bar)
 *    $pageUrl - URL for page
 *    $pageHeading - descriptive text for page (optional)
 *    $pageButton - button for display in top nav bar (optional)
 *    $pageGroups - does the page require a moodle group select control
 *    $hideBox - boolean flag that can be used to prevent output of the default BOX_START (optional)
 */
if ($pageTitle != ''){
   $PAGE->set_title($pageTitle);
}

$PAGE->set_cm($cm);
$PAGE->set_context($context);

if (isset($pageUrl)){
   $PAGE->set_url($pageUrl);
}

$heading = '';
if (isset($pageSession)){
   if (isset($pageSession->name)){
      $heading = $pageSession->name . " - ";
   }
}

if ($pageHeading != ''){
   $heading .= $pageHeading;
}
$heading = format_string($heading);

if (isset($pageButton)){
   $PAGE->set_button($pageButton);
}

$PAGE->set_heading($heading);
$PAGE->set_pagelayout('standard');
$PAGE->set_course($COURSE);
echo $OUTPUT->header();

if (isset($pageGroups)){
   echo groups_print_activity_menu($cm, new moodle_url($pageUrl));
}

$startBox = true;
if (isset($hidebox)){
   if ($hidebox == true){
      $startBox = false;
   }
}

if ($startBox){
   echo $OUTPUT->box_start();
}