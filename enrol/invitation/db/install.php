<?php
/**
 * install.php
 *
 * @package    enrol
 * @subpackage invitation
 * @copyright  2012 Rex Lorenzo <rex@oid.ucla.edu>
 */
global $CFG;
require_once($CFG->dirroot . '/enrol/invitation/eventslib.php');
require_once($CFG->libdir . '/enrollib.php');

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_enrol_invitation_install() {
    global $DB;
    
    // enable site invitation enrollment plugin
    // see /admin/enrol.php
    $enabled = enrol_get_plugins(true);
    $enabled = array_keys($enabled);
    $enabled[] = 'invitation';
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    $syscontext = context_system::instance();    
    $syscontext->mark_dirty(); // resets all enrol caches    
    
    // install site invitation plugin for every course on the system
    // NOTE: use get_recordset instead of get_records because system might have
    // very many courses and loading them all into memory would crash the system
    // see http://docs.moodle.org/dev/Datalib_Notes
    // and http://docs.moodle.org/dev/Data_manipulation_API#Using_Recordsets
    $courses_records = $DB->get_recordset('course');    
    foreach ($courses_records as $course) {
        // make sure that we aren't adding the SITEID
        if ($course->id == SITEID) {
            continue;
        }
        
        if (!add_site_invitation_plugin($course)) {
            debugging('Cannot add enrol plugin for courseid ' . $course->id);
        }        
    }
    $courses_records->close();
}
