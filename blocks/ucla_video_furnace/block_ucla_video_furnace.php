<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');


class block_ucla_video_furnace extends block_base {
    
    public function init(){
        $this->title = get_string('pluginname', 'block_ucla_video_furnace');
    }
    
    public function get_content(){
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;        
        
        if (!isset($this->course)) {
            global $COURSE;
            $this->course = $COURSE;
        }

        return self::get_action_link($this->course);
    }
    
    public function get_navigation_nodes($course) {
		global $DB;
        
        $courseid = $course->id; // course id from the hook function
        $coursefound = ucla_map_courseid_to_termsrses($courseid); 
	   	//get video list, if no video, do not show the "video furnace" link
		foreach ($coursefound as $k=>$course){
			$videos = $DB->get_records_select('ucla_video_furnace', '`term` = "'. $course->term .'" AND `srs` = "'. $course->srs .'"');
		}
		$nodes = array();

        if (!empty($coursefound)&&!empty($videos)) {                            
            // Must hardcode the naming string since this is a static function
            $nodes[] = navigation_node::create('Video Furnace', self::get_action_link($courseid)); 
        }
        return $nodes;
    }
    
    /**
     *  This will create a link to the ucla video furnace page.
     **/
    static function get_action_link($courseid) {
        global $CFG;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_video_furnace/view.php', array('course_id' => $courseid));
    }

    /**
     *  Returns the applicable places that this block can be added.
     *  This block really cannot be added anywhere, so we just made a place
     *  up (hacky). If we do not do this, we will get this
     *  plugin_devective_exception.
     * @todo Not really sure if theres an equivalent to 'blocks-ucla_control_panel' => false, for this block.
     **/
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            //'blocks-ucla_control_panel' => false,
            'not-really-applicable' => true
        );
    }
		
}


//EOF
