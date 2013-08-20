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
 * Generator class to help in the writing of unit tests for the local_ucla
 * plugin.
 *
 * @copyright 2013 UC Regents
 * @package   local_ucla
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->libdir . '/testing/generator/data_generator.php');

/**
 * local_ucla data generator
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  UC Regents
 */
class local_ucla_generator extends testing_data_generator {
    /** @var array list some subject areas */
    public $subject_areas = array('E&S SCI',
                                  'LBR&WS',
                                  'WOM STD',
                                  'COMM ST',
                                  'SPAN',
                                  'DIS STD',
                                  'BIOINFO',
                                  'ASIA AM',
                                  'FILM TV',
                                  'GENDER');

    /** @var array list some catalog numbers */
    public $course_numbers = array('193-28',
                                   '23L-7',
                                   '258-1',
                                   '174B-1',
                                   '31A-4',
                                   '246B-1',
                                   '198-15',
                                   '375-4',
                                   '31B-2',
                                   '246-1');

    /** @var array list some srs numbers */
    public $srs_numbers = array('324427200',
                                '141645200',
                                '324756200',
                                '662027200',
                                '596620218',
                                '196570200',
                                '662250200',
                                '662241200',
                                '196794200',
                                '587144200');

    /** @var array list some terms */
    public $terms = array('12S', '121', '12F', '13W', '13S');

    /**
     * Given an array for a course, generate given class. Given course array
     * may or may not contain the following keys:
     *
     * term, srs, department, course
     *
     * @throws dml_exception
     *
     * @param array $courses    If not passed, then will randomly create a
     *                          course that may or may not be a crosslist. If an
     *                          array is passed and some keys are not specified,
     *                          then those missing elements will be randomly
     *                          generated. If multiple course records are given,
     *                          then it will join all those courses as a
     *                          crosslist.
     *
     * @return array            Returns an array of ucla_request_classes entries
     */
    public function create_class($courses = null) {
        global $DB;

        $course_to_create = array();

        // is this course cross-listed?
        if (empty($courses)) {
            // course has 25% chance to be crosslisted with 2-5 crosslists
            if (rand(1,4) == 4) {
                $is_crosslisted = true;
                // this is going to be crosslisted, so figure out how many
                // crosslists to make
                $num_crosslists = rand(2,4);
                for ($i=1; $i<=$num_crosslists; $i++) {
                    $course_to_create[] = array();
                }
            } else {
                $course_to_create[] = array();
            }
        } else if (isset($courses[0]) && is_array($courses[0])) {
            // crosslist found
            $course_to_create = $courses;
        } else {
            // need to be in an array
            $course_to_create[] = $courses;
        }
        
        // get proper setid to use when inserting records into request table
        $setid = $DB->get_field('ucla_request_classes',
                'MAX(setid) + 1 AS max_setid', array());

        // generate any missing keys and try to insert records
        $first_entry = true;
        $courseid = null;
        foreach ($course_to_create as $course) {
            // make sure that term/srs is unique
            if (isset($course['term']) && isset($course['srs'])) {
                if ($DB->record_exists('ucla_request_classes',
                        array('term' => $course['term'],
                              'srs' => $course['srs']))) {
                    // cannot recover from a user specified term/srs dup
                    $a = sprintf('term = %s and srs = %s', $course['term'],
                            $course['srs']);
                    throw new dml_exception('duplicatefieldname', $a);
                }
            }

            // else we need to automatically generate either term/srs
            $num_tries = 0;
            $srs = null;
            $term = null;
            do {
                if (empty($course['term'])) {
                    $max = count($this->terms);
                    $term = $this->terms[rand(0, $max-1)];
                } else {
                    $term = $course['term'];
                }
                if (empty($course['srs'])) {
                    $max = count($this->srs_numbers);
                    $srs = $this->srs_numbers[rand(0, $max-1)];
                } else {
                    $srs = $course['srs'];
                }

                // will run into infinite loop if we tried to automatically
                // generate 5*10 courses
                if ($num_tries > 50) {
                    $a = sprintf('term = %s and srs = %s', $term, $srs);
                    throw new dml_exception('duplicatefieldname', $a);
                }
                ++$num_tries;
            } while ($DB->record_exists('ucla_request_classes',
                        array('term' => $term, 'srs' => $srs)));

            $course['term'] = $term;
            $course['srs'] = $srs;

            // next generate subject area/course number
            $auto_gen_shortname = false;
            if (empty($course['department'])) {
                $max = count($this->subject_areas);
                $course['department'] = $this->subject_areas[rand(0, $max-1)];
                $auto_gen_shortname = true;
            }
            if (empty($course['course'])) {
                $max = count($this->course_numbers);
                $course['course'] = $this->course_numbers[rand(0, $max-1)];
                $auto_gen_shortname = true;
            }

            // now insert courses

            // always take first entry as the hostcourse
            if ($first_entry) {
                $course['hostcourse'] = 1;
                $course['setid'] = $setid;
                $course['action'] = 'built';
                
                // <term>-<department><course>
                $shortname = sprintf('%s-%s%s', $course['term'],
                        $course['department'], $course['course']);
                // Remove spaces and ampersands
                $shortname = preg_replace('/[\s&]/', '', $shortname);

                // NOTE: might have dup shortnames, so if we are autogenerating
                // them, then just append an int to the end
                $num_tries = 0;
                if ($auto_gen_shortname) {
                    $tmp_shortname = $shortname;
                    while ($DB->record_exists('course', 
                            array('shortname' => $tmp_shortname))) {
                        $tmp_shortname = $shortname . '-' . $num_tries;
                        if ($num_tries > 50) {
                            $a = sprintf('term = %s and srs = %s', $term, $srs);
                            throw new dml_exception('duplicatefieldname', $a);
                        }
                        ++$num_tries;
                    }
                    $shortname = $tmp_shortname;
                }

                // create shell course
                $created_course = $this->create_course(
                        array('shortname' => $shortname));
                $courseid = $created_course->id;

                $first_entry = false;
            } else {
                $course['hostcourse'] = 0;
            }
            $course['courseid'] = $courseid;

            // save request
            $DB->insert_record('ucla_request_classes', $course);
        }

        // finished creating courses, so return array of created course requests
        return ucla_map_courseid_to_termsrses($courseid);
    }

    /**
     * Create the roles needed to do enrollment. Note, that these roles will not
     * have the same capabilities as the real roles, they are just roles with
     * the same name as needed.
     */
    public function create_ucla_roles() {
        $roles[] = array('name' => 'Instructor',
                         'shortname' => 'editinginstructor',
                         'archetype' => 'editingteacher');
        $roles[] = array('name' => 'Supervising Instructor',
                         'shortname' => 'supervising_instructor',
                         'archetype' => 'editingteacher');
        $roles[] = array('name' => 'Teaching Assistant (admin)',
                         'shortname' => 'ta_admin',
                         'archetype' => 'editingteacher');
        $roles[] = array('name' => 'TA Instructor',
                         'shortname' => 'ta_instructor',
                         'archetype' => 'editingteacher');
        $roles[] = array('name' => 'Teaching Assistant',
                         'shortname' => 'ta',
                         'archetype' => 'student');
        $roles[] = array('name' => 'Student Facilitator',
                         'shortname' => 'studentfacilitator',
                         'archetype' => 'editingteacher');

        foreach ($roles as $role) {
            create_role($role['name'], $role['shortname'], '', $role['archetype']);
        }
    }
}