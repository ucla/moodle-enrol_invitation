<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Responds to the mod_created/mod_updated to make sure the course module's 
 * visiblity is the same as its parent section.
 * 
 * Fixes bug CCLE-3556 - Hiding section doesn't hide material in section
 * 
 * @param object $mod
 */
function check_mod_parent_visiblity($mod) {
    global $DB;
    // needs to be fast, so just do everything in one database query
    $sql = "UPDATE  {course_modules} AS cm
            INNER JOIN  {course_sections} as cs ON (cm.section=cs.id)
            SET     cm.visible=cs.visible
            WHERE   cm.id=:cmid";
    $DB->execute($sql, array('cmid' => $mod->cmid));
    
    // something might have change, so clear cache
    rebuild_course_cache($mod->courseid, true);
}

function ucla_sync_built_courses($edata) {
    require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
    
    // This hopefully means that this plugin IS enabled
    $enrol = enrol_get_plugin('database');
    if (empty($enrol)) {
        debugging('Database enrolment plugin is not installed');
        return false;
    }
    
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
        // This will handle auto-groups
        $enrol->sync_enrolments($verbose, null, $courseid);
    }
    
    return true;
}
