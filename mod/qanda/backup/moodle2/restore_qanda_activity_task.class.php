<?php

/**
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/qanda/backup/moodle2/restore_qanda_stepslib.php'); // Because it exists (must)

/**
 * qanda restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_qanda_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_qanda_activity_structure_step('qanda_structure', 'qanda.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('qanda', array('intro'), 'qanda');
        $contents[] = new restore_decode_content('qanda_entries', array('answer'), 'qanda_entry');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('QANDAVIEWBYID', '/mod/qanda/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('QANDAINDEX', '/mod/qanda/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('QANDASHOWENTRY', '/mod/qanda/showentry.php?courseid=$1&eid=$2',
                        array('course', 'qanda_entry'));

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * qanda logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('qanda', 'add', 'view.php?id={course_module}', '{qanda}');
        $rules[] = new restore_log_rule('qanda', 'update', 'view.php?id={course_module}', '{qanda}');
        $rules[] = new restore_log_rule('qanda', 'view', 'view.php?id={course_module}', '{qanda}');
        $rules[] = new restore_log_rule('qanda', 'add category', 'editcategories.php?id={course_module}', '{qanda_category}');
        $rules[] = new restore_log_rule('qanda', 'edit category', 'editcategories.php?id={course_module}', '{qanda_category}');
        $rules[] = new restore_log_rule('qanda', 'delete category', 'editcategories.php?id={course_module}', '{qanda_category}');
        $rules[] = new restore_log_rule('qanda', 'add entry', 'view.php?id={course_module}&mode=entry&hook={qanda_entry}', '{qanda_entry}');
        $rules[] = new restore_log_rule('qanda', 'update entry', 'view.php?id={course_module}&mode=entry&hook={qanda_entry}', '{qanda_entry}');
        $rules[] = new restore_log_rule('qanda', 'delete entry', 'view.php?id={course_module}&mode=entry&hook={qanda_entry}', '{qanda_entry}');
        $rules[] = new restore_log_rule('qanda', 'approve entry', 'showentry.php?id={course_module}&eid={qanda_entry}', '{qanda_entry}');
        $rules[] = new restore_log_rule('qanda', 'view entry', 'showentry.php?eid={qanda_entry}', '{qanda_entry}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('qanda', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

}
