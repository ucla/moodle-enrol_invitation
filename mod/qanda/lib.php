<?php

/**
 * Library of functions and constants for module qanda
 * (replace qanda with the name of your module and delete this line)
 *
 * @package   mod-qanda
 */
require_once($CFG->libdir . '/completionlib.php');

//define("QANDA_SHOW_ALL_CATEGORIES", 0);
//define("QANDA_SHOW_NOT_CATEGORISED", -1);

define("QANDA_NO_VIEW", -1);
define("QANDA_STANDARD_VIEW", 0);
//define("qanda_CATEGORY_VIEW", 1);
define("QANDA_DATE_VIEW", 2);
//define("qanda_AUTHOR_VIEW", 3);
define("QANDA_ADDENTRY_VIEW", 4);
define("QANDA_IMPORT_VIEW", 5);
define("QANDA_EXPORT_VIEW", 6);
define("QANDA_APPROVAL_VIEW", 7);

/// STANDARD FUNCTIONS ///////////////////////////////////////////////////////////
/**
 * @global object
 * @param object $qanda
 * @return int
 */
function qanda_add_instance($qanda) {
    global $DB;
/// Given an object containing all the necessary data,
/// (defined by the form in mod_form.php) this function
/// will create a new instance and return the id number
/// of the new instance.

    if (empty($qanda->ratingtime) or empty($qanda->assessed)) {
        $qanda->assesstimestart = 0;
        $qanda->assesstimefinish = 0;
    }

    if (empty($qanda->globalqanda)) {
        $qanda->globalqanda = 0;
    }

    if (!has_capability('mod/qanda:manageentries', context_system::instance())) {
        $qanda->globalqanda = 0;
    }

    $qanda->timecreated = time();
    $qanda->timemodified = $qanda->timecreated;

    //Check displayformat is a valid one
    $formats = get_list_of_plugins('mod/qanda/formats', 'TEMPLATE');
    if (!in_array($qanda->displayformat, $formats)) {
        print_error('unknowformat', '', '', $qanda->displayformat);
    }

    $returnid = $DB->insert_record("qanda", $qanda);
    $qanda->id = $returnid;
//    qanda_grade_item_update($qanda);

    return $returnid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @global object
 * @param object $qanda
 * @return bool
 */
function qanda_update_instance($qanda) {
    global $CFG, $DB;

    if (empty($qanda->globalqanda)) {
        $qanda->globalqanda = 0;
    }

    if (!has_capability('mod/qanda:manageentries', context_system::instance())) {
        // keep previous
        unset($qanda->globalqanda);
    }

    $qanda->timemodified = time();
    $qanda->id = $qanda->instance;

    if (empty($qanda->ratingtime) or empty($qanda->assessed)) {
        $qanda->assesstimestart = 0;
        $qanda->assesstimefinish = 0;
    }

    //Check displayformat is a valid one
    $formats = get_list_of_plugins('mod/qanda/formats', 'TEMPLATE');
    if (!in_array($qanda->displayformat, $formats)) {
        print_error('unknowformat', '', '', $qanda->displayformat);
    }

    $DB->update_record("qanda", $qanda);
    if ($qanda->defaultapproval) {
        $DB->execute("UPDATE {qanda_entries} SET approved = 1 where approved <> 1 and qandaid = ?", array($qanda->id));
    }
//    qanda_grade_item_update($qanda);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id qanda id
 * @return bool success
 */
function qanda_delete_instance($id) {
    global $DB, $CFG;

    if (!$qanda = $DB->get_record('qanda', array('id' => $id))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('qanda', $id)) {
        return false;
    }

    if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
        return false;
    }

    $fs = get_file_storage();

    if ($qanda->mainqanda) {
        // unexport entries
        $sql = "SELECT ge.id, ge.sourceqandaid, cm.id AS sourcecmid
                  FROM {qanda_entries} ge
                  JOIN {modules} m ON m.name = 'qanda'
                  JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = ge.sourceqandaid)
                 WHERE ge.qandaid = ? AND ge.sourceqandaid > 0";

        if ($exported = $DB->get_records_sql($sql, array($id))) {
            foreach ($exported as $entry) {
                $entry->qandaid = $entry->sourceqandaid;
                $entry->sourceqandaid = 0;
                $newcontext = context_module::instance($entry->sourcecmid);
                if ($oldfiles = $fs->get_area_files($context->id, 'mod_qanda', 'attachment', $entry->id)) {
                    foreach ($oldfiles as $oldfile) {
                        $file_record = new stdClass();
                        $file_record->contextid = $newcontext->id;
                        $fs->create_file_from_storedfile($file_record, $oldfile);
                    }
                    $fs->delete_area_files($context->id, 'mod_qanda', 'attachment', $entry->id);
                    $entry->attachment = '1';
                } else {
                    $entry->attachment = '0';
                }
                $DB->update_record('qanda_entries', $entry);
            }
        }
    } else {
        // move exported entries to main qanda
        $sql = "UPDATE {qanda_entries}
                   SET sourceqandaid = 0
                 WHERE sourceqandaid = ?";
        $DB->execute($sql, array($id));
    }

    // Delete any dependent records
    $entry_select = "SELECT id FROM {qanda_entries} WHERE qandaid = ?";
    $DB->delete_records_select('comments', "contextid=? AND commentarea=? AND itemid IN ($entry_select)", array($id, 'qanda_entry', $context->id));
    $DB->delete_records_select('qanda_alias', "entryid IN ($entry_select)", array($id));

    $category_select = "SELECT id FROM {qanda_categories} WHERE qandaid = ?";
    $DB->delete_records_select('qanda_entries_categories', "categoryid IN ($category_select)", array($id));
    $DB->delete_records('qanda_categories', array('qandaid' => $id));
    $DB->delete_records('qanda_entries', array('qandaid' => $id));

    // delete all files
    $fs->delete_area_files($context->id);

    // qanda_grade_item_delete($qanda);

    return $DB->delete_records('qanda', array('id' => $id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $qanda
 * @return object|null
 */
function qanda_user_outline($course, $user, $mod, $qanda) {
    global $CFG;

    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'qanda', $qanda->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    if ($entries = qanda_get_user_entries($qanda->id, $user->id)) {
        $result = new stdClass();
        $result->info = count($entries) . ' ' . get_string("entries", "qanda");

        $lastentry = array_pop($entries);
        $result->time = $lastentry->timemodified;

        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new stdClass();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;

        //datesubmitted == time created. dategraded == time modified or time overridden
        //if grade was last modified by the user themselves use date graded. Otherwise use date submitted
        //TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }

        return $result;
    }
    return NULL;
}

/**
 * @global object
 * @param int $qandaid
 * @param int $userid
 * @return array
 */
function qanda_get_user_entries($qandaid, $userid) {
/// Get all the entries for a user in a qanda
    global $DB;

    return $DB->get_records_sql("SELECT e.*, u.firstname, u.lastname, u.email, u.picture
                                   FROM {qanda} g, {qanda_entries} e, {user} u
                             WHERE g.id = ?
                               AND e.qandaid = g.id
                               AND e.userid = ?
                               AND e.userid = u.id
                          ORDER BY e.timemodified ASC", array($qandaid, $userid));
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $qanda
 */
function qanda_user_complete($course, $user, $mod, $qanda) {
    global $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'qanda', $qanda->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade') . ': ' . $grade->str_long_grade);
        if ($grade->str_feedback) {
            echo $OUTPUT->container(get_string('feedback') . ': ' . $grade->str_feedback);
        }
    }

    if ($entries = qanda_get_user_entries($qanda->id, $user->id)) {
        echo '<table width="95%" border="0"><tr><td>';
        foreach ($entries as $entry) {
            $cm = get_coursemodule_from_instance("qanda", $qanda->id, $course->id);
            qanda_print_entry($course, $cm, $qanda, $entry, "", "", 0);
            echo '<p>';
        }
        echo '</td></tr></table>';
    }
}

/**
 * Returns all qanda entries since a given time for specified qanda
 *
 * @param array $activities sequentially indexed array of objects
 * @param int   $index
 * @param int   $timestart
 * @param int   $courseid
 * @param int   $cmid
 * @param int   $userid defaults to 0
 * @param int   $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function qanda_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);
    $cm = $modinfo->cms[$cmid];
    $context = context_module::instance($cm->id);

    if (!has_capability('mod/qanda:view', $context)) {
        return;
    }

    $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $context);
    $groupmode = groups_get_activity_groupmode($cm, $course);

    $params['timestart'] = $timestart;

    if ($userid) {
        $userselect = "AND u.id = :userid";
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin = '';
    }

    $params['timestart'] = $timestart;
    $params['qandaid'] = $cm->instance;

    $ufields = user_picture::fields('u', array('lastaccess', 'firstname', 'lastname', 'email', 'picture', 'imagealt'));
    $entries = $DB->get_records_sql("
              SELECT ge.id AS entryid, ge.*, $ufields
                FROM {qanda_entries} ge
                JOIN {user} u ON u.id = ge.userid
                     $groupjoin
               WHERE ge.timemodified > :timestart
                 AND ge.qandaid = :qandaid
                     $userselect
                     $groupselect
            ORDER BY ge.timemodified ASC", $params);

    if (!$entries) {
        return;
    }

    foreach ($entries as $entry) {
        $usersgroups = null;
        if ($entry->userid != $USER->id) {
            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                if (is_null($usersgroups)) {
                    $usersgroups = groups_get_all_groups($course->id, $entry->userid, $cm->groupingid);
                    if (is_array($usersgroups)) {
                        $usersgroups = array_keys($usersgroups);
                    } else {
                        $usersgroups = array();
                    }
                }
                if (!array_intersect($usersgroups, $modinfo->get_groups($cm->id))) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();
        $tmpactivity->type = 'qanda';
        $tmpactivity->cmid = $cm->id;
        $tmpactivity->qandaid = $entry->qandaid;
        $tmpactivity->name = format_string($cm->name, true);
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $entry->timemodified;
        $tmpactivity->content = new stdClass();
        $tmpactivity->content->entryid = $entry->entryid;
        $tmpactivity->content->question = $entry->question;
        $tmpactivity->content->answer = $entry->answer;
        $tmpactivity->user = new stdClass();
        $tmpactivity->user->id = $entry->userid;
        $tmpactivity->user->firstname = $entry->firstname;
        $tmpactivity->user->lastname = $entry->lastname;
        $tmpactivity->user->fullname = fullname($entry, $viewfullnames);
        $tmpactivity->user->picture = $entry->picture;
        $tmpactivity->user->imagealt = $entry->imagealt;
        $tmpactivity->user->email = $entry->email;

        $activities[$index++] = $tmpactivity;
    }

    return true;
}

/**
 * Outputs the qanda entry indicated by $activity
 *
 * @param object $activity      the activity object the qanda resides in
 * @param int    $courseid      the id of the course the qanda resides in
 * @param bool   $detail        not used, but required for compatibilty with other modules
 * @param int    $modnames      not used, but required for compatibilty with other modules
 * @param bool   $viewfullnames not used, but required for compatibilty with other modules
 * @return void
 */
function qanda_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $OUTPUT;

    echo html_writer::start_tag('div', array('class' => 'qanda-activity clearfix'));
    if (!empty($activity->user)) {
        echo html_writer::tag('div', $OUTPUT->user_picture($activity->user, array('courseid' => $courseid)), array('class' => 'qanda-activity-picture'));
    }

    echo html_writer::start_tag('div', array('class' => 'qanda-activity-content'));
    echo html_writer::start_tag('div', array('class' => 'qanda-activity-entry'));

    $urlparams = array('g' => $activity->qandaid, 'mode' => 'entry', 'hook' => $activity->content->entryid);
    echo html_writer::tag('a', strip_tags($activity->content->question), array('href' => new moodle_url('/mod/qanda/view.php', $urlparams)));
    echo html_writer::end_tag('div');

    $url = new moodle_url('/user/view.php', array('course' => $courseid, 'id' => $activity->user->id));
    $name = $activity->user->fullname;
    $link = html_writer::link($url, $name);

    echo html_writer::start_tag('div', array('class' => 'user'));
    echo $link . ' - ' . userdate($activity->timestamp);
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');
    return;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in qanda activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @global object
 * @global object
 * @global object
 * @param object $course
 * @param object $viewfullnames
 * @param int $timestart
 * @return bool
 */
function qanda_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT, $PAGE;

    //TODO: use timestamp in approved field instead of changing timemodified when approving in 2.0
    if (!defined('qanda_RECENT_ACTIVITY_LIMIT')) {
        define('qanda_RECENT_ACTIVITY_LIMIT', 50);
    }
    $modinfo = get_fast_modinfo($course);
    $ids = array();

    foreach ($modinfo->cms as $cm) {
        if ($cm->modname != 'qanda') {
            continue;
        }
        if (!$cm->uservisible) {
            continue;
        }
        $ids[$cm->instance] = $cm->id;
    }

    if (!$ids) {
        return false;
    }

    // generate list of approval capabilities for all qandas in the course.
    $approvals = array();
    foreach ($ids as $glinstanceid => $glcmid) {
        $context = context_module::instance($glcmid);
        if (has_capability('mod/qanda:view', $context)) {
            // get records qanda entries that are approved if user has no capability to approve entries.
            if (has_capability('mod/qanda:answer', $context)) {
                $approvals[] = ' ge.qandaid = :glsid' . $glinstanceid . ' ';
            } else {
                $approvals[] = ' (ge.approved = 1 AND ge.qandaid = :glsid' . $glinstanceid . ') ';
            }
            $params['glsid' . $glinstanceid] = $glinstanceid;
        }
    }

    if (count($approvals) == 0) {
        return false;
    }
    $selectsql = 'SELECT ge.id, ge.question, ge.approved, ge.timemodified, ge.qandaid,
                                        ' . user_picture::fields('u', null, 'userid');
    $countsql = 'SELECT COUNT(*)';

    $joins = array(' FROM {qanda_entries} ge ');
    $joins[] = 'JOIN {user} u ON u.id = ge.userid ';
    $fromsql = implode($joins, "\n");

    $params['timestart'] = $timestart;
    $clausesql = ' WHERE ge.timemodified > :timestart ';

    if (count($approvals) > 0) {
        $approvalsql = 'AND (' . implode($approvals, ' OR ') . ') ';
    } else {
        $approvalsql = '';
    }
    $ordersql = 'ORDER BY ge.timemodified ASC';
    $entries = $DB->get_records_sql($selectsql . $fromsql . $clausesql . $approvalsql . $ordersql, $params, 0, (qanda_RECENT_ACTIVITY_LIMIT + 1));

    if (empty($entries)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newentries', 'qanda') . ':', 3);
    $strftimerecent = get_string('strftimerecent');
    $entrycount = 0;
    foreach ($entries as $entry) {
        if ($entrycount < qanda_RECENT_ACTIVITY_LIMIT) {
            if ($entry->approved) {
                $dimmed = '';
                $urlparams = array('g' => $entry->qandaid, 'mode' => 'entry', 'hook' => $entry->id);
            } else {
                $dimmed = ' dimmed_text';
                $urlparams = array('id' => $ids[$entry->qandaid], 'mode' => 'approval', 'hook' => format_text($entry->question, true));
            }
            $link = new moodle_url($CFG->wwwroot . '/mod/qanda/view.php', $urlparams);
            echo '<div class="head' . $dimmed . '">';
            echo '<div class="date">' . userdate($entry->timemodified, $strftimerecent) . '</div>';
            echo '<div class="name">' . fullname($entry, $viewfullnames) . '</div>';
            echo '</div>';
            echo '<div class="info"><a href="' . $link . '">' . format_string($entry->question, true) . '</a></div>';
            $entrycount += 1;
        } else {
            $numnewentries = $DB->count_records_sql($countsql . $joins[0] . $clausesql . $approvalsql, $params);
            echo '<div class="head"><div class="activityhead">' . get_string('andmorenewentries', 'qanda', $numnewentries - qanda_RECENT_ACTIVITY_LIMIT) . '</div></div>';
            break;
        }
    }

    return true;
}

/**
 * @global object
 * @param object $log
 */
function qanda_log_info($log) {
    global $DB;

    return $DB->get_record_sql("SELECT e.*, u.firstname, u.lastname
                                  FROM {qanda_entries} e, {user} u
                                 WHERE e.id = ? AND u.id = ?", array($log->info, $log->userid));
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @return bool
 */
function qanda_cron() {
    return true;
}

/**
 * This function return an array of valid qanda_formats records
 * Everytime it's called, every existing format is checked, new formats
 * are included if detected and old formats are deleted and any qanda
 * using an invalid format is updated to the default (dictionary).
 *
 * @global object
 * @global object
 * @return array
 */
function qanda_get_available_formats() {
    global $CFG, $DB;

    //Get available formats (plugin) and insert (if necessary) them into qanda_formats
    $formats = get_list_of_plugins('mod/qanda/formats', 'TEMPLATE');
    $pluginformats = array();
    foreach ($formats as $format) {
        //If the format file exists
        if (file_exists($CFG->dirroot . '/mod/qanda/formats/' . $format . '/' . $format . '_format.php')) {
            include_once($CFG->dirroot . '/mod/qanda/formats/' . $format . '/' . $format . '_format.php');
            //If the function exists
            if (function_exists('qanda_show_entry_' . $format)) {
                //Acummulate it as a valid format
                $pluginformats[] = $format;
                //If the format doesn't exist in the table
                if (!$rec = $DB->get_record('qanda_formats', array('name' => $format))) {
                    //Insert the record in qanda_formats
                    $gf = new stdClass();
                    $gf->name = $format;
                    $gf->popupformatname = $format;
                    $gf->visible = 1;
                    $DB->insert_record("qanda_formats", $gf);
                }
            }
        }
    }

    //Delete non_existent formats from qanda_formats table
    $formats = $DB->get_records("qanda_formats");
    foreach ($formats as $format) {
        $todelete = false;
        //If the format in DB isn't a valid previously detected format then delete the record
        if (!in_array($format->name, $pluginformats)) {
            $todelete = true;
        }

        if ($todelete) {
            //Delete the format
            $DB->delete_records('qanda_formats', array('name' => $format->name));
            //Reasign existing qandas to default (dictionary) format
            if ($qandas = $DB->get_records('qanda', array('displayformat' => $format->name))) {
                foreach ($qandas as $qanda) {
                    $DB->set_field('qanda', 'displayformat', 'dictionary', array('id' => $qanda->id));
                }
            }
        }
    }

    //Now everything is ready in qanda_formats table
    $formats = $DB->get_records("qanda_formats");

    return $formats;
}

/**
 * @param bool $debug
 * @param string $text
 * @param int $br
 */
function qanda_debug($debug, $text, $br = 1) {
    if ($debug) {
        echo '<font color="red">' . $text . '</font>';
        if ($br) {
            echo '<br />';
        }
    }
}

/**
 *
 * @global object
 * @param int $qandaid
 * @param string $entrylist
 * @param string $pivot
 * @return array
 */
function qanda_get_entries($qandaid, $entrylist, $pivot = "") {
    global $DB;
    if ($pivot) {
        $pivot .= ",";
    }

    return $DB->get_records_sql("SELECT $pivot id,userid,question,answer,format
                                   FROM {qanda_entries}
                                  WHERE qandaid = ?
                                        AND id IN ($entrylist)", array($qandaid));
}

/**
 * @global object
 * @global object
 * @param object $question
 * @param string $courseid
 * @return array
 */
function qanda_get_entries_search($question, $courseid) {
    global $CFG, $DB;

    //Check if the user is an admin
    $bypassadmin = 1; //This means NO (by default)
    if (has_capability('moodle/course:viewhiddenactivities', context_system::instance())) {
        $bypassadmin = 0; //This means YES
    }

    //Check if the user is a teacher
    $bypassteacher = 1; //This means NO (by default)
    if (has_capability('mod/qanda:manageentries', context_course::instance($courseid))) {
        $bypassteacher = 0; //This means YES
    }

    $questionlower = textlib::strtolower(trim($question));

    $params = array('courseid1' => $courseid, 'courseid2' => $courseid, 'questionlower' => $questionlower, 'question' => $question);

    return $DB->get_records_sql("SELECT e.*, g.name as qandaname, cm.id as cmid, cm.course as courseid
                                   FROM {qanda_entries} e, {qanda} g,
                                        {course_modules} cm, {modules} m
                                  WHERE m.name = 'qanda' AND
                                        cm.module = m.id AND
                                        (cm.visible = 1 OR  cm.visible = $bypassadmin OR
                                            (cm.course = :courseid1 AND cm.visible = $bypassteacher)) AND
                                        g.id = cm.instance AND
                                        e.qandaid = g.id  AND
                                        ( (e.casesensitive != 0 AND LOWER(question) = :questionlower) OR
                                          (e.casesensitive = 0 and question = :question)) AND
                                        (g.course = :courseid2 OR g.globalqanda = 1) AND
                                         e.usedynalink != 0 AND
                                         g.usedynalink != 0", $params);
}

/**
 * @global object
 * @global object
 * @param object $course
 * @param object $course
 * @param object $qanda
 * @param object $entry
 * @param string $mode
 * @param string $hook
 * @param int $printicons
 * @param int $displayformat
 * @param bool $printview
 * @return mixed
 */
function qanda_print_entry($course, $cm, $qanda, $entry, $mode = '', $hook = '', $printicons = 1, $displayformat = -1, $printview = false) {
    global $USER, $CFG;
    $return = false;
    if ($displayformat < 0) {
        $displayformat = $qanda->displayformat;
    }
    if ($entry->approved or ($USER->id == $entry->userid) or ($mode == 'approval' and !$entry->approved)) {
        $formatfile = $CFG->dirroot . '/mod/qanda/formats/' . $displayformat . '/' . $displayformat . '_format.php';
        if ($printview) {
            $functionname = 'qanda_print_entry_' . $displayformat;
        } else {
            $functionname = 'qanda_show_entry_' . $displayformat;
        }

        if (file_exists($formatfile)) {
            include_once($formatfile);
            if (function_exists($functionname)) {
                $return = $functionname($course, $cm, $qanda, $entry, $mode, $hook, $printicons);
            } else if ($printview) {
                //If the qanda_print_entry_XXXX function doesn't exist, print default (old) print format
                $return = qanda_print_entry_default($entry, $qanda, $cm);
            }
        }
    }
    return $return;
}

/**
 * Default (old) print format used if custom function doesn't exist in format
 *
 * @param object $entry
 * @param object $qanda
 * @param object $cm
 * @return void Output is echo'd
 */
function qanda_print_entry_default($entry, $qanda, $cm) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');
    $context = context_module::instance($cm->id);
    $question = $entry->question;
    $question = file_rewrite_pluginfile_urls($question, 'pluginfile.php', $context->id, 'mod_qanda', 'question', $entry->id);
    $answer = $entry->answer;
    $answer = file_rewrite_pluginfile_urls($answer, 'pluginfile.php', $context->id, 'mod_qanda', 'answer', $entry->id);


    $question = preg_replace('/^<p[^>]*>(.*?)<\/p>$/i', '$1', $question); //Remove outer <p></p>
    $text = html_writer::empty_tag('br');
    $text.=html_writer::empty_tag('br');

    $text.=html_writer::tag('h3', $entry->entrycount . '. ' . format_text($question, FORMAT_HTML));

    $answer = preg_replace('/^<p[^>]*>(.*?)<\/p>$/i', '$1', $answer); //Remove outer <p></p>

    $text.=html_writer::tag('span', format_text($answer, FORMAT_HTML), array('class' => 'nolink'));

    echo ($text);
}

/**
 * Print qanda question/term as a heading &lt;h3>
 * @param object $entry
 */
//function qanda_print_entry_question($entry, $return = false) {
function qanda_print_entry_question($entry, $qanda, $cm, $return = false) {

    global $OUTPUT;

    //$text = html_writer::tag('h3', format_string($entry->question));
    //$text = html_writer::tag('title', format_text($entry->question, FORMAT_HTML));

    $question = $entry->question;
    $context = context_module::instance($cm->id);
    $question = file_rewrite_pluginfile_urls($question, 'pluginfile.php', $context->id, 'mod_qanda', 'question', $entry->id);
    /*
      $options = new stdClass();
      $options->para = false;
      $options->trusted = $entry->answertrust;
      $options->context = $context;
      $options->overflowdiv = true;

      $text = format_text($answer, $entry->answerformat, $options); */
    //$text = html_writer::tag('div', format_text($entry->question, FORMAT_HTML), array('class' => 'question-text'));
    $text = html_writer::tag('div', format_text($question, FORMAT_HTML), array('class' => 'question-text'));

    if (!empty($entry->highlight)) {
        $text = highlight($entry->highlight, $text);
    }

    if ($return) {
        return $text;
    } else {
        echo $text;
    }
}

/**
 *
 * @global moodle_database DB
 * @param object $entry
 * @param object $qanda
 * @param object $cm
 */
function qanda_print_entry_answer($entry, $qanda, $cm) {
    global $DB, $qanda_EXCLUDEQUESTIONS;

    $answer = $entry->answer;

    //Calculate all the strings to be no-linked
    //First, the question
    $qanda_EXCLUDEQUESTIONS = array($entry->question);
    //Now the aliases
    if ($aliases = $DB->get_records('qanda_alias', array('entryid' => $entry->id))) {
        foreach ($aliases as $alias) {
            $qanda_EXCLUDEQUESTIONS[] = trim($alias->alias);
        }
    }

    $context = context_module::instance($cm->id);
    $answer = file_rewrite_pluginfile_urls($answer, 'pluginfile.php', $context->id, 'mod_qanda', 'answer', $entry->id);

    $options = new stdClass();
    $options->para = false;
    $options->trusted = $entry->answertrust;
    $options->context = $context;
    $options->overflowdiv = true;

    $text = format_text($answer, $entry->answerformat, $options);

    // Stop excluding questions from autolinking
    unset($qanda_EXCLUDEQUESTIONS);

    if (!empty($entry->highlight)) {
        $text = highlight($entry->highlight, $text);
    }
    if (isset($entry->footer)) {   // Unparsed footer info
        $text .= $entry->footer;
    }
    echo $text;
}

/**
 *
 * @global object
 * @param object $course
 * @param object $cm
 * @param object $qanda
 * @param object $entry
 * @param string $mode
 * @param string $hook
 * @param string $type
 * @return string|void
 */
function qanda_print_entry_aliases($course, $cm, $qanda, $entry, $mode = '', $hook = '', $type = 'print') {
    global $DB;

    $return = '';
    if ($aliases = $DB->get_records('qanda_alias', array('entryid' => $entry->id))) {
        foreach ($aliases as $alias) {
            if (trim($alias->alias)) {
                if ($return == '') {
                    $return = '<select id="keyword" style="font-size:8pt">';
                }
                $return .= "<option>$alias->alias</option>";
            }
        }
        if ($return != '') {
            $return .= '</select>';
        }
    }
    if ($type == 'print') {
        echo $return;
    } else {
        return $return;
    }
}

/**
 *
 * @global object
 * @global object
 * @global object
 * @param object $course
 * @param object $cm
 * @param object $qanda
 * @param object $entry
 * @param string $mode
 * @param string $hook
 * @param string $type
 * @return string|void
 */
function qanda_print_entry_icons($course, $cm, $qanda, $entry, $mode = '', $hook = '', $type = 'print') {
    global $USER, $CFG, $DB, $OUTPUT;

    $context = context_module::instance($cm->id);

    $output = false;   //To decide if we must really return text in "return". Activate when needed only!
    $importedentry = ($entry->sourceqandaid == $qanda->id);
    $ismainqanda = $qanda->mainqanda;

    $return_alt = '<div class="manage_entry">';
    $return = '<span class="commands">';
    // Differentiate links for each entry.
    $altsuffix = ': ' . strip_tags(format_text($entry->question));

    if (!$entry->approved) {
        $output = true;
        $return .= html_writer::tag('span', get_string('entryishidden', 'qanda'), array('class' => 'qanda-hidden-note'));
    }

    $iscurrentuser = ($entry->userid == $USER->id);

    if (has_capability('mod/qanda:manageentries', $context) or (isloggedin() and has_capability('mod/qanda:write', $context) and $iscurrentuser and !$entry->approved)) {
        // only teachers can export entries so check it out
        if (has_capability('mod/qanda:export', $context) and !$ismainqanda and !$importedentry) {
            $mainqanda = $DB->get_record('qanda', array('mainqanda' => 1, 'course' => $course->id));
            if ($mainqanda) {  // if there is a main qanda defined, allow to export the current entry
                $output = true;
                $return .= '<a class="action-icon" title="' . get_string('exporttomainqanda', 'qanda') . '" href="exportentry.php?id=' . $entry->id . '&amp;prevmode=' . $mode . '&amp;hook=' . urlencode($hook) . '"><img src="' . $OUTPUT->pix_url('export', 'qanda') . '" class="smallicon" alt="' . get_string('exporttomainqanda', 'qanda') . $altsuffix . '" /></a>';
            }
        }

        if ($entry->sourceqandaid) {
            $icon = $OUTPUT->pix_url('minus', 'qanda');   // graphical metaphor (minus) for deleting an imported entry
        } else {
            $icon = $OUTPUT->pix_url('t/delete');
        }

        //Decide if an entry is editable:
        // -It isn't a imported entry (so nobody can edit a imported (from secondary to main) entry)) and
        // -The user is teacher or he is a student with time permissions (edit period or editalways defined).
        $ineditperiod = ((time() - $entry->timecreated < $CFG->maxeditingtime) || $qanda->editalways);
        if (!$importedentry and (has_capability('mod/qanda:manageentries', $context) or ($entry->userid == $USER->id and ($ineditperiod and has_capability('mod/qanda:write', $context))))) {
            $output = true;
            $return .= "<a class='action-icon' title=\"" . get_string("delete") . "\" href=\"deleteentry.php?id=$cm->id&amp;mode=delete&amp;entry=$entry->id&amp;prevmode=$mode&amp;hook=" . urlencode($hook) . "\"><img src=\"";
            $return .= $icon;
            $return .= "\" class=\"smallicon\" alt=\"" . get_string("delete") . $altsuffix . "\" /></a>";

            $return .= "<a class='action-icon' title=\"" . get_string("edit") . "\" href=\"edit.php?cmid=$cm->id&amp;id=$entry->id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "\"><img src=\"" . $OUTPUT->pix_url('t/edit') . "\" class=\"smallicon\" alt=\"" . get_string("edit") . $altsuffix . "\" /></a>";

            if ($mode == 'approval') {
                $return_alt .= "<a class='qanda-answer' title=\"" . get_string("answer") . "\" href=\"edit.php?cmid=$cm->id&amp;id=$entry->id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "\">" . get_string("answer") . "</a>";
            } else {
                $return_alt .= "<a class='qanda-edit' title=\"" . get_string("edit") . "\" href=\"edit.php?cmid=$cm->id&amp;id=$entry->id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "\">" . get_string("edit") . "</a>";
            }

            $return_alt .= "<a class='qanda-delete' title=\"" . get_string("delete") . "\" href=\"deleteentry.php?id=$cm->id&amp;mode=delete&amp;entry=$entry->id&amp;prevmode=$mode&amp;hook=" . urlencode($hook) . "\">" . get_string("delete") . "</a>";
           
        } elseif ($importedentry) {
            $return .= "<font size=\"-1\">" . get_string("exportedentry", "qanda") . "</font>";
        }
    }
    if (!empty($CFG->enableportfolios) && (has_capability('mod/qanda:exportentry', $context) || ($iscurrentuser && has_capability('mod/qanda:exportownentry', $context)))) {
        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('qanda_entry_portfolio_caller', array('id' => $cm->id, 'entryid' => $entry->id), 'mod_qanda');

        $filecontext = $context;
        if ($entry->sourceqandaid == $cm->instance) {
            if ($maincm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
                $filecontext = context_module::instance($maincm->id);
            }
        }
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($filecontext->id, 'mod_qanda', 'attachment', $entry->id, "timemodified", false) || $files = $fs->get_area_files($filecontext->id, 'mod_qanda', 'question', $entry->id, "timemodified", false) || $files = $fs->get_area_files($filecontext->id, 'mod_qanda', 'answer', $entry->id, "timemodified", false) || $files = $fs->get_area_files($filecontext->id, 'mod_qanda', 'entry', $entry->id, "timemodified", false)) {

            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        }

        $return .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
    }
    $return .= '</span>';
    $return_alt .= '</div>';
    /* Removing comment capability
      if (!empty($CFG->usecomments) && has_capability('mod/qanda:comment', $context) and $qanda->allowcomments) {
      require_once($CFG->dirroot . '/comment/lib.php');
      $cmt = new stdClass();
      $cmt->component = 'mod_qanda';
      $cmt->context = $context;
      $cmt->course = $course;
      $cmt->cm = $cm;
      $cmt->area = 'qanda_entry';
      $cmt->itemid = $entry->id;
      $cmt->showcount = true;
      $comment = new comment($cmt);
      $return .= '<div>' . $comment->output(true) . '</div>';
      $output = true;
      }
     */
    //If we haven't calculated any REAL thing, delete result ($return)
    if (!$output) {
        $return = '';
    }
    //Print or get
    if ($type == 'print') {
        echo $return_alt;
    } else {
        return $return_alt;
    }
}

/**
 * @param object $course
 * @param object $cm
 * @param object $qanda
 * @param object $entry
 * @param string $mode
 * @param object $hook
 * @param bool $printicons
 * @param bool $aliases
 * @return void
 */
function qanda_print_entry_lower_section($course, $cm, $qanda, $entry, $mode, $hook, $printicons, $aliases = true) {
    if ($aliases) {
        $aliases = qanda_print_entry_aliases($course, $cm, $qanda, $entry, $mode, $hook, 'html');
    }
    $icons = '';
    if ($printicons) {
        $icons = qanda_print_entry_icons($course, $cm, $qanda, $entry, $mode, $hook, 'html');
    }

    if ($aliases || $icons || !empty($entry->rating)) {
        echo '<table>';
        if ($aliases) {
            echo '<tr valign="top"><td class="aliases">' .
            '<label for="keyword">' . get_string('aliases', 'qanda') . ': </label>' .
            $aliases . '</td></tr>';
        }
        if ($icons) {
            echo '<tr valign="top"><td class="icons">' . $icons . '</td></tr>';
        }
        if (!empty($entry->rating)) {
            echo '<tr valign="top"><td class="ratings">';
            qanda_print_entry_ratings($course, $entry);
            echo '</td></tr>';
        }
        echo '</table>';
    }
}

/**
 * @todo Document this function
 */
function qanda_print_entry_attachment($entry, $cm, $format = NULL, $align = "right", $insidetable = true) {
///   valid format values: html  : Return the HTML link for the attachment as an icon
///                        text  : Return the HTML link for tha attachment as text
///                        blank : Print the output to the screen
    if ($entry->attachment) {
        if ($insidetable) {
            echo "<table border=\"0\" width=\"100%\" align=\"$align\"><tr><td align=\"$align\" nowrap=\"nowrap\">\n";
        }
        echo qanda_print_attachments($entry, $cm, $format, $align);
        if ($insidetable) {
            echo "</td></tr></table>\n";
        }
    }
}

/**
 * @global object
 * @param object $cm
 * @param object $entry
 * @param string $mode
 * @param string $align
 * @param bool $insidetable
 */
function qanda_print_entry_approval($cm, $entry, $mode, $align = "right", $insidetable = true) {
    global $CFG, $OUTPUT;

    if ($mode == 'approval' and !$entry->approved) {
        if ($insidetable) {
            echo '<table class="qanda-approval" align="' . $align . '"><tr><td align="' . $align . '">';
        }
        echo $OUTPUT->action_icon(
                new moodle_url('approve.php', array('eid' => $entry->id, 'mode' => $mode, 'sesskey' => sesskey())), new pix_icon('t/approve', get_string('approve', 'qanda'), '', array('class' => 'iconsmall', 'align' => $align))
        );
        if ($insidetable) {
            echo '</td></tr></table>';
        }
    }
}

/**
 * It returns all entries from all Q&A pair lists that matches the specified criteria
 *  within a given $course. It performs an $extended search if necessary.
 * It restrict the search to only one $qanda if the $qanda parameter is set.
 *
 * @global object
 * @global object
 * @param object $course
 * @param array $searchterms
 * @param int $extended
 * @param object $qanda
 * @return array
 */
function qanda_search($course, $searchterms, $extended = 0, $qanda = NULL) {
    global $CFG, $DB;

    if (!$qanda) {
        if ($qandas = $DB->get_records("qanda", array("course" => $course->id))) {
            $glos = "";
            foreach ($qandas as $qanda) {
                $glos .= "$qanda->id,";
            }
            $glos = substr($glos, 0, -1);
        }
    } else {
        $glos = $qanda->id;
    }

    if (!has_capability('mod/qanda:manageentries', context_course::instance($qanda->course))) {
        $qandamodule = $DB->get_record("modules", array("name" => "qanda"));
        $onlyvisible = " AND g.id = cm.instance AND cm.visible = 1 AND cm.module = $qandamodule->id";
        $onlyvisibletable = ", {course_modules} cm";
    } else {

        $onlyvisible = "";
        $onlyvisibletable = "";
    }

    if ($DB->sql_regex_supported()) {
        $REGEXP = $DB->sql_regex(true);
        $NOTREGEXP = $DB->sql_regex(false);
    }

    $searchcond = array();
    $params = array();
    $i = 0;

    $concat = $DB->sql_concat('e.question', "' '", 'e.answer');


    foreach ($searchterms as $searchterm) {
        $i++;

        $NOT = false; /// Initially we aren't going to perform NOT LIKE searches, only MSSQL and Oracle
        /// will use it to simulate the "-" operator with LIKE clause
        /// Under Oracle and MSSQL, trim the + and - operators and perform
        /// simpler LIKE (or NOT LIKE) queries
        if (!$DB->sql_regex_supported()) {
            if (substr($searchterm, 0, 1) == '-') {
                $NOT = true;
            }
            $searchterm = trim($searchterm, '+-');
        }

        // TODO: +- may not work for non latin languages

        if (substr($searchterm, 0, 1) == '+') {
            $searchterm = trim($searchterm, '+-');
            $searchterm = preg_quote($searchterm, '|');
            $searchcond[] = "$concat $REGEXP :ss$i";
            $params['ss' . $i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";
        } else if (substr($searchterm, 0, 1) == "-") {
            $searchterm = trim($searchterm, '+-');
            $searchterm = preg_quote($searchterm, '|');
            $searchcond[] = "$concat $NOTREGEXP :ss$i";
            $params['ss' . $i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";
        } else {
            $searchcond[] = $DB->sql_like($concat, ":ss$i", false, true, $NOT);
            $params['ss' . $i] = "%$searchterm%";
        }
    }

    if (empty($searchcond)) {
        $totalcount = 0;
        return array();
    }

    $searchcond = implode(" AND ", $searchcond);

    $sql = "SELECT e.*
              FROM {qanda_entries} e, {qanda} g $onlyvisibletable
             WHERE $searchcond
               AND (e.qandaid = g.id or e.sourceqandaid = g.id) $onlyvisible
               AND g.id IN ($glos) AND e.approved <> 0";

    return $DB->get_records_sql($sql, $params);
}

/**
 * @global object
 * @param array $searchterms
 * @param object $qanda
 * @param bool $extended
 * @return array
 */
function qanda_search_entries($searchterms, $qanda, $extended) {
    global $DB;

    $course = $DB->get_record("course", array("id" => $qanda->course));
    return qanda_search($course, $searchterms, $extended, $qanda);
}

/**
 * if return=html, then return a html string.
 * if return=text, then return a text-only string.
 * otherwise, print HTML for non-images, and return image HTML
 *     if attachment is an image, $align set its aligment.
 *
 * @global object
 * @global object
 * @param object $entry
 * @param object $cm
 * @param string $type html, txt, empty
 * @param string $align left or right
 * @return string image string or nothing depending on $type param
 */
function qanda_print_attachments($entry, $cm, $type = NULL, $align = "left") {
    global $CFG, $DB, $OUTPUT;

    if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
        return '';
    }

    if ($entry->sourceqandaid == $cm->instance) {
        if (!$maincm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
            return '';
        }
        $filecontext = context_module::instance($maincm->id);
    } else {
        $filecontext = $context;
    }

    $strattachment = get_string('attachment', 'qanda');

    $fs = get_file_storage();

    $imagereturn = '';
    $output = '';

    if ($files = $fs->get_area_files($filecontext->id, 'mod_qanda', 'attachment', $entry->id, "timemodified", false)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
            $path = file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $context->id . '/mod_qanda/attachment/' . $entry->id . '/' . $filename);

            if ($type == 'html') {
                $output .= "<a href=\"$path\">$iconimage</a> ";
                $output .= "<a href=\"$path\">" . s($filename) . "</a>";
                $output .= "<br />";
            } else if ($type == 'text') {
                $output .= "$strattachment " . s($filename) . ":\n$path\n";
            } else {
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
                    // Image attachments don't get printed as links
                    $imagereturn .= "<br /><img src=\"$path\" alt=\"\" />";
                } else {
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">" . s($filename) . "</a>", FORMAT_HTML, array('context' => $context));
                    $output .= '<br />';
                }
            }
        }
    }

    if ($type) {
        return $output;
    } else {
        echo $output;
        return $imagereturn;
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Lists all browsable file areas
 *
 * @package  mod_qanda
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function qanda_get_file_areas($course, $cm, $context) {
    return array(
        'attachment' => get_string('areaattachment', 'mod_qanda'),
        'entry' => get_string('areaentry', 'mod_qanda'),
        'question' => get_string('areaquestion', 'mod_qanda'),
        'answer' => get_string('areaanswer', 'mod_qanda'),
    );
}

/**
 * File browsing support for qanda module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info_stored file_info_stored instance or null if not found
 */
function qanda_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    if (!isset($areas[$filearea])) {
        return null;
    }

    if (is_null($itemid)) {
        require_once($CFG->dirroot . '/mod/qanda/locallib.php');
        return new qanda_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
    }

    if (!$entry = $DB->get_record('qanda_entries', array('id' => $itemid))) {
        return null;
    }

    if (!$qanda = $DB->get_record('qanda', array('id' => $cm->instance))) {
        return null;
    }

    if ($qanda->defaultapproval and !$entry->approved and !has_capability('mod/qanda:answer', $context)) {
        return null;
    }

    // this trickery here is because we need to support source qanda access
    if ($entry->qandaid == $cm->instance) {
        $filecontext = $context;
    } else if ($entry->sourceqandaid == $cm->instance) {
        if (!$maincm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
            return null;
        }
        $filecontext = context_module::instance($maincm->id);
    } else {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($filecontext->id, 'mod_qanda', $filearea, $itemid, $filepath, $filename))) {
        return null;
    }

    // Checks to see if the user can manage files or is the owner.
    // TODO MDL-33805 - Do not use userid here and move the capability check above.
    if (!has_capability('moodle/course:managefiles', $context) && $storedfile->get_userid() != $USER->id) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';

    return new file_info_stored($browser, $filecontext, $storedfile, $urlbase, s($entry->question), true, true, false, false);
}

/**
 * Serves the qanda attachments. Implements needed access control ;-)
 *
 * @package  mod_qanda
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClsss $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function qanda_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'attachment' or $filearea === 'entry' or $filearea === 'question' or $filearea === 'answer') {
        $entryid = (int) array_shift($args);

        require_course_login($course, true, $cm);

        if (!$entry = $DB->get_record('qanda_entries', array('id' => $entryid))) {
            return false;
        }

        if (!$qanda = $DB->get_record('qanda', array('id' => $cm->instance))) {
            return false;
        }

        if ($qanda->defaultapproval and !$entry->approved and !has_capability('mod/qanda:answer', $context)) {
            return false;
        }

        // this trickery here is because we need to support source qanda access

        if ($entry->qandaid == $cm->instance) {
            $filecontext = $context;
        } else if ($entry->sourceqandaid == $cm->instance) {
            if (!$maincm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
                return false;
            }
            $filecontext = context_module::instance($maincm->id);
        } else {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = "/$filecontext->id/mod_qanda/$filearea/$entryid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
    } else if ($filearea === 'export') {
        require_login($course, false, $cm);
        require_capability('mod/qanda:export', $context);

        if (!$qanda = $DB->get_record('qanda', array('id' => $cm->instance))) {
            return false;
        }

        $cat = array_shift($args);
        $cat = clean_param($cat, PARAM_ALPHANUM);

        $filename = clean_filename(strip_tags(format_string($qanda->name)) . '.xml');
        $content = qanda_generate_export_file($qanda, NULL, $cat, $cm);

        send_file($content, $filename, 0, 0, true, true);
    }

    return false;
}

/**
 *
 */
function qanda_print_tabbed_table_end() {
    echo "</div></div>";
}

/**
 * @param object $cm
 * @param object $qanda
 * @param string $mode
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function qanda_print_approval_menu($cm, $qanda, $mode, $hook, $sortkey = '', $sortorder = '') {
    if ($qanda->showalphabet) {
        echo '<div class="qandaexplain">' . get_string("explainalphabet", "qanda") . '</div><br />';
    }
    qanda_print_special_links($cm, $qanda, $mode, $hook);

    //qanda_print_alphabet_links($cm, $qanda, $mode, $hook, $sortkey, $sortorder);

    qanda_print_all_links($cm, $qanda, $mode, $hook);

    qanda_print_sorting_links($cm, $mode, 'CREATION', 'asc');
}

/**
 * @param object $cm
 * @param object $qanda
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function qanda_print_import_menu($cm, $qanda, $mode, $hook, $sortkey = '', $sortorder = '') {
    echo '<div class="qandaexplain">' . get_string("explainimport", "qanda") . '</div>';
}

/**
 * @param object $cm
 * @param object $qanda
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function qanda_print_export_menu($cm, $qanda, $mode, $hook, $sortkey = '', $sortorder = '') {
    echo '<div class="qandaexplain">' . get_string("explainexport", "qanda") . '</div>';
}

/**
 * @param object $cm
 * @param object $qanda
 * @param string $hook
 * @param string $sortkey
 * @param string $sortorder
 */
function qanda_print_alphabet_menu($cm, $qanda, $mode, $hook, $sortkey = '', $sortorder = '') {
    if ($mode != 'date') {
        if ($qanda->showalphabet) {
            //echo '<div class="qandaexplain">' . get_string("explainalphabet", "qanda") . '</div><br />';
            echo html_writer::nonempty_tag('div', get_string("explainalphabet", "qanda"), array('class' => 'qandaexplain'));
            echo html_writer::empty_tag('br');
        }

        qanda_print_special_links($cm, $qanda, $mode, $hook);

        qanda_print_alphabet_links($cm, $qanda, $mode, $hook, $sortkey, $sortorder);

        qanda_print_all_links($cm, $qanda, $mode, $hook);
    } else {
        qanda_print_sorting_links($cm, $mode, $sortkey, $sortorder);
    }
}

/**
 * @global object
 * @param object $cm
 * @param object $qanda
 * @param string $mode
 * @param string $hook
 */
function qanda_print_all_links($cm, $qanda, $mode, $hook) {
    global $CFG;
    if ($qanda->showall) {
        $strallentries = get_string("allentries", "qanda");
        if ($hook == 'ALL') {
            //echo "<b>$strallentries</b>";
            echo html_writer::nonempty_tag('b', $strallentries);
        } else {
            $strexplainall = strip_tags(get_string("explainall", "qanda"));
            //echo "<a title=\"$strexplainall\" href=\"$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;mode=$mode&amp;hook=ALL\">$strallentries</a>";
            $link = "$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;mode=$mode&amp;hook=ALL";
            echo html_writer::link($link, $strallentries, array('title' => $strexplainall));
        }
    }
}

/**
 * @global object
 * @param object $cm
 * @param object $qanda
 * @param string $mode
 * @param string $hook
 */
function qanda_print_special_links($cm, $qanda, $mode, $hook) {
    global $CFG;
    if ($qanda->showspecial) {
        $strspecial = get_string("special", "qanda");
        if ($hook == 'SPECIAL') {
            echo html_writer::nonempty_tag('b', $strspecial) . " | ";
        } else {
            $strexplainspecial = strip_tags(get_string("explainspecial", "qanda"));
            //echo "<a title=\"$strexplainspecial\" href=\"$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;mode=$mode&amp;hook=SPECIAL\">$strspecial</a> | ";
            $link = "$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;mode=$mode&amp;hook=SPECIAL";
            echo html_writer::link($link, $strexplainspecial, array('title' => $strexplainspecial));
        }
    }
}

/**
 * @global object
 * @param object $cm
 * @param string $mode
 * @param string $sortkey
 * @param string $sortorder
 */
function qanda_print_sorting_links($cm, $mode, $sortkey = '', $sortorder = '') {
    global $CFG, $OUTPUT;

    $asc = get_string("ascending", "qanda");
    $desc = get_string("descending", "qanda");
    $bopen = '<b>';
    $bclose = '</b>';

    $neworder = '';
    $currentorder = '';
    $currentsort = '';
    if ($sortorder) {
        if ($sortorder == 'asc') {
            $currentorder = $asc;
            $neworder = '&amp;sortorder=desc';
            $newordertitle = get_string('changeto', 'qanda', $desc);
        } else {
            $currentorder = $desc;
            $neworder = '&amp;sortorder=asc';
            $newordertitle = get_string('changeto', 'qanda', $asc);
        }
        $icon = " <img src=\"" . $OUTPUT->pix_url($sortorder, 'qanda') . "\" class=\"icon\" alt=\"$newordertitle\" />";
    } else {
        if ($sortkey != 'CREATION' and $sortkey != 'UPDATE' and
                $sortkey != 'FIRSTNAME' and $sortkey != 'LASTNAME') {
            $icon = "";
            $newordertitle = $asc;
        } else {
            $newordertitle = $desc;
            $neworder = '&amp;sortorder=desc';
            $icon = ' <img src="' . $OUTPUT->pix_url('asc', 'qanda') . '" class="icon" alt="' . $newordertitle . '" />';
        }
    }
    $ficon = '';
    $fneworder = '';
    $fbtag = '';
    $fendbtag = '';

    $sicon = '';
    $sneworder = '';

    $sbtag = '';
    $fbtag = '';
    $fendbtag = '';
    $sendbtag = '';

    $sendbtag = '';

    if ($sortkey == 'CREATION' or $sortkey == 'FIRSTNAME') {
        $ficon = $icon;
        $fneworder = $neworder;
        $fordertitle = $newordertitle;
        $sordertitle = $asc;
        $fbtag = $bopen;
        $fendbtag = $bclose;
    } elseif ($sortkey == 'UPDATE' or $sortkey == 'LASTNAME') {
        $sicon = $icon;
        $sneworder = $neworder;
        $fordertitle = $asc;
        $sordertitle = $newordertitle;
        $sbtag = $bopen;
        $sendbtag = $bclose;
    } else {
        $fordertitle = $asc;
        $sordertitle = $asc;
    }

    if ($sortkey == 'CREATION' or $sortkey == 'UPDATE') {
        $forder = 'CREATION';
        $sorder = 'UPDATE';
        $fsort = get_string("sortbycreation", "qanda");
        $ssort = get_string("sortbylastupdate", "qanda");

        $currentsort = $fsort;
        if ($sortkey == 'UPDATE') {
            $currentsort = $ssort;
        }
        $sort = get_string("sortchronogically", "qanda");
    } elseif ($sortkey == 'FIRSTNAME' or $sortkey == 'LASTNAME') {
        $forder = 'FIRSTNAME';
        $sorder = 'LASTNAME';
        $fsort = get_string("firstname");
        $ssort = get_string("lastname");

        $currentsort = $fsort;
        if ($sortkey == 'LASTNAME') {
            $currentsort = $ssort;
        }
        $sort = get_string("sortby", "qanda");
    }
    $current = '<span class="accesshide">' . get_string('current', 'qanda', "$currentsort $currentorder") . '</span>';
    echo "<br />$current $sort: $sbtag<a title=\"$ssort $sordertitle\" href=\"$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;sortkey=$sorder$sneworder&amp;mode=$mode\">$ssort$sicon</a>$sendbtag | " .
    "$fbtag<a title=\"$fsort $fordertitle\" href=\"$CFG->wwwroot/mod/qanda/view.php?id=$cm->id&amp;sortkey=$forder$fneworder&amp;mode=$mode\">$fsort$ficon</a>$fendbtag<br />";
}

/**
 *
 * @param object $entry0
 * @param object $entry1
 * @return int [-1 | 0 | 1]
 */
function qanda_sort_entries($entry0, $entry1) {

    if (textlib::strtolower(ltrim($entry0->question)) < textlib::strtolower(ltrim($entry1->question))) {
        return -1;
    } elseif (textlib::strtolower(ltrim($entry0->question)) > textlib::strtolower(ltrim($entry1->question))) {
        return 1;
    } else {
        return 0;
    }
}

/**
 *
 * @global object
 * @param array $entries
 * @param array $aliases
 * @param array $categories
 * @return string
 */
function qanda_generate_export_csv($entries, $aliases, $categories) {
    global $CFG;
    $csv = '';
    $delimiter = '';
    require_once($CFG->libdir . '/csvlib.class.php');
    $delimiter = csv_import_reader::get_delimiter('comma');
    $csventries = array(0 => array(get_string('question', 'qanda'), get_string('answer', 'qanda')));
    $csvaliases = array(0 => array());
    $csvcategories = array(0 => array());
    $aliascount = 0;
    $categorycount = 0;

    foreach ($entries as $entry) {
        $thisaliasesentry = array();
        $thiscategoriesentry = array();
        $thiscsventry = array($entry->question, nl2br($entry->answer));

        if (array_key_exists($entry->id, $aliases) && is_array($aliases[$entry->id])) {
            $thiscount = count($aliases[$entry->id]);
            if ($thiscount > $aliascount) {
                $aliascount = $thiscount;
            }
            foreach ($aliases[$entry->id] as $alias) {
                $thisaliasesentry[] = trim($alias);
            }
        }
        if (array_key_exists($entry->id, $categories) && is_array($categories[$entry->id])) {
            $thiscount = count($categories[$entry->id]);
            if ($thiscount > $categorycount) {
                $categorycount = $thiscount;
            }
            foreach ($categories[$entry->id] as $catentry) {
                $thiscategoriesentry[] = trim($catentry);
            }
        }
        $csventries[$entry->id] = $thiscsventry;
        $csvaliases[$entry->id] = $thisaliasesentry;
        $csvcategories[$entry->id] = $thiscategoriesentry;
    }
    $returnstr = '';
    foreach ($csventries as $id => $row) {
        $aliasstr = '';
        $categorystr = '';
        if ($id == 0) {
            $aliasstr = get_string('alias', 'qanda');
            $categorystr = get_string('category', 'qanda');
        }
        $row = array_merge($row, array_pad($csvaliases[$id], $aliascount, $aliasstr), array_pad($csvcategories[$id], $categorycount, $categorystr));
        $returnstr .= '"' . implode('"' . $delimiter . '"', $row) . '"' . "\n";
    }
    return $returnstr;
}

/**
 *
 * @param object $qanda
 * @param string $ignored invalid parameter
 * @param int|string $hook
 * @return string
 */
function qanda_generate_export_file($qanda, $ignored = "", $hook = 0, $cm) {
    global $CFG, $DB;

    //$cm = get_coursemodule_from_instance("qanda", $qanda->id, $course->id);
    $context = context_module::instance($cm->id);

    $co = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $co .= qanda_start_tag("qanda", 0, true);
    $co .= qanda_start_tag("INFO", 1, true);
    $co .= qanda_full_tag("NAME", 2, false, $qanda->name);
    $co .= qanda_full_tag("INTRO", 2, false, $qanda->intro);
    $co .= qanda_full_tag("INTROFORMAT", 2, false, $qanda->introformat);
    $co .= qanda_full_tag("ALLOWDUPLICATEDENTRIES", 2, false, $qanda->allowduplicatedentries);
    $co .= qanda_full_tag("MAINQANDA", 2, false, $qanda->mainqanda);
    $co .= qanda_full_tag("DISPLAYFORMAT", 2, false, $qanda->displayformat);
    $co .= qanda_full_tag("SHOWSPECIAL", 2, false, $qanda->showspecial);
    $co .= qanda_full_tag("SHOWALPHABET", 2, false, $qanda->showalphabet);
    $co .= qanda_full_tag("SHOWALL", 2, false, $qanda->showall);
    $co .= qanda_full_tag("ALLOWCOMMENTS", 2, false, $qanda->allowcomments);
    $co .= qanda_full_tag("USEDYNALINK", 2, false, $qanda->usedynalink);
    $co .= qanda_full_tag("DEFAULTAPPROVAL", 2, false, $qanda->defaultapproval);
    $co .= qanda_full_tag("GLOBALqanda", 2, false, $qanda->globalqanda);
    $co .= qanda_full_tag("ENTBYPAGE", 2, false, $qanda->entbypage);

    if ($entries = $DB->get_records("qanda_entries", array("qandaid" => $qanda->id))) {
        $co .= qanda_start_tag("ENTRIES", 2, true);
        foreach ($entries as $entry) {
            $permissiongranted = 1;
            if ($hook) {
                switch ($hook) {
                    case "ALL":
                    case "SPECIAL":
                        break;
                    default:
                        $permissiongranted = ($entry->question[strlen($hook) - 1] == $hook);
                        break;
                }
            }
            if ($hook) {
                switch ($hook) {
                    case QANDA_SHOW_ALL_CATEGORIES:
                        break;
                    case QANDA_SHOW_NOT_CATEGORISED:
                        $permissiongranted = !$DB->record_exists("qanda_entries_categories", array("entryid" => $entry->id));
                        break;
                    default:
                        $permissiongranted = $DB->record_exists("qanda_entries_categories", array("entryid" => $entry->id, "categoryid" => $hook));
                        break;
                }
            }
            if ($entry->approved and $permissiongranted) {
                $co .= qanda_start_tag("ENTRY", 3, true);
                $question = $entry->question;
                $question = file_rewrite_pluginfile_urls($question, 'pluginfile.php', $context->id, 'mod_qanda', 'question', $entry->id);
                $co .= qanda_full_tag("QUESTION", 4, false, $question); //trim($entry->question)
                $co .= qanda_full_tag("QUESTIONFORMAT", 4, false, $entry->questionformat);
                $answer = $entry->answer;
                $answer = file_rewrite_pluginfile_urls($answer, 'pluginfile.php', $context->id, 'mod_qanda', 'answer', $entry->id);
                $co .= qanda_full_tag("ANSWER", 4, false, $answer);
                $co .= qanda_full_tag("ANSWERFORMAT", 4, false, $entry->answerformat); // note: use old name for BC reasons
                $co .= qanda_full_tag("USEDYNALINK", 4, false, $entry->usedynalink);
                $co .= qanda_full_tag("CASESENSITIVE", 4, false, $entry->casesensitive);
                $co .= qanda_full_tag("FULLMATCH", 4, false, $entry->fullmatch);
                $co .= qanda_full_tag("TEACHERENTRY", 4, false, $entry->teacherentry);

                if ($aliases = $DB->get_records("qanda_alias", array("entryid" => $entry->id))) {
                    $co .= qanda_start_tag("ALIASES", 4, true);
                    foreach ($aliases as $alias) {
                        $co .= qanda_start_tag("ALIAS", 5, true);
                        $co .= qanda_full_tag("NAME", 6, false, trim($alias->alias));
                        $co .= qanda_end_tag("ALIAS", 5, true);
                    }
                    $co .= qanda_end_tag("ALIASES", 4, true);
                }
                if ($catentries = $DB->get_records("qanda_entries_categories", array("entryid" => $entry->id))) {
                    $co .= qanda_start_tag("CATEGORIES", 4, true);
                    foreach ($catentries as $catentry) {
                        $category = $DB->get_record("qanda_categories", array("id" => $catentry->categoryid));

                        $co .= qanda_start_tag("CATEGORY", 5, true);
                        $co .= qanda_full_tag("NAME", 6, false, $category->name);
                        $co .= qanda_full_tag("USEDYNALINK", 6, false, $category->usedynalink);
                        $co .= qanda_end_tag("CATEGORY", 5, true);
                    }
                    $co .= qanda_end_tag("CATEGORIES", 4, true);
                }

                $co .= qanda_end_tag("ENTRY", 3, true);
            }
        }
        $co .= qanda_end_tag("ENTRIES", 2, true);
    }


    $co .= qanda_end_tag("INFO", 1, true);
    $co .= qanda_end_tag("qanda", 0, true);

    return $co;
}

/// Functions designed by Eloy Lafuente
/// Functions to create, open and write header of the xml file

/**
 * Read import file and convert to current charset
 *
 * @global object
 * @param string $file
 * @return string
 */
function qanda_read_imported_file($file_content) {
    require_once(dirname(__FILE__) . '/../../lib/xmlize.php');
    global $CFG;

    return xmlize($file_content, 0);
}

/**
 * Return the xml start tag
 *
 * @param string $tag
 * @param int $level
 * @param bool $endline
 * @return string
 */
function qanda_start_tag($tag, $level = 0, $endline = false) {
    if ($endline) {
        $endchar = "\n";
    } else {
        $endchar = "";
    }
    return str_repeat(" ", $level * 2) . "<" . strtoupper($tag) . ">" . $endchar;
}

/**
 * Return the xml end tag
 * @param string $tag
 * @param int $level
 * @param bool $endline
 * @return string
 */
function qanda_end_tag($tag, $level = 0, $endline = true) {
    if ($endline) {
        $endchar = "\n";
    } else {
        $endchar = "";
    }
    return str_repeat(" ", $level * 2) . "</" . strtoupper($tag) . ">" . $endchar;
}

/**
 * Return the start tag, the contents and the end tag
 *
 * @global object
 * @param string $tag
 * @param int $level
 * @param bool $endline
 * @param string $content
 * @return string
 */
function qanda_full_tag($tag, $level = 0, $endline = true, $content) {
    global $CFG;

    $st = qanda_start_tag($tag, $level, $endline);
    $co = preg_replace("/\r\n|\r/", "\n", s($content));
    $et = qanda_end_tag($tag, 0, true);
    return $st . $co . $et;
}

/**
 *
 * Returns the html code to represent any pagging bar. Paramenters are:
 *
 * The function dinamically show the first and last pages, and "scroll" over pages.
 * Fully compatible with Moodle's print_paging_bar() function. Perhaps some day this
 * could replace the general one. ;-)
 *
 * @param int $totalcount total number of records to be displayed
 * @param int $page page currently selected (0 based)
 * @param int $perpage number of records per page
 * @param string $baseurl url to link in each page, the string 'page=XX' will be added automatically.
 *
 * @param int $maxpageallowed Optional maximum number of page allowed.
 * @param int $maxdisplay Optional maximum number of page links to show in the bar
 * @param string $separator Optional string to be used between pages in the bar
 * @param string $specialtext Optional string to be showed as an special link
 * @param string $specialvalue Optional value (page) to be used in the special link
 * @param bool $previousandnext Optional to decide if we want the previous and next links
 * @return string
 */
function qanda_get_paging_bar($totalcount, $page, $perpage, $baseurl, $maxpageallowed = 99999, $maxdisplay = 20, $separator = "&nbsp;", $specialtext = "", $specialvalue = -1, $previousandnext = true) {

    $code = '';

    $showspecial = false;
    $specialselected = false;

    //Check if we have to show the special link
    if (!empty($specialtext)) {
        $showspecial = true;
    }
    //Check if we are with the special link selected
    if ($showspecial && $page == $specialvalue) {
        $specialselected = true;
    }

    //If there are results (more than 1 page)
    if ($totalcount > $perpage) {
        $code .= "<div style=\"text-align:center\">";
        $code .= "<p>" . get_string("page") . ":";

        $maxpage = (int) (($totalcount - 1) / $perpage);

        //Lower and upper limit of page
        if ($page < 0) {
            $page = 0;
        }
        if ($page > $maxpageallowed) {
            $page = $maxpageallowed;
        }
        if ($page > $maxpage) {
            $page = $maxpage;
        }

        //Calculate the window of pages
        $pagefrom = $page - ((int) ($maxdisplay / 2));
        if ($pagefrom < 0) {
            $pagefrom = 0;
        }
        $pageto = $pagefrom + $maxdisplay - 1;
        if ($pageto > $maxpageallowed) {
            $pageto = $maxpageallowed;
        }
        if ($pageto > $maxpage) {
            $pageto = $maxpage;
        }

        //Some movements can be necessary if don't see enought pages
        if ($pageto - $pagefrom < $maxdisplay - 1) {
            if ($pageto - $maxdisplay + 1 > 0) {
                $pagefrom = $pageto - $maxdisplay + 1;
            }
        }

        //Calculate first and last if necessary
        $firstpagecode = '';
        $lastpagecode = '';
        if ($pagefrom > 0) {
            $firstpagecode = "$separator<a href=\"{$baseurl}page=0\">1</a>";
            if ($pagefrom > 1) {
                $firstpagecode .= "$separator...";
            }
        }
        if ($pageto < $maxpage) {
            if ($pageto < $maxpage - 1) {
                $lastpagecode = "$separator...";
            }
            $lastpagecode .= "$separator<a href=\"{$baseurl}page=$maxpage\">" . ($maxpage + 1) . "</a>";
        }

        //Previous
        if ($page > 0 && $previousandnext) {
            $pagenum = $page - 1;
            $code .= "&nbsp;(<a  href=\"{$baseurl}page=$pagenum\">" . get_string("previous") . "</a>)&nbsp;";
        }

        //Add first
        $code .= $firstpagecode;

        $pagenum = $pagefrom;

        //List of maxdisplay pages
        while ($pagenum <= $pageto) {
            $pagetoshow = $pagenum + 1;
            if ($pagenum == $page && !$specialselected) {
                $code .= "$separator<b>$pagetoshow</b>";
            } else {
                $code .= "$separator<a href=\"{$baseurl}page=$pagenum\">$pagetoshow</a>";
            }
            $pagenum++;
        }

        //Add last
        $code .= $lastpagecode;

        //Next
        if ($page < $maxpage && $page < $maxpageallowed && $previousandnext) {
            $pagenum = $page + 1;
            $code .= "$separator(<a href=\"{$baseurl}page=$pagenum\">" . get_string("next") . "</a>)";
        }

        //Add special
        if ($showspecial) {
            $code .= '&nbsp;'; //'<br />';
            if ($specialselected) {
                $code .= "<b>$specialtext</b>";
            } else {
                $code .= "$separator<a href=\"{$baseurl}page=$specialvalue\">$specialtext</a>";
            }
        }

        //End html
        $code .= "</p>";
        $code .= "</div>";
    }

    return $code;
}

/**
 * @return array
 */
function qanda_get_view_actions() {
    return array('view', 'view all', 'view entry');
}

/**
 * @return array
 */
function qanda_get_post_actions() {
    return array('add category', 'add entry', 'approve entry', 'delete category', 'delete entry', 'edit category', 'update entry');
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the qanda.
 * @param object $mform form passed by reference
 */
function qanda_reset_course_form_answer(&$mform) {
    $mform->addElement('header', 'qandaheader', get_string('modulenameplural', 'qanda'));
    $mform->addElement('checkbox', 'reset_qanda_all', get_string('resetqandasall', 'qanda'));

    $mform->addElement('select', 'reset_qanda_types', get_string('resetqandas', 'qanda'), array('main' => get_string('mainqanda', 'qanda'), 'secondary' => get_string('secondaryqanda', 'qanda')), array('multiple' => 'multiple'));
    $mform->setAdvanced('reset_qanda_types');
    $mform->disabledIf('reset_qanda_types', 'reset_qanda_all', 'checked');

    $mform->addElement('checkbox', 'reset_qanda_notenrolled', get_string('deletenotenrolled', 'qanda'));
    $mform->disabledIf('reset_qanda_notenrolled', 'reset_qanda_all', 'checked');

    $mform->addElement('checkbox', 'reset_qanda_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_qanda_ratings', 'reset_qanda_all', 'checked');

    $mform->addElement('checkbox', 'reset_qanda_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_qanda_comments', 'reset_qanda_all', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function qanda_reset_course_form_defaults($course) {
    return array('reset_qanda_all' => 0, 'reset_qanda_ratings' => 1, 'reset_qanda_comments' => 1, 'reset_qanda_notenrolled' => 0);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * qanda responses for course $data->courseid.
 *
 * @global object
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function qanda_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'qanda');
    $status = array();

    $allentriessql = "SELECT e.id
                        FROM {qanda_entries} e
                             JOIN {qanda} g ON e.qandaid = g.id
                       WHERE g.course = ?";

    $allqandassql = "SELECT g.id
                           FROM {qanda} g
                          WHERE g.course = ?";

    $params = array($data->courseid);

    $fs = get_file_storage();

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_qanda';
    $ratingdeloptions->ratingarea = 'entry';

    // delete entries if requested
    if (!empty($data->reset_qanda_all) or (!empty($data->reset_qanda_types) and in_array('main', $data->reset_qanda_types) and in_array('secondary', $data->reset_qanda_types))) {

        $params[] = 'qanda_entry';
        $DB->delete_records_select('comments', "itemid IN ($allentriessql) AND commentarea=?", $params);
        $DB->delete_records_select('qanda_alias', "entryid IN ($allentriessql)", $params);
        $DB->delete_records_select('qanda_entries', "qandaid IN ($allqandassql)", $params);

        // now get rid of all attachments
        if ($qandas = $DB->get_records_sql($allqandassql, $params)) {
            foreach ($qandas as $qandaid => $unused) {
                if (!$cm = get_coursemodule_from_instance('qanda', $qandaid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_qanda', 'attachment');

                //delete ratings
                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            qanda_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('resetqandasall', 'qanda'), 'error' => false);
    } else if (!empty($data->reset_qanda_types)) {
        $mainentriessql = "$allentriessql AND g.mainqanda=1";
        $secondaryentriessql = "$allentriessql AND g.mainqanda=0";

        $mainqandassql = "$allqandassql AND g.mainqanda=1";
        $secondaryqandassql = "$allqandassql AND g.mainqanda=0";

        if (in_array('main', $data->reset_qanda_types)) {
            $params[] = 'qanda_entry';
            $DB->delete_records_select('comments', "itemid IN ($mainentriessql) AND commentarea=?", $params);
            $DB->delete_records_select('qanda_entries', "qandaid IN ($mainqandassql)", $params);

            if ($qandas = $DB->get_records_sql($mainqandassql, $params)) {
                foreach ($qandas as $qandaid => $unused) {
                    if (!$cm = get_coursemodule_from_instance('qanda', $qandaid)) {
                        continue;
                    }
                    $context = context_module::instance($cm->id);
                    $fs->delete_area_files($context->id, 'mod_qanda', 'attachment');

                    //delete ratings
                    $ratingdeloptions->contextid = $context->id;
                    $rm->delete_ratings($ratingdeloptions);
                }
            }

            // remove all grades from gradebook
            if (empty($data->reset_gradebook_grades)) {
                qanda_reset_gradebook($data->courseid, 'main');
            }

            $status[] = array('component' => $componentstr, 'item' => get_string('resetqandas', 'qanda') . ': ' . get_string('mainqanda', 'qanda'), 'error' => false);
        } else if (in_array('secondary', $data->reset_qanda_types)) {
            $params[] = 'qanda_entry';
            $DB->delete_records_select('comments', "itemid IN ($secondaryentriessql) AND commentarea=?", $params);
            $DB->delete_records_select('qanda_entries', "qandaid IN ($secondaryqandassql)", $params);
            // remove exported source flag from entries in main qanda
            $DB->execute("UPDATE {qanda_entries}
                             SET sourceqandaid=0
                           WHERE qandaid IN ($mainqandassql)", $params);

            if ($qandas = $DB->get_records_sql($secondaryqandassql, $params)) {
                foreach ($qandas as $qandaid => $unused) {
                    if (!$cm = get_coursemodule_from_instance('qanda', $qandaid)) {
                        continue;
                    }
                    $context = context_module::instance($cm->id);
                    $fs->delete_area_files($context->id, 'mod_qanda', 'attachment');

                    //delete ratings
                    $ratingdeloptions->contextid = $context->id;
                    $rm->delete_ratings($ratingdeloptions);
                }
            }

            // remove all grades from gradebook
            if (empty($data->reset_gradebook_grades)) {
                qanda_reset_gradebook($data->courseid, 'secondary');
            }

            $status[] = array('component' => $componentstr, 'item' => get_string('resetqandas', 'qanda') . ': ' . get_string('secondaryqanda', 'qanda'), 'error' => false);
        }
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_qanda_notenrolled)) {
        $entriessql = "SELECT e.id, e.userid, e.qandaid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {qanda_entries} e
                              JOIN {qanda} g ON e.qandaid = g.id
                              LEFT JOIN {user} u ON e.userid = u.id
                        WHERE g.course = ? AND e.userid > 0";

        $course_context = context_course::instance($data->courseid);
        $notenrolled = array();
        $rs = $DB->get_recordset_sql($entriessql, $params);
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                if (array_key_exists($entry->userid, $notenrolled) or !$entry->userexists or $entry->userdeleted or !is_enrolled($course_context, $entry->userid)) {
                    $DB->delete_records('comments', array('commentarea' => 'qanda_entry', 'itemid' => $entry->id));
                    $DB->delete_records('qanda_entries', array('id' => $entry->id));

                    if ($cm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
                        $context = context_module::instance($cm->id);
                        $fs->delete_area_files($context->id, 'mod_qanda', 'attachment', $entry->id);

                        //delete ratings
                        $ratingdeloptions->contextid = $context->id;
                        $rm->delete_ratings($ratingdeloptions);
                    }
                }
            }
            $status[] = array('component' => $componentstr, 'item' => get_string('deletenotenrolled', 'qanda'), 'error' => false);
        }
        $rs->close();
    }

    // remove all ratings
    if (!empty($data->reset_qanda_ratings)) {
        //remove ratings
        if ($qandas = $DB->get_records_sql($allqandassql, $params)) {
            foreach ($qandas as $qandaid => $unused) {
                if (!$cm = get_coursemodule_from_instance('qanda', $qandaid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);

                //delete ratings
                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            qanda_reset_gradebook($data->courseid);
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallratings'), 'error' => false);
    }

    // remove comments
    if (!empty($data->reset_qanda_comments)) {
        $params[] = 'qanda_entry';
        $DB->delete_records_select('comments', "itemid IN ($allentriessql) AND commentarea= ? ", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallcomments'), 'error' => false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('qanda', array('assesstimestart', 'assesstimefinish'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 * @return array
 */
function qanda_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames', 'moodle/site:trustcontent', 'moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate', 'moodle/comment:view', 'moodle/comment:post', 'moodle/comment:delete');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function qanda_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return false;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_GRADE_HAS_GRADE: return false; //  true;
        case FEATURE_GRADE_OUTCOMES: return false; // true;
        case FEATURE_RATE: return false; //true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;

        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this qanda based on any conditions
 * in qanda settings.
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function qanda_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get qanda details
    if (!($qanda = $DB->get_record('qanda', array('id' => $cm->instance)))) {
        throw new Exception("Can't find qanda {$cm->instance}");
    }

    $result = $type; // Default return value

    if ($qanda->completionentries) {
        $value = $qanda->completionentries <=
                $DB->count_records('qanda_entries', array('qandaid' => $qanda->id, 'userid' => $userid, 'approved' => 1));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}

function qanda_extend_navigation($navigation, $course, $module, $cm) {
    global $CFG;
    //Hide navigation bar links that print under each course Q&A
    //   $navigation->add(get_string('standardview', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $cm->id, 'mode' => 'letter')));
    //   $navigation->add(get_string('categoryview', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $cm->id, 'mode' => 'cat')));
    //   $navigation->add(get_string('dateview', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $cm->id, 'mode' => 'date')));
    //   $navigation->add(get_string('authorview', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $cm->id, 'mode' => 'author')));
    //Navigation links for viewing entries and adding a new entry
    //    $navigation->add(get_string('addentry', 'qanda'), new moodle_url('/mod/qanda/edit.php', array('cmid' => $cm->id)));
    //    $navigation->add(get_string('qanda:view', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $cm->id)));
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $qandanode The node to add module settings to
 */
function qanda_extend_settings_navigation(settings_navigation $settings, navigation_node $qandanode) {
    global $PAGE, $DB, $CFG, $USER;

    $mode = optional_param('mode', '', PARAM_ALPHA);
    $hook = optional_param('hook', 'ALL', PARAM_CLEAN);

    if (has_capability('mod/qanda:import', $PAGE->cm->context)) {
        $qandanode->add(get_string('importentries', 'qanda'), new moodle_url('/mod/qanda/import.php', array('id' => $PAGE->cm->id)));
    }

    if (has_capability('mod/qanda:export', $PAGE->cm->context)) {
        $qandanode->add(get_string('exportentries', 'qanda'), new moodle_url('/mod/qanda/export.php', array('id' => $PAGE->cm->id, 'mode' => $mode, 'hook' => $hook)));
    }

    if (has_capability('mod/qanda:answer', $PAGE->cm->context) && ($hiddenentries = $DB->count_records('qanda_entries', array('qandaid' => $PAGE->cm->instance, 'approved' => 0)))) {
        $qandanode->add(get_string('waitingapproval', 'qanda'), new moodle_url('/mod/qanda/view.php', array('id' => $PAGE->cm->id, 'mode' => 'approval')));
    }

    if (has_capability('mod/qanda:write', $PAGE->cm->context)) {
        $qandanode->add(get_string('addentry', 'qanda'), new moodle_url('/mod/qanda/edit.php', array('cmid' => $PAGE->cm->id)));
    }

    $qanda = $DB->get_record('qanda', array("id" => $PAGE->cm->instance));

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->qanda_enablerssfeeds) && $qanda->rsstype && $qanda->rssarticles && has_capability('mod/qanda:view', $PAGE->cm->context)) {
        require_once("$CFG->libdir/rsslib.php");

        $string = get_string('rsstype', 'forum');

        $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $USER->id, 'mod_qanda', $qanda->id));
        $qandanode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));
    }
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @package  mod_qanda
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function qanda_comment_permissions($comment_param) {
    return array('post' => true, 'view' => true);
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @package  mod_qanda
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function qanda_comment_validate($comment_param) {
    global $DB;
    // validate comment area
    if ($comment_param->commentarea != 'qanda_entry') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$record = $DB->get_record('qanda_entries', array('id' => $comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if ($record->sourceqandaid && $record->sourceqandaid == $comment_param->cm->instance) {
        $qanda = $DB->get_record('qanda', array('id' => $record->sourceqandaid));
    } else {
        $qanda = $DB->get_record('qanda', array('id' => $record->qandaid));
    }
    if (!$qanda) {
        throw new comment_exception('invalidid', 'data');
    }
    if (!$course = $DB->get_record('course', array('id' => $qanda->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('qanda', $qanda->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    $context = context_module::instance($cm->id);

    if ($qanda->defaultapproval and !$record->approved and !has_capability('mod/qanda:answer', $context)) {
        throw new comment_exception('notapproved', 'qanda');
    }
    // validate context id
    if ($context->id != $comment_param->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // validation for comment deletion
    if (!empty($comment_param->commentid)) {
        if ($comment = $DB->get_record('comments', array('id' => $comment_param->commentid))) {
            if ($comment->commentarea != 'qanda_entry') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $comment_param->context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $comment_param->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function qanda_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-qanda-*' => get_string('page-mod-qanda-x', 'qanda'),
        'mod-qanda-view' => get_string('page-mod-qanda-view', 'qanda'),
        'mod-qanda-edit' => get_string('page-mod-qanda-edit', 'qanda'));
    return $module_pagetype;
}

/**
 *
 * @global object
 * @global object
 * @global object
 * @param int $courseid
 * @param array $entries
 * @param int $displayformat
 */
function qanda_print_dynaentry($courseid, $entries, $displayformat = -1) {
    global $USER, $CFG, $DB;

    echo '<div class="box-align-center">';
    echo '<table class="qandapopup" cellspacing="0"><tr>';
    echo '<td>';
    if ($entries) {
        foreach ($entries as $entry) {
            if (!$qanda = $DB->get_record('qanda', array('id' => $entry->qandaid))) {
                print_error('invalidid', 'qanda');
            }
            if (!$course = $DB->get_record('course', array('id' => $qanda->course))) {
                print_error('coursemisconf');
            }
            if (!$cm = get_coursemodule_from_instance('qanda', $entry->qandaid, $qanda->course)) {
                print_error('invalidid', 'qanda');
            }

            //If displayformat is present, override qanda->displayformat
            if ($displayformat < 0) {
                $dp = $qanda->displayformat;
            } else {
                $dp = $displayformat;
            }

            //Get popupformatname
            $format = $DB->get_record('qanda_formats', array('name' => $dp));
            $displayformat = $format->popupformatname;

            //Check displayformat variable and set to default if necessary
            if (!$displayformat) {
                $displayformat = 'dictionary';
            }

            $formatfile = $CFG->dirroot . '/mod/qanda/formats/' . $displayformat . '/' . $displayformat . '_format.php';
            $functionname = 'qanda_show_entry_' . $displayformat;

            if (file_exists($formatfile)) {
                include_once($formatfile);
                if (function_exists($functionname)) {
                    $functionname($course, $cm, $qanda, $entry, '', '', '', '');
                }
            }
        }
    }
    echo '</td>';
    echo '</tr></table></div>';
}