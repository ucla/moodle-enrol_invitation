<?php

/**
 * Generator class to help in the writing of unit tests for the TA sites plugin.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/enrol/meta/lib.php');

/**
 * block_ucla_tasites data generator
 *
 * @package    block_ucla_tasites
 * @category   phpunit
 * @copyright  UC Regents
 */
class block_ucla_tasites_generator extends phpunit_block_generator {
    public $ta_admin_id = null;
    public $ta_id = null;

    /**
     * Create new TA site for given course record and user.
     * 
     * @param stdClass $course  Parent course
     * @param array $user       Owner of TA site
     * 
     * @return stdClass         Newly create TA site course record
     */
    public function create_instance($course = NULL, array $user = NULL) {
        if (empty($course)) {
            $course = $this->datagenerator->create_course();
        }
        if (empty($user)) {
            $user = $this->datagenerator->create_user();
        }
        $tasite = $this->create_instance_with_role($course, (array)$user, 'ta');
        return $tasite;
    }

    /**
     * Create new TA site for given course record. Will also make sure that user
     * is a TA with given role for parent course.
     * 
     * @param stdClass $course  Parent course
     * @param array $user       Owner of TA site
     * @param string $role      Should be 'ta' or 'ta_admin', defaults to 'ta'.
     * 
     * @return stdClass         Newly create TA site course record
     */
    public function create_instance_with_role($course, array $user, $role = 'ta') {
        global $DB;

        // make sure that user has given role in parent course
        $context = context_course::instance($course->id);
        $roleid = $this->ta_id;
        if ($role == 'ta_admin') {
            $roleid = $this->ta_admin_id;
        }
        $this->enrol_user($user['id'], $course->id, $roleid);

        $tainfo = new stdClass();
        $tainfo->parent_course = $course;
        $tainfo->id = $user['id'];        
        $tainfo->firstname = $user['firstname'];
        $tainfo->lastname = $user['lastname'];
        $tainfo->fullname = fullname((object) $user);

        $tasite = block_ucla_tasites::create_tasite($tainfo);

        return $tasite;
    }

    /**
     * Simplified enrolment of user to course using default options.
     *
     * It is strongly recommended to use only this method for 'manual' and 'self' plugins only!!!
     *
     * @todo This method was copied from Moodle 2.4+ data_generator. When we
     * upgrade to the next version of Moodle, please replace this.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $roleid optional role id, use only with manual plugin
     * @param string $enrol name of enrol plugin,
     *     there must be exactly one instance in course,
     *     it must support enrol_user() method.
     * @return bool success
     */
    private function enrol_user($userid, $courseid, $roleid = null, $enrol = 'manual') {
        global $DB;

        if (!$plugin = enrol_get_plugin($enrol)) {
            return false;
        }

        $instances = $DB->get_records('enrol', array('courseid'=>$courseid, 'enrol'=>$enrol));
        if (count($instances) != 1) {
            return false;
        }
        $instance = reset($instances);

        if (is_null($roleid) and $instance->roleid) {
            $roleid = $instance->roleid;
        }

        $plugin->enrol_user($instance, $userid, $roleid);

        return true;
    }

    /**
     * Does al needed setup to get TA sites working in phpunit.
     *  - Creates TA and TA admin roles necessary to use the TA site block for 
     *    unit tests.
     *  - Enables meta enrollment plugin
     */
    public function setup() {
        global $DB;

        // Create TA and TA admin roles.
        $this->ta_admin_id = create_role('Teaching Assistant (admin)',
                'ta_admin', '', 'editingteacher');
        $this->ta_id = create_role('Teaching Assistant', 'ta', '', 'student');

        // To enable meta enrollment plugin we are just going to enable
        // everything.
        $all = enrol_get_plugins(false);
        set_config('enrol_plugins_enabled', implode(',', array_keys($all)));
    }
}