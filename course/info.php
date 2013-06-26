<?php

/// Displays external information about a course

    require_once("../config.php");
    require_once("lib.php");

    $id   = optional_param('id', false, PARAM_INT); // Course id
    $name = optional_param('name', false, PARAM_RAW); // Course short name

    if (!$id and !$name) {
        print_error("unspecifycourseid");
    }

    if ($name) {
        if (!$course = $DB->get_record("course", array("shortname"=>$name))) {
            print_error("invalidshortname");
        }
    } else {
        if (!$course = $DB->get_record("course", array("id"=>$id))) {
            print_error("invalidcourseid");
        }
    }

    $site = get_site();

    if ($CFG->forcelogin) {
        require_login();
    }

    $context = context_course::instance($course->id);
    if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $context)) {
        // START UCLA MOD: CCLE-3786 - Preventing past course access for students
        //print_error('coursehidden', '', $CFG->wwwroot .'/');
        $config_week = get_config('local_ucla', 'student_access_ends_week');
        $alt_msg_shown = false;
        if (!empty($config_week)) {
            // need to give different message if user is viewing a past
            // hidden site
            require_once($CFG->dirroot . '/local/ucla/lib.php');
            if (is_past_course($course)) {
                print_error('coursehidden', 'local_ucla', $CFG->wwwroot .'/');
                $alt_msg_shown = true;
            }
        }
        if (empty($alt_msg_shown)) {
            print_error('coursehidden', '', $CFG->wwwroot .'/');
        }
        // END UCLA MOD: CCLE-3786
    }

    $PAGE->set_course($course);
    $PAGE->set_pagelayout('course');
    $PAGE->set_url('/course/info.php', array('id' => $course->id));
    $PAGE->set_title(get_string("summaryof", "", $course->fullname));
    $PAGE->set_heading(get_string('courseinfo'));
    $PAGE->navbar->add(get_string('summary'));

    echo $OUTPUT->header();

    // print enrol info
    if ($texts = enrol_get_course_description_texts($course)) {
        echo $OUTPUT->box_start('generalbox icons');
        echo implode($texts);
        echo $OUTPUT->box_end();
    }

    $courserenderer = $PAGE->get_renderer('core', 'course');
    echo $courserenderer->course_info_box($course);

    echo "<br />";

    echo $OUTPUT->footer();


