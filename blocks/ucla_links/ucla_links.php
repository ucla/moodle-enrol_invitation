<?php
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');


    $course_id = optional_param('course_id', 0, PARAM_INT); // course ID

    if (! $course = $DB->get_record('course', array('id'=>$course_id))) {
        print_error('coursemisconf');
    }

// Initialize $PAGE
    $PAGE->set_url('/blocks/ucla_links/ucla_links.php', array('courseid' => $courseid));
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
    $PAGE->set_title($course->shortname.': UCLA Links');
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagetype('course-*' . $course->format);
    $PAGE->set_pagelayout('course');

    if ($courseid == SITEID) {
        $PAGE->navbar->add(get_string('pluginname','ucla_links'));
    } else {
        $countcategories = $DB->count_records('course_categories');
        if ($countcategories > 1 || ($countcategories == 1 && $DB->count_records('course') > 200)) {
            $PAGE->navbar->add(get_string('categories'));
        } else {
            $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/category.php?id='.$course->category));
            $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php?id='.$course_id));
            $PAGE->navbar->add(get_string('pluginname','block_ucla_links'));
        }
    }
    // using core renderer
    echo $OUTPUT->header();
    // Links not complete, placeholder for testing...
    // This will probably have to be refactored later...
    echo $OUTPUT->box("<p>Useful Links for UCLA Class Sites</p>
        <ul>
            <li>Constitution Day at UC</li>
        </ul>
        <ul>
            <li>Academic</li>
            <ul>
                <li>Schedule of Classes</li>
                <li>General Catalog</li>
                <li>Academic Calendar</li>
            </ul>
        </ul>
         <ul>
            <li>Digital Images</li>
            <ul>
                <li>Library of Congress</li>
                <li>UCLA Arts Library Image Databases</li>
                <li>UCLA Library Digital Collections</li>
            </ul>
        </ul>
         <ul>
            <li>Libraries</li>
            <ul>
                <li>UCLA Library Home Page</li>
                <li>UCLA Library Catalog</li>
                <li>Course Reserves</li>
            </ul>
        </ul>
            ");
    echo $OUTPUT->footer();
?>
