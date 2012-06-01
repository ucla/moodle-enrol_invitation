<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/ucla/ucla_course_prefs.class.php');
$thispath = '/blocks/ucla_modify_coursemenu';
//require_once($CFG->dirroot . $thispath . '/block_ucla_modify_coursemenu.php');
require_once($CFG->dirroot . $thispath . '/modify_coursemenu_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);
$topic_num = optional_param('topic', null, PARAM_INT);

$course = $DB->get_record('course', array('id' => $course_id),
    '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', 
        array('course_id' => $course_id, 'topic' => $topic_num));

// set editing url to be topic or default page
$go_back_url = new moodle_url('/course/view.php', 
        array('id' => $course_id, 'topic' => $topic_num));
set_editing_mode_button($go_back_url);

$sections = get_all_sections($course_id);

$sectnums = array();
$sectionnames = array();
$sectionvisibility = array();
foreach ($sections as $section) {
    $sid = $section->id;
    $sectids[$sid] = $sid;
    $sectnums[$sid] = $section->section;
    $sectionnames[$sid] = get_section_name($course, $section);
    $sectionvisibility[$sid] = $section->visible;
}

$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);


$course_preferences = new ucla_course_prefs($course_id);
   $landing_page = $course_preferences->get_preference('landing_page', false);
    if ($landing_page === false) {
        $landing_page = 0;
    } 

    //reorder the section numbers in the database
    $counter = 1;
    foreach($sections as $key => $value) {
    if($key != 0) {
        $sql = "update mdl_course_sections set section='$counter' where course='$course_id' and section='$key'";
        $DB->execute($sql);
        $counter++;       
    }
}

$modify_coursemenu_form = new ucla_modify_coursemenu_form(
    null,
    array(
        'course_id' => $course_id, 
        'sections'  => $sections,
        'topic'     => $topic_num,
        'landing_page' => $landing_page
    ),
    'post',
    '',
    array('class' => 'ucla_modify_coursemenu_form')
);

//extract the data from the form and update the database
if ($data = $modify_coursemenu_form->get_data()) {
    
    echo json_encode($data);
    //updating the names
    foreach ($data->name as $secid => $secname) { 
    $sectionDB = $DB->get_record('course_sections', array('id' => "$secid"), '*', MUST_EXIST);
    $sectionDB->name = $secname;
    $DB->update_record('course_sections', $sectionDB);
    }
    
    //update the section visibility
    foreach ($data->hide as $sectnum => $hide) {
        if($sectnum != 0) set_section_visible($course_id, $sectnum, $data->hide[$sectnum]^1);
    }
    
        $numsections = count($sections)-1;        
        if(isset($data->delete)) {
        //delete the checked sections

        foreach($data->delete as $secnum => $delete) {
        if($sectnum != 0) {
        $sql = "delete from mdl_course_sections WHERE course='$course_id' AND section='$secnum'";
        $DB->execute($sql);
        $numsections--; 
        }
        }
    
    //update the number of sections in the database
    $sql = "update mdl_course set numsections='$numsections' where id='$course_id'";
    $DB->execute($sql);  
    }
   
    $course_preferences->set_preference('landing_page', $data->landing);
    $course_preferences->commit();
    
    redirect(new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
                array('course_id' => $course_id, 'topic' => $topic_num)));
        
}


$restr = get_string('ucla_modify_course_menu', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";

 $PAGE->requires->css('/blocks/ucla_modify_coursemenu/styles.css');
$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');
 $modify_coursemenu_form->display();
 

 
     echo "<form name='input' action='' method='post'>
<input type='submit' value='Add New Section' name='submit' />
</form>";

     
if(isset($_POST['submit'])) {
    $numsections = count($sections)-1;
    $numsections++;
    $sql = "update mdl_course set numsections='$numsections' where id='$course_id'";
    $DB->execute($sql);

    setup_sections ($numsections, $sections, $DB, $course);
    rebuild_course_cache($course_id);
           redirect(new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
                array('course_id' => $course_id, 'topic' => $topic_num)));
}
     

echo $OUTPUT->footer();

?>