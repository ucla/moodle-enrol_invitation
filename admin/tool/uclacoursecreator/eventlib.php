<?php
/*
 * Library hold functions that will be called for event handling
 */
require_once(dirname(__FILE__) . '/uclacoursecreator.class.php');

/**
 * Respond to events that require course creator to build now.
 * 
 * @param array $terms  An array of terms to run course builder for
 */
function build_courses_now($terms) {
    $bcc = new uclacoursecreator();

    // This may take a while...
    @set_time_limit(0);    
    
    $bcc->set_terms($terms);
    $bcc->cron();    
}

/**
 * Responds to course deletion event. Does the following things:
 * 
 * 1) Delete course request in ucla_request_classes
 * 2) Check MyUCLA url web service to see if course has urls
 * 2a) If has urls and they aren't pointing to current server, skip them
 * 2b) If has urls and they are pointing to the current server, then clear them
 * 
 * 
 * @param object $course    Course object
 * @return boolean          False on error, otherwise true
 */
function handle_course_deleted($course) {
    global $CFG, $DB;
    
    // check if course exists in ucla_request_classes
    $ucla_request_classes = ucla_map_courseid_to_termsrses($course->id);
    if (empty($ucla_request_classes)) {
        return true;
    }
    
    // 1) Delete course request in ucla_request_classes
    $DB->delete_records('ucla_request_classes', array('courseid' => $course->id));
    
    // 2) Check MyUCLA url web service to see if course has urls    
    $cc = new uclacoursecreator();
    $myucla_urlupdater = $cc->get_myucla_urlupdater();
    if (empty($myucla_urlupdater)) {
        return true;    // not installed
    }
    
    $has_error = false;
    foreach ($ucla_request_classes as $ucla_request_class) {
        $course = array();  // make sure it is clean for each loop
        $course = array('term'  => $ucla_request_class->term, 
                        'srs'   => $ucla_request_class->srs);        

        $result = $myucla_urlupdater->send_MyUCLA_urls(array($course));        
        $class_url = array_pop($result);  // returns indexed array    
        
        // 2a) If has urls and they aren't pointing to current server, skip them
        if (strpos($class_url, $CFG->wwwroot) === false) {
            continue;
        }

        // 2b) If has urls and they are pointing to the current server, then clear them        
        $course['url'] = '';
        $result = $myucla_urlupdater->send_MyUCLA_urls(array($course), true);        
        if (false === strpos(array_pop($result), $myucla_urlupdater::expected_success_message)) {
            // has error, track it and continue
            $has_error = true;
        }            
    }
    
    return !$has_error;
}

/**
 * Checks if course has a ucla_course_menu block. If so, then it makes the
 * block have a defaultweight of -10 (move to the very 1st element)
 *  
 * @param object $course 
 * 
 * @return boolean
 */
function move_site_menu_block($course) {
    global $DB;
    
    // get course context
    $context = get_context_instance(CONTEXT_COURSE, $course->id);    
    if (empty($context)) {
        return false;
    }

    // get block instance, if any
    $block_instance = $DB->get_record('block_instances', 
            array('blockname' => 'ucla_course_menu', 
                  'parentcontextid' => $context->__get('id')));
    if (!empty($block_instance)) {
        // calling block_instance will call set_default_location()
        $ucla_course_menu = block_instance('ucla_course_menu', $block_instance);
    }

    return true;
}
