<?php

function xmldb_block_ucla_office_hours_install(){
    //Currently, course/user pairs are added in officehours.php
    //When a user updates his/her information, 
    //the course/user pair is added in at that time,
    //if they are not already in the database
    //Maybe we should add in course/user pairs in here?
    
    /*
    global $CFG, $DB;
    
    $course_list = $DB->get_records('course', NULL, '', 'id, category');
    for($iter = 1; $iter <= sizeof($course_list); $iter++) {
        $course_id = $course_list[$iter]->id;
        //Make sure we are not adding a course with a category or id of 0
        if($course_list[$iter]->category != 0 && $course_id != 0){
            if(! $DB->get_record('ucla_officehours', array('course' => $course_id)) ) {
                $DB->set_field('ucla_officehours', 'courseid', $course_id);
            } else {
                //Course is already in table
            }
        }
    }
    */
    
    return true;
}

?>
