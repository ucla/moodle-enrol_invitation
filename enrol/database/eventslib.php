<?php

defined('MOODLE_INTERNAL') || die();

function ucla_sync_built_courses($edata) {
    // This hopefully means that this plugin IS enabled
    $enrol = enrol_get_plugin('database');
    $verbose = debugging();

    $courseidsenrol = array();

    foreach ($edata->completed_requests as $key => $request) {
        if (empty($request->courseid)) {
            continue;
        }

        $courseidsenrol[$request->courseid] = true;
    }

    foreach ($courseidsenrol as $courseid => $na) {
        // Not sure where to log errors...
        $enrol->sync_enrolments($verbose, null, $courseid);
    }
}
