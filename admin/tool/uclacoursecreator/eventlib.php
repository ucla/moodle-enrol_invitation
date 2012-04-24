<?php
/*
 * Library hold functions that will be called for event handling
 */

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
        $block_instance->defaultweight = -10;
        $DB->update_record('block_instances', $block_instance); 
    }

    return true;
}
?>
