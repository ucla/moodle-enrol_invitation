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

/**
 * Deletes a given user's repo keys or all users whose lastaccess is passed a 
 * given time interval.
 * 
 * @param object $param     If called with parameter, it is most likely because
 *                          it was called via the events api. Should contain
 *                          a variable called "userid".
 */
function delete_repo_keys($param = null) {
    global $DB;
    $REPO_TIMEOUT_INTERVAL = 300;   // 5 minutes
    $repo_keys = array('dropbox__access_key', 'dropbox__access_secret', 
        'dropbox__request_secret', 'boxnet__auth_token');
    if (isset($param) && isset($param->userid)) {
        $param = $param->userid;
    } else if (isset($param)) {
        debugging('$param passed without userid set');
        return false;   // exit out early if called in appropiately
    }
    
    list($repo_where, $repo_params) = $DB->get_in_or_equal($repo_keys, SQL_PARAMS_NAMED, 'repo_key');
    if (!is_null($param)) {
        // delete repo keys for user
        $repo_params['userid'] = $param;        
        $DB->delete_records_select('user_preferences', "name $repo_where AND " . 
                "userid=:userid", $repo_params);
    } else {
        // delete ONLY the repo keys
        $repo_params['timelimit'] = $REPO_TIMEOUT_INTERVAL;
        $where = "userid IN (
                    SELECT  id
                    FROM    {user}
                    WHERE   lastaccess<=UNIX_TIMESTAMP()-:timelimit
                )";
        $DB->delete_records_select('user_preferences', "$where AND name $repo_where", $repo_params);
    }
    
    return true;
}

/**
 * Created because cannot declare more than once function handler per event.
 * 
 * @param object $eventdata
 */
function local_ucla_handle_mod($eventdata) {
    check_mod_parent_visiblity($eventdata);
    delete_repo_keys($eventdata);
}

function ucla_sync_built_courses($edata) {
    global $CFG;
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
