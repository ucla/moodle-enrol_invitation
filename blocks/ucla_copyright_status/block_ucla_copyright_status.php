<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/ucla_copyright_status/lib.php');

class block_ucla_copyright_status extends block_base {
    
    public function init(){
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
     *  This will create a link to the ucla video furnace page.
     **/
    static function get_action_link($courseid) {
        global $CFG;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_copyright_status/view.php', array('courseid' => $courseid));
    }
}

