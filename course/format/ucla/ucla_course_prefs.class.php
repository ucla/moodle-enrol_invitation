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

/**
 * @copyright UCLA 2011
 * @author yangmungi@ucla.edu
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ucla
 * @subpackage format
 * @subpackage courseprefs
 *
 * This file should be included from the format:
 * See moodle/format/ucla/lib.php
 **/

defined('MOODLE_INTERNAL') || die();

class ucla_course_prefs {
    // This maintains the preferences
    private $preferences;

    private $courseid;

    // Constructor
    function __construct($courseid) {
        $this->courseid = $courseid;
        $this->preferences = 
            ucla_course_prefs::get_course_preferences($courseid);
    }

    function get_preference($preference, $default=false) {
        if (isset($this->preferences[$preference])) {
            return $this->preferences[$preference]->value;
        }

        return $default;
    }

    function set_preference($preference, $value, $commit=false) {
        if (!isset($this->preferences[$preference])) {
            $newpref = new StdClass();
            $newpref->name = $preference;
            $newpref->courseid = $this->courseid;
        } else {
            $newpref = $this->preferences[$preference];
        }
        
        $newpref->value = $value;
        $newpref->timestamp = time();

        $this->preferences[$preference] = $newpref;

        if ($commit) {
            $this->commit_one($preference);
        }
    }

    function commit() {
        foreach ($this->preferences as $pref => $obj) {
            $this->commit_one($pref);
        }
    }

    function commit_one($preference) {
        global $DB;

        if ($preference == null || !isset($this->preferences[$preference])) {
            return false;
        }

        $obj =& $this->preferences[$preference];
        
        if (isset($obj->id)) {
            $DB->update_record('ucla_course_prefs', $obj);
        } else {
            $obj->id = $DB->insert_record('ucla_course_prefs', $obj);
        }
    }

    /**
     *  Will return an Array of course preferences.
     *  
     *  @param int $courseid The id number of the course.
     *  @return Array The list of preferences.
     **/
    static function get_course_preferences($courseid) {
        global $DB;

        $cond_arr = array();
        $cond_arr['courseid'] = $courseid;

        $unindexed = $DB->get_records('ucla_course_prefs', $cond_arr);

        $indexed = array();
        foreach ($unindexed as $record) {
            $indexed[$record->name] = $record;
        }

        return $indexed;
    }
}
