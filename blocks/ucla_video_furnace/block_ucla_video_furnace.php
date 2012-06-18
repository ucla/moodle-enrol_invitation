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
require_once($CFG->dirroot.'/blocks/ucla_video_furnace/lib.php');


class block_ucla_video_furnace extends block_base {
    
    public function init(){
        $this->title = get_string('pluginname', 'block_ucla_video_furnace');
    }
    
	 /**
     *  This will create a link to the ucla video furnace page.
     **/
    static function get_action_link($courseid) {
        global $CFG;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_video_furnace/view.php', array('course_id' => $courseid));
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

        return get_action_link($this->course);
    }
    
    public function get_navigation_nodes($course) {
		global $DB;
        
        $courseid = $course->id; // course id from the hook function
        $coursefound = ucla_map_courseid_to_termsrses($courseid); // could return more than one course across terms
		$nodes = array();

        if (!empty($coursefound)){
			//get video list, if no video, do not show the "video furnace" link
			$condition_str = "";
			foreach ($coursefound as $k=>$course){
				$condition_str .= '(`term` = "'.$course->term. '" AND `srs`= "'. $course->srs. '") OR ';
			}
			$condition_str = substr($condition_str, 0, -4); 
			$videos = $DB->get_records_select('ucla_video_furnace', $condition_str);

			if (!empty($videos)){
				// Must hardcode the naming string since this is a static function
	            $nodes[] = navigation_node::create('Video Furnace', self::get_action_link($courseid)); 
			}
	    }
        return $nodes;
    }
 
}

