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



class block_video_furnace extends block_base {
    
    public function init(){
        $this->title = get_string('plugin_name', 'block_video_furnace');
    }
    
    public function get_content(){
        if (!isset($this->course)) {
            global $COURSE;
            $this->course = $COURSE;
        }

        return self::get_action_link($this->course);
    }
    
    /**
     *  This will create a link to the control panel.
     **/
    static function get_action_link($course) {
        global $CFG;

        $courseid = $course->id;

        return new moodle_url($CFG->wwwroot . '/blocks/video_furnace/'
            . 'view.php', array('course_id' => $courseid));
    }
    
}


//EOF
