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

defined('MOODLE_INTERNAL') || die;

function xmldb_voicetools_install() {
    global $CFG, $DB;

    if (!isset($CFG->voicetools_initialdisable) || empty($CFG->voicetools_initialdisable)) {
        $DB->set_field('modules', 'visible', 0, array('name'=>'voicetools'));  // Disable it by default
        set_config('voicetools_initialdisable', 1);
    }
    else if ($CFG->voicetools_initialdisable == 1 ){//we make sure that the voicetools module is disabled
        $DB->set_field('modules', 'visible', 0, array('name'=>'voicetools'));  // Disable it by default
    }
}
