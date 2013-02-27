<?php
/*
 * Library hold functions that will be called for event handling
 */

/**
 * Checks if course has a ucla_course_menu block. If so, then it makes the
 * block have a defaultweight of -10 (move to the very 1st element)
 *
 * @param mixed $param
 *
 * @return boolean
 */
function move_site_menu_block($param) {
    global $DB;

    // handle different parameter types (course_created vs course_restored)
    if (isset($param->id)) {
        $courseid = $param->id;    // course created
    } else {
        // only respond to course restores
        if ($param->type != backup::TYPE_1COURSE) {
            return true;
        }
        $courseid = $param->courseid;  // course restored
    }

    // get course context
    $context = context_course::instance($courseid);
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
