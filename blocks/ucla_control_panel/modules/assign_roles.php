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

isset('MOODLE_INTERNAL') || die();

class ucla_cp_module_assign_roles extends ucla_cp_module {
    function __construct($course, $home=false) {
        global $CFG;

        $this->action = new moodle_url($CFG->wwwroot . '/enrol/users.php',
            array('id' => $course->id));

        $this->home = $home;

        $this->shortname = $course->shortname;

        parent::__construct();
        
        if ($home) {
            $this->item_name .= '_master';
        }
    }

    function autotag() {
        return array('ucla_cp_mod_advanced');
    }

    function autocap() {
        return 'moodle/course:update';
    }
    
    function get_key() {
        if ($this->home) {
            $namer = 'assign_roles_0_' . $this->shortname;
        } else {
            $namer = 'assign_roles_1_' . $this->shortname;
        }

        return $namer;
    }
}
