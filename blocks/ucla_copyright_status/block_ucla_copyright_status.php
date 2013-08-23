<?php
/**
 * Block class for UCLA Manage copyright status
 *
 * @package    ucla
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents    
 * @author     Jun Wan <jwan@humnet.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');

class block_ucla_copyright_status extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_copyright_status');
    }

    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     * Adding link to site menu block header.
     *
     * @param object $course
     * @param int $displaysection
     *
     * @return array   Returns link to tool. 
     */
    static function get_editing_link($params) {
        global $CFG;
        
        // Get number of items copyright itemsthat need attention
        $a = sizeof(get_files_copyright_status_by_course($params['course']->id, 
                $CFG->sitedefaultlicense));

        // Put inside a badge if items need attention
        if (empty($a)) {
            $span = '';
        } else {
            $span = html_writer::tag('span', $a, 
                    array('class' => 'badge badge-warning', 
                          'tabindex' => '0',
                          'aria-label' => $a . get_string('aria_copyright_badge', 'block_ucla_copyright_status'), 
                          'aria-describedby' => 'id-copyright-status'));
        }
        
        $link = html_writer::link(
                new moodle_url('/blocks/ucla_copyright_status/view.php',
                    array('courseid' => $course->id,
                          'section' => $params['section'])),
                get_string('pluginname', 'block_ucla_copyright_status'),
                array('id' => 'id-copyright-status'));
        
           // site menu block arranges editing links by key, make sure this is the
        // 3rd link
        return array(3 => $link . $span);
    }

    /**
     * Adding link to copyright management in control panel, in "Other tools".
     * 
     * @global type $CFG
     * @param object $course
     * @param object $context
     */
    static function ucla_cp_hook($course, $context) {
        global $CFG;

        $result = array();
        $result[] = array(
                'item_name' => 'manage_copyright',
                'tags' => array('ucla_cp_mod_other'),
                'action' => new moodle_url('/blocks/ucla_copyright_status/view.php',
                    array('courseid' => $course->id)),
                'required_cap' => 'moodle/course:manageactivities'
            );

        return $result;
    }

}

