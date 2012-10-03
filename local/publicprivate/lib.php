<?php
/**
 * Library of interface functions and constants for public/private
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the public/private specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage publicprivate
 */

/**
 * Called by the event api whenever mod_created/mod_updated are triggered. 
 * 
 * Checks the grouping of a resource and makes sure that public/private is 
 * properly triggered. For example:
 * 
 *  groupingid | groupmembersonly | result
 *  0          | 1                | Make public
 *  >0 && !pp  | 1                | Do nothing
 *  pp         | 1                | Already private, do nothing
 *  0          | 0                | Do nothing
 *  >0 && !pp  | 0                | Do nothing
 *  pp         | 0                | Make private
 * 
 *  pp = public/private groupingid
 * 
 * @param object $mod   Mod passed is not the actual module object with the 
 *                      grouping configs. It just has modulename, name, cmid, 
 *                      courseid, and userid
 */
function handle_mod($mod) {
    global $CFG;
    
    require_once($CFG->libdir . '/publicprivate/module.class.php');
    $changes_made = false;  // if true, then need to clear course cache
    
    $ppmod = PublicPrivate_Module::build($mod->cmid);

    $groupingid = $ppmod->get_grouping();
    $groupmembersonly = $ppmod->get_groupmembersonly();
    
    if (!empty($groupmembersonly)) {
        if (empty($groupingid)) {
            // groupingid | groupmembersonly | result            
            // 0          | 1                | Make public
            // for some reason, $groupmembersonly was enabled, but with no grouping set
            $ppmod->disable();
            $changes_made = true;
        }
        // everything else is do nothing if groupmembersonly=1         
    } else {
        // need to get public/private grouping
        require_once($CFG->libdir . '/publicprivate/course.class.php');
        $ppcourse = PublicPrivate_Course::build($mod->courseid);
        $ppgrouping = $ppcourse->get_grouping();
        if (!empty($ppgrouping)) {
            if ($ppgrouping == $groupingid) {
                // groupingid | groupmembersonly | result            
                // pp         | 0                | Make private
                // for some reason, have pp grouping, but groupmembersonly,
                // which would make public/private useless
                $ppmod->enable();
                $changes_made = true;
            }
        }
    }
    
    if (!empty($changes_made)) {
        // potential changes in visibity, so need to clear cache
        rebuild_course_cache($mod->courseid, true);
    }
}

/**
 * Cron for public/private to do some sanity checks:
 *  1) courses with public/private enabled should have the public/private 
 *     grouping as the default grouping
 *  2) group members for public/private grouping should only be in group once
 *  3) Make sure that enablegroupmembersonly is enabled for course content
 *     using the public/private goruping if enablepublicprivate is true
 */
function local_publicprivate_cron() {    
    global $CFG, $DB;
    require_once($CFG->libdir . '/publicprivate/course.class.php');    
    
    // 1) courses with public/private enabled should have the public/private 
    //    grouping as the default grouping
    mtrace('Looking for courses with invalid publicprivate groupings set');
    
    // first find all courses that have enablepublicprivate=1, but 
    // have defaultgroupingid=0 (should be publicprivate grouping)
    
    $courses = $DB->get_recordset('course', array('enablepublicprivate' => 1, 
            'defaultgroupingid' => 0));
    if ($courses->valid()) {
        foreach ($courses as $course) {
            if (empty($course->groupingpublicprivate)) {
                // public/private is enabled, but there is no public/private 
                // grouping?! disable pp and then reenable
                $ppcourse = new PublicPrivate_Course($course);
                if ($ppcourse->is_activated()) {
                    // is activated, but has no groupingpublicprivate?!
                    // need to redo this course, something wrong happened
                    mtrace(sprintf('  Deactivating public/private for course %d', 
                            $course->id));                                          
                    $ppcourse->deactivate();
                }
                mtrace(sprintf('  Activating public/private for course %d', 
                        $course->id, $course->groupingpublicprivate));                      
                $ppcourse->activate();
                $course->groupingpublicprivate = $ppcourse->get_grouping();          
                mtrace(sprintf('  Activated public/private for course %d, groupingpublicprivate now %d', 
                        $course->id, $course->groupingpublicprivate));                                      
            }
            
            $course->defaultgroupingid = $course->groupingpublicprivate;
            mtrace(sprintf('  Seting defaultgroupingid to be %d for course %d', 
                    $course->groupingpublicprivate, $course->id));            
            $result = $DB->update_record('course', $course, true);
        }
    }
    
    // 2) group members for public/private grouping should only be in group once
    mtrace('Looking for duplicate groups_members entries');
    
    // just find any duplicate entries in groups_members table, since they 
    // shouldn't be there anyways
    $sql = "SELECT  duplicate.*
            FROM    {groups_members} AS original,
                    {groups_members} AS duplicate
            WHERE   original.groupid=duplicate.groupid AND
                    original.userid=duplicate.userid AND
                    original.id!=duplicate.id";
    $results = $DB->get_records_sql($sql);    
    if (!empty($results)) {
        $valid_group_members = array(); // keep track of which group members to keep       
        foreach ($results as $result) {
            $groupid    = $result->groupid;
            $userid     = $result->userid;

            if (!isset($valid_group_members[$groupid][$userid])) {
                $valid_group_members[$groupid][$userid] = true;
                continue;
            }

            // found duplicate, so delete it
            mtrace(sprintf('  Deleting duplicate entry in groups_members for ' . 
                    'groupid %d and userid %d', $groupid, $userid));               
            $DB->delete_records('groups_members', array('id' => $result->id));     
        }
    }
    
    // 3) Make sure that enablegroupmembersonly is enabled for course content 
    //    using the public/private goruping if enablepublicprivate is true 
    mtrace('Looking for private course content with enablepublicprivate=0');    
    
    $sql = "SELECT  cm.id                
            FROM    {course} AS c,
                    {course_modules} AS cm
            WHERE   c.id=cm.course AND
                    c.groupingpublicprivate=cm.groupingid AND
                    cm.groupmembersonly=0";
    $results = $DB->get_records_sql($sql);
    if (!empty($results)) {
        foreach ($results as $result) {
            mtrace(sprintf('  Fixing groupmembersonly for course_module %d', 
                    $result->id));            
            $DB->set_field('course_modules', 'groupmembersonly', 1, 
                    array('id' => $result->id));
        }    
    }    
}
