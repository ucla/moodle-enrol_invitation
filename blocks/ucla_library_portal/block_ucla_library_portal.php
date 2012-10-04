<?php
/**
 * Block class for UCLA Library Research Portal
 *
 * @package    block
 * @subpackage ucla_library_portal
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');

class block_ucla_library_portal extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_library_portal');
    }

    public function get_content() {
        return null;
    }

    /**
     * Use UCLA Course menu block hook
     */
    public static function get_navigation_nodes($course) {
        $ret_val = array();
        // check to see if course is non-srs
        if (is_collab_site($course)) {
            return $ret_val;
        }        
        $url = new moodle_url('http://www.library.ucla.edu/library-research-portal');
        $ret_val[] = navigation_node::create(get_string('portal_name', 'block_ucla_library_portal'), $url);
        return $ret_val;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }    
}
