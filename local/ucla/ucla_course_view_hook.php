<?php

defined('MOODLE_INTERNAL') || die;

// This essentially loads ucla library for all course sites
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 *  Will redirect a user to corresponding course on archive server.
 **/ 
function ucla_course_view_hook($shortname, $id) {
    global $CFG, $DB;

    // Save a lot of time
    if (!$shortname) {
        return false;
    }

    // No way to judge anything
    if (!isset($CFG->remotetermcutoff)) {
        return false;
    }

    $redirurl = $CFG->archiveserver .'/course/view/' . $shortname;
    $maybeterm = substr($shortname, 0, 3);

    // No term, just treat it like a non-ucla course
    if (!ucla_validator('term', $maybeterm)) {
        return false;
    }

    $termcmp = term_cmp_fn($maybeterm, $CFG->remotetermcutoff);
    if ($termcmp == -1) {
        // Then we goto 1.9 server for older terms
        return $redirurl;
    }

    $reginfo = false;

    if (!$id) {
        $course = $DB->get_record('course', array('shortname' => $shortname));
        if ($course) {
            $id = $course->id;
            $reginfo = ucla_map_courseid_to_termsrses($id);
        }   
    }

    // Regular course, belongs to THIS server, requested on THIS
    // server
    if (!$reginfo && $termcmp == 0) {
        // Then we goto 1.9 server for older terms
        return $redirurl;
    }

    // Normal or config site
    return false;
}
