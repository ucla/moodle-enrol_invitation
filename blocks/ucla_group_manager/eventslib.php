<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');

function ucla_group_manager_sync_course_event($edata) {
    // Extract out what enrolments got updates
    if (empty($edata->courses)) {
        return true;
    }

    foreach ($edata->courses as $courseid => $course_set) {
        ucla_group_manager::sync_course($courseid);
    }
}
