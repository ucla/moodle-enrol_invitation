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
 * Helper class to aid our modifications for the database enrolment plugin.
 *
 * This plugin contains the methods and logic needed to modify the database
 * enrollment plugin to support UCLA's unique way of mapping Registrar data
 * to Moodle courses and roles.
 *
 * @package    enrol_database
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot .'/user/lib.php');

/**
 * Helper class for database enrolment plugin implementation.
 * @author  Rex Lorenzo - based on code by Yangmun Choi
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_enrollment_helper {

    /**
     * Translate results from the stored procedure "ccle_roster_class" to
     * fields expected by Moodle.
     *
     * @param progress_trace $trace
     * @param array $regdata             Data row from "ccle_roster_class" SP.
     * @param string $remoteuserfield   Config enrol_database|remoteuserfield.
     *
     * @return array
     */
    public function translate_ccle_roster_class(progress_trace $trace,
            array $regdata, string $remoteuserfield) {
        // Name of the student in “LAST, FIRST MIDDLE” format.
        $names = explode(',', trim($regdata['full_name_person']));

        if (empty($names)) {
            $trace->output('WARNING: Found user with no name from ' .
                    'ccle_roster_class: '.implode(', ', array_keys($regdata)));
            $names[0] = '';
            $firstmiddle = '';
        } else if (empty($names[1])) {
            // No first name.
            $firstmiddle = '';
        } else {
            // Might have MIDDLE name data.
            $firstmiddle = $names[1];
        }

        return array(
            $remoteuserfield    => $regdata['stu_id'],
            'firstname'         => $firstmiddle,
            'lastname'          => $names[0],
            'email'             => $regdata['ss_email_addr'],
            'username'          => $this->normalize_bolid($regdata['bolid'])
        );
    }

    /**
     * Translate results from the stored procedure "ccle_course_instructorsget"
     * to fields expected by Moodle.
     *
     * @param progress_trace $trace
     * @param array $regdata            Row from "ccle_course_instructorsget".
     * @param string $remoteuserfield   Config enrol_database|remoteuserfield.
     *
     * @return array
     */
    public function translate_ccle_course_instructorsget(progress_trace $trace,
            array $regdata, string $remoteuserfield) {
        return array(
            $remoteuserfield    => $regdata['ucla_id'],
            'firstname'         => $regdata['first_name_person'],
            'lastname'          => $regdata['last_name_person'],
            'email'             => $regdata['ursa_email'],
            'username'          => $this->normalize_bolid($regdata['bolid'])
        );
    }

    /**
     * Appends '@ucla' to the 'bolid' field from Registrar data.
     *
     * @param string $bolid
     * @return string           Returns an empty string in bolid is empty.
     */
    public function normalize_bolid(string $bolid) {
        if (!empty($bolid)) {
            return $bolid . '@ucla.edu';
        }
        return '';
    }

}
