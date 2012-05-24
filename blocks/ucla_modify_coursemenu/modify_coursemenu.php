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

$PAGE->set_url('/blocks/ucla_rearrange/rearrange.php', 
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

/*
$course_preferences = new ucla_course_prefs($course_id);

$course_preferences->set_preference('landing_page', '513');
$course_preferences->commit();

echo json_encode($course_preferences->get_course_preferences($course_id));
*/

 



$modify_coursemenu_form = new ucla_modify_coursemenu_form(
    null,
    array(
        'course_id' => $course_id, 
        'sections'  => $sections,
        'topic'     => $topic_num
    ),
    'post',
    '',
    array('class' => 'ucla_modify_coursemenu_form')
);

if ($data = $modify_coursemenu_form->get_data()) {
    echo json_encode($data);
   // echo "HERE";
    //echo json_encode($data->name);
    
    foreach ($data->name as $secid => $secname) {
     //echo "Key: $; Value: $value<br />\n";   
    
    
    
    $sectionDB = $DB->get_record('course_sections', array('id' => "$secid"), '*', MUST_EXIST);

    $sectionDB->name = $secname;
    //echo json_encode($sectionDB);
     //set_section_visible($courseid, $sectionnumber, $visibility) {
   // set_section_visible("506", 5, 1);
    //$sectionDB->name = "Week 2";
    $DB->update_record('course_sections', $sectionDB);
    }
    
   // foreach ($data->hide as $sectnum => $hide)
    
    for ($i = 1; $i < count($data->hide); $i++) {
        set_section_visible($course_id, $i, $data->hide[$i]^1);
    }
    
        if(isset($data->delete)) {
        
        $numsections = count($sections)-1;
        foreach($data->delete as $secnum => $delete) {
            
        
        $sql = "delete from mdl_course_sections WHERE course='$course_id' AND section='$secnum'";
       // echo $sql;
        $DB->execute($sql);
        $numsections--;          
        
        //insert into mdl_course_sections VALUES('615', '506', '6', 'Week 6', '', '1', 'NULL', '1');
    }
    $sql = "update mdl_course set numsections='$numsections' where id='$course_id'";
    $DB->execute($sql);
    }
    
    /*
    foreach ($data->delete as $secnum => $delete) {
        $sql = "delete from mdl_course_sections WHERE course='$course_id' AND section='$secnum'";
        echo $sql;
        //$DB->execute($sql);
    }
    */
  /*
      $sql = "delete from mdl_course_sections WHERE course=506 AND section=6
";
        $DB->execute($sql);
    */
    
        //insert into mdl_course_sections VALUES('615', '506', '6', 'Week 6', '', '1', 'NULL', '1');
        //select section,name FROM mdl_course_sections WHERE course="506";
    //rebuild_course_cache($course_id);

        redirect(new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
                array('course_id' => $course_id, 'topic' => $topic_num)));
        
}





// TODO put a title


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
    //$sql = "insert into mdl_course_sections VALUES('615', '506', '6', 'Week 6', '', '1', 'NULL', '1')";
    
    $sql = "update mdl_course set numsections='$numsections' where id='$course_id'";
    $DB->execute($sql);
    //http://localhost:8080/moodle/course/view.php?id=506&topic=-4
    rebuild_course_cache($course_id);
           redirect(new moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
                array('course_id' => $course_id, 'topic' => $topic_num)));
}
     

echo $OUTPUT->footer();


?>