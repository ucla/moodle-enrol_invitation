<?php

defined('MOODLE_INTERNAL') || die();

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

function course_restored_enrol_check($data) {
    global $DB;

   // only respond to course restores
   if ($data->type != backup::TYPE_1COURSE) {
       return true;
   }
    
    $record = $DB->get_record('enrol', array('enrol' => 'database', 
        'courseid' => $data->courseid, 'status' => ENROL_INSTANCE_DISABLED));
    
    if(!empty($record)) {
        ucla_reg_enrolment_plugin_cron::update_plugin($courseid, $record->id);
    }
}
