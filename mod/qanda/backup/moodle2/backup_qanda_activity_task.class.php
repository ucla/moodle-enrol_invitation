<?php

/**
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/qanda/backup/moodle2/backup_qanda_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the qanda instance
 */
class backup_qanda_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
        
    }

    /**
     * Defines a backup step to store the instance data in the qanda.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_qanda_activity_structure_step('qanda_structure', 'qanda.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of qandas
        $search = "/(" . $base . "\/mod\/qanda\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@QANDAINDEX*$2@$', $content);

        // Link to qanda view by moduleid
        $search = "/(" . $base . "\/mod\/qanda\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@QANDAVIEWBYID*$2@$', $content);

        // Link to qanda entry
        $search = "/(" . $base . "\/mod\/qanda\/showentry.php\?courseid=)([0-9]+)(&|&amp;)eid=([0-9]+)/";
        $content = preg_replace($search, '$@QANDASHOWENTRY*$2*$4@$', $content);

        return $content;
    }

}
