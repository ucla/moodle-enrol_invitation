<?php

defined('MOODLE_INTERNAL') || die;

// This essentially loads ucla library for all course sites
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 *  Will redirect a user to corresponding course on archive server.
 **/ 
function local_ucla_course_view_hook($shortname, $id) {
    global $DB;

    // Save a lot of time
    if (!$shortname) {
        return false;
    }

    // No way to judge anything
    $remotetermcutoff = get_config('local_ucla', 'remotetermcutoff');
    if (!$remotetermcutoff) {
        return false;
    }

    $archiveserver = get_config('local_ucla', 'archiveserver');
    if (!$archiveserver) {
        return false;
    }

    $redirurl = $archiveserver .'/course/view/' . $shortname;
    $maybeterm = substr($shortname, 0, 3);

    // No term, just treat it like a non-ucla course
    if (!ucla_validator('term', $maybeterm)) {
        return false;
    }

    $termcmp = term_cmp_fn($maybeterm, $remotetermcutoff);
    $reginfo = false;

    // Do not check for any courses after the specified term
    if ($termcmp > 0) {
        return false;
    }

    if (!$id) {
        $course = $DB->get_record('course', array('shortname' => $shortname));
        if ($course) {
            $id = $course->id;
        } 
    }

    // This course doesn't exist on this local server
    if (empty($id) || !ucla_map_courseid_to_termsrses($id)) {
        // Then we goto 1.9 server for older terms
        return $redirurl;
    } 

    return false;
}
