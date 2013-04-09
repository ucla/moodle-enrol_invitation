<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');

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
 * Determines if, given current week, whether or not to hide the past term's 
 * courses. Uses local_ucla|student_access_ends_week to determine if courses
 * should be hidden.
 *
 * If local_ucla|student_access_ends_week is 0, not set, or not equal to
 * $weeknum then will do nothing.
 *
 * If local_ucla|student_access_ends_week is equal to $weeknum, then will hide
 * the previous term's courses.
 *
 * Responds to the ucla_weeksdisplay_changed event.
 *
 * @param int $weeknum
 */
function hide_past_courses($weeknum) {
    global $CFG, $DB;
    $config_week = get_config('local_ucla', 'student_access_ends_week');

    // If local_ucla|student_access_ends_week is 0, not set, or not equal to
    // $weeknum then will do nothing.
    if (empty($config_week) || $config_week != $weeknum) {
        return true;
    }

    // If local_ucla|student_access_ends_week is equal to $weeknum, then will 
    // hide the previous term's courses.
    if (empty($CFG->currentterm)) {
        // For some reason, currentterm is empty, just exit.
        return true;
    }

    $past_term = term_get_prev($CFG->currentterm);
    if (!ucla_validator('term', $past_term)) {
        // Strange, cannot figure out past_term, just exit.
        return true;
    }

    list($num_hidden_courses, $num_hidden_tasites, $num_problem_courses,
            $error_messages) = hide_courses($past_term);

    // Finished hiding courses, notify admins.
    $to = get_config('local_ucla', 'admin_email');
    if (empty($to)) {
        // Did not have admin contact setup, just exit.
        return true;
    }

    $subj = 'Hiding courses for ' . $past_term;
    $body = sprintf("Hid %d courses.\n\n", $num_hidden_courses);
    $body .= sprintf("Hid %d TA sites.\n\n", $num_hidden_tasites);
    $body .= sprintf("Had %d problem courses.\n\n", $num_problem_courses);
    $body .= $error_messages;
    ucla_send_mail($to, $subj, $body);
    
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
