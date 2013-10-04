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
 * Class to course problems with course sections that might be caused by Moodle
 * plugins.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class definition.
 */
class local_ucla_course_section_fixer {

    /**
     * Check if course has sections that are larger than numsections and have
     * an empty sequence and either empty null name or has a default name,
     * meaning that they should be safe to delete.
     *
     * @param stdClass $course
     * @return boolean          Returns false if it found an extra section,
     *                          otherwise false.
     */
    static public function check_extra_sections(stdClass $course) {
        global $DB;

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id));
        
        $numsections = course_get_format($course)->get_course()->numsections;
        foreach ($sections as $section) {
            if ($section->section > $numsections) {
                if (empty($section->sequence) && empty($section->summary)) {
                    // Make sure section name is also something not user
                    // modified. "Week" is something the UCLA format adds and
                    // "New section" is the default name the Modify sections
                    // tool uses.
                    if (empty($section->name) ||
                            strpos($section->name, 'Week ') === 0 ||
                            $section->name === 'New section') {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Checks if the order of sections in the DB is sequential.
     *
     * @param stdClass $course
     *
     * @return boolean          Returns true if no problems found, otherwise
     *                          returns false.
     */
    static public function check_section_order(stdClass $course) {
        global $DB;

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id), 'section', 'section');

        $current = 0;
        foreach ($sections as $section) {
            if ($section->section != $current) {
                return false;
            }
            ++$current;
        }

        return true;
    }

    /**
     * Counts the number of sections a course has and compares it against the
     * format's numsections value. Then will either just detect or fix the
     * number to match the database depending on the $adjustnum parameter.
     *
     * This method is defined differently than the other check/handle methods
     * because a user might not always want the numsection to equal the number
     * in the database. A use case of this is to add content to that section
     * and then reduce the number of sections so that the content is accessible
     * via links, but does not show up in the site menu block.
     *
     * @param stdClass $course
     * @param boolean $adjustnum    Default false. If true, will adjust
     *                              numsections value to match the number of
     *                              sections in the database.
     *
     * @return boolean              If $adjustnum is false, then will return
     *                              true if numsections is less than the number
     *                              of sections in the database. Otherwise
     *                              returns false.
     *                              If $adjustnum is true, then will return
     *                              false on an error, otherwise returns true.
     */
    static public function detect_numsections(stdClass $course, $adjustnum = false) {
        global $DB;

        $numsections = course_get_format($course)->get_course()->numsections;
        $actualcount = $DB->count_records('course_sections', array('course' => $course->id));

        // Adding 1 to $numsections, because it does not include Site info (0).
        if ($actualcount > ($numsections+1)) {
            // Course has more sections than specified in $numsections.
            if ($adjustnum) {
                // Fix problem. Again, remember that $numsections does not
                // include Site info (0).
                $newnumsections = ($actualcount - 1);

                // Do a slight sanity check to make sure we aren't setting this
                // to a crazy high value.
                $maxsections = get_config('moodlecourse', 'maxsections');
                if ($newnumsections > $maxsections) {
                    $newnumsections = $maxsections;
                }

                $data = array('numsections' => $newnumsections);
                course_get_format($course)->update_course_format_options($data);
                return true;
            } else {
                // Just report it.
                return true;
            }
        }

        return false;
    }

    /**
     * Find and fix any problems with a course's sections.
     *
     * If any problems were found and fixed, will rebuild the course cache.
     *
     * @param stdClass $course
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function fix_problems(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        if (!self::has_problems($course)) {
            return $retval;
        }

        // Fix problems and keep tally. 
        $methods = get_class_methods(get_called_class());
        foreach ($methods as $method) {
            if (strpos($method, 'handle_') === 0) {
                $results = self::$method($course);
                foreach ($results as $type => $num) {
                    $retval[$type] += $num;
                }
            }
        }

        // If any changes were made, then we need to rebuild the course cache.
        if ($retval['added'] > 0 || $retval['deleted'] > 0 || $retval['updated'] > 0) {
            rebuild_course_cache($course->id);
        }

        return $retval;
    }

    /**
     * Delete the extra sections a course might have if they are larger than
     * numsections and have an empty sequence, empty summary and either empty
     * null name or has a default name, meaning that they should be safe to
     * delete.
     *
     * @param stdClass $course
     *
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function handle_extra_sections(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id));
        $numsections = course_get_format($course)->get_course()->numsections;

        foreach ($sections as $section) {
            if ($section->section > $numsections) {
                if (empty($section->sequence) && empty($section->summary)) {
                    // Make sure section name is also something not user
                    // modified. "Week" is something the UCLA format adds and
                    // "New section" is the default name the Modify sections
                    // tool uses.
                    if (empty($section->name) ||
                            strpos($section->name, 'Week ') === 0 ||
                            $section->name === 'New section') {
                        // Safe to delete.
                        $DB->delete_records('course_sections',
                                array('id' => $section->id));
                        ++$retval['deleted'];
                    }
                }
            }
        }

        return $retval;
    }

    /**
     * Renumbers course sections so that they are sequential.
     *
     * @param stdClass $course
     * 
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function handle_section_order(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id), 'section', 'id,section');

        $current = 0;
        foreach ($sections as $section) {
            if ($section->section != $current) {
                // Section not in expected order, so renumber it.
                $section->section = $current;
                $DB->update_record('course_sections', $section);
                ++$retval['updated'];
            }
            ++$current;
        }

        // Do we need to adjust numsections?

        return $retval;
    }

    /**
     * Calls check methods and returns true if problems were found, otherwise
     * returns false.
     *
     * @param stdClass $course
     *
     * @return boolean
     */
    static public function has_problems(stdClass $course) {
        global $DB;

        // Call check methods and exit out if they all return true.
        $methods = get_class_methods(get_called_class());
        $noproblems = true;
        foreach ($methods as $method) {
            if (!$noproblems) {
                break;
            }
            if (strpos($method, 'check_') === 0) {
                $noproblems = self::$method($course);
            }
        }

        return !$noproblems;
    }
}
