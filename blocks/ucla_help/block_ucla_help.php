<?php
/**
 * Block class for UCLA Help Form
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
defined('MOODLE_INTERNAL') || die();

class block_ucla_help extends block_base {

    public function init()
    {
        $this->title = get_string('pluginname', 'block_ucla_help');
    }

    public function get_content()
    {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;        
        $this->content->text = get_string('block_text', 'block_ucla_help');

        return $this->content;
    }

    public function instance_allow_multiple() {
        return false;
    }
    
    public function has_config() {
        return true;
    }    
    
    // link to page that will let user interact with block
    public static function get_action_link() {
        global $CFG, $COURSE;        
        if (empty($COURSE) || $COURSE->id === SITEID) {
            // not in a course
//            return sprintf('<a id="loadUclaHelpOverlay" href="/blocks/ucla_help/index.php">%s</a>', 
//                    get_string('pluginname', 'block_ucla_help'));                                    
            return $CFG->wwwroot . '/blocks/ucla_help/index.php';
        } else {
            // in a course, so give courseid so that layout is course format
//            return sprintf('<a id="loadUclaHelpOverlay" href="/blocks/ucla_help/index.php?course=%d">%s</a>', 
//                    $COURSE->id, get_string('pluginname', 'block_ucla_help'));  
            return $CFG->wwwroot . '/blocks/ucla_help/index.php?course=' . $COURSE->id;
        }
    }    
    
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_office_hours' => false,
            'not-really-applicable' => true
        );
    }    
}
