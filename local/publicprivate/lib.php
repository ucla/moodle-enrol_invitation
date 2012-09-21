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
 * Cron for public/private to do some sanity checks:
 *  1) courses with public/private enabled should have the public/private 
 *     grouping as the default grouping
 *  2) group members for public/private grouping should only be in group once 
 *     (@todo)
 *  3) Make sure that enablegroupmembersonly is enabled if enablepublicprivate is
 *     enabled
 */
function local_publicprivate_cron() {
    global $DB;
    
    // courses with public/private enabled should have the public/private 
    // grouping as the default grouping
    
    // first find all courses that have enablepublicprivate=1, but 
    // have defaultgroupingid=0 (should be publicprivate grouping
    $courses = $DB->get_recordset('course', array('enablepublicprivate' => 1, 
            'defaultgroupingid' => 0));
    if ($courses->valid()) {
        foreach ($courses as $course) {
            $course->defaultgroupingid = $course->groupingpublicprivate;
            $result = $DB->update_record('course', $course, true);
            if ($result) {
                mtrace(sprintf('  Setting defaultgroupingid to be ' . 
                        '%d for course %d', 
                        $course->groupingpublicprivate, $course->id));
            }
        }
    }
}
