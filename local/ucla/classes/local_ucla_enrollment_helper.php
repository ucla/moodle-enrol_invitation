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
require_once($CFG->dirroot . '/user/lib.php');
ucla_require_registrar();

/**
 * Helper class for database enrolment plugin implementation.
 * @author  Rex Lorenzo - based on code by Yangmun Choi
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_enrollment_helper {

    /**
     * If set, is course that we are processing.
     * @var int
     */
    private $courseid = null;

    /**
     * Value of enrol_database|remoterolefield.
     * 
     * @var string
     */
    protected $remoterolefield;

    /**
     * Value of enrol_database|remoteuserfield.
     *
     * @var string
     */
    protected $remoteuserfield;

    /**
     * If set, are terms that we are processing.
     * @var int
     */
    private $terms = null;

    /**
     * Used to output any messages.
     *
     * @var progress_trace
     */
    protected $trace;

    /**
     * Constructor.
     *
     * @param progress_trace $trace
     * @param enrol_database_plugin $enroldatabase
     * @param int|array $termsorcourseid
     */
    public function __construct(progress_trace $trace,
            enrol_database_plugin $enroldatabase, $termsorcourseid = null) {

        $this->trace = $trace;

        // Store needed config variables.
        $this->remoterolefield = $enroldatabase->get_config('remoterolefield');
        $this->remoteuserfield = $enroldatabase->get_config('remoteuserfield');

        $this->set_run_parameter($termsorcourseid);
    }

    /**
     *
     * @param array $requestclasss Array of results from ucla_request_classes
     *
     * @return array
     */
    public function get_enrollments(array $requestclassses) {
        $instructors = $this->get_instructors($requestclassses);
        $students = array();
        return array_merge($instructors, $students);
    }

    /**
     * Calls stored procedure ccle_courseinstructorsget, translates results,
     * and then does the role mapping to return an array of instructors and
     * their roles.
     *
     * @param array $requestclasss Array of results from ucla_request_classes
     *
     * @return array
     */
    public function get_instructors(array $requestclasses) {
        $enrolments = array();

        foreach ($requestclasses as $requestclass) {
            $subjarea = $requestclass->department;
            $courseid = $requestclass->courseid;

            // Query registrar for instructors.
            $instructors = $this->query_registrar('ccle_courseinstructorsget',
                    $requestclass->term, $requestclass->srs);

            // Need to create mapping of role code to primary/secondary
            // sections.
            $otherroles = array();
            foreach ($instructors as $index => $instructor) {
                // If srs is different than parameters given for
                // ccle_courseinstructorsget, then it is a secondary srs.
                $sectiontype = 'primary';
                if ($instructor['srs'] != $requestclass->srs) {
                    $sectiontype = 'secondary';
                }

                $primaryccode = $instructor['role'];
                $otherroles[$sectiontype][] = $primaryccode;
                $instructors[$index]['prof_codes'][$sectiontype][] = $primaryccode;
            }

            // Map instructors to their appropiate role in the course.
            foreach ($instructors as $instructor) {
                // Skip "THE STAFF" or "TA".
                if (is_dummy_ucla_user($instructor['ucla_id'])) {
                    continue;
                }

                try {
                    $localrole = role_mapping(
                            $instructor['prof_codes'], $otherroles, $subjarea
                    );
                } catch (moodle_exception $me) {
                    // Cannot find a good role map, so skip processing.
                    $this->trace('Could not get good mapping for ' .
                            implode('|', $instructor), 1);
                    continue;
                }

                $user = $this->translate_ccle_course_instructorsget($instructor);
                $user[$this->remoterolefield] = $localrole;
                $enrolments[$courseid][] = $user;
            }
        }

        return $enrolments;
    }

    /**
     * Returns an array of courseids mapped to a boolean that is expected by the
     * enrol_database plugin when it is checking which courses to add the
     * enrollment plugin.
     */
    public function get_external_enrollment_courses() {
        global $DB;
        $retval = array();

        if (empty($this->terms)) {
            throw new Exception('No terms to query');
        }

        $records = $DB->get_records_list('ucla_request_classes', 'term', $this->terms);
        foreach ($records as $record) {
            $retval[$record->courseid] = true;
        }

        return $retval;
    }

    /**
     * Appends '@ucla' to the 'bolid' field from Registrar data.
     *
     * @param string $bolid
     * @return string           Returns an empty string in bolid is empty.
     */
    public function normalize_bolid($bolid) {
        if (!empty($bolid)) {
            return $bolid . '@ucla.edu';
        }
        return '';
    }

    /**
     * Queries given stored procedure and filters results.
     *
     * Function is basically a wrapper for run_registrar_query, to make unit
     * testing stubbing possible.
     *
     * @param string $sp        Stored procedure to call.
     * @param string $term
     * @param string $srs
     *
     * @return array            Returns array of successful results.
     */
    public function query_registrar($sp, $term, $srs) {
        $results = registrar_query::run_registrar_query(
                        $sp, array($term, $srs), false
        );

        // Log any failed results.
        if (!empty($results[registrar_query::failed_outputs])) {
            $this->trace(sprintf('%d failed results from %s (%s, %s)',
                    count($results[registrar_query::failed_outputs]), $sp,
                    $term, $srs));
        }

        return $results[registrar_query::query_results];
    }

    /**
     * Sets what type of enrollment we are doing.
     *
     * @param int|array $termsorcourseid
     */
    public function set_run_parameter($termsorcourseid = null) {
        $this->courseid = null;
        $this->terms = null;
        if (is_array($termsorcourseid)) {
            // Really a list of terms to process.
            $this->terms = $termsorcourseid;
        } else if (is_int ($termsorcourseid)) {
            $this->courseid = $termsorcourseid;
        }
    }

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
    public function translate_ccle_roster_class(array $regdata) {
        // Name of the student in “LAST, FIRST MIDDLE” format.
        $names = explode(',', trim($regdata['full_name_person']));

        if (empty($names)) {
            $this->trace->output('WARNING: Found user with no name from ' .
                    'ccle_roster_class: ' . implode(', ', array_keys($regdata)));
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
            $this->remoteuserfield => $regdata['stu_id'],
            'firstname' => $firstmiddle,
            'lastname' => $names[0],
            'email' => $regdata['ss_email_addr'],
            'username' => $this->normalize_bolid($regdata['bolid'])
        );
    }

    /**
     * Translate results from the stored procedure "ccle_course_instructorsget"
     * to fields expected by Moodle.
     *
     * @param array $regdata            Row from "ccle_course_instructorsget".
     *
     * @return array
     */
    public function translate_ccle_course_instructorsget(array $regdata) {
        return array(
            $this->remoteuserfield => $regdata['ucla_id'],
            'firstname' => $regdata['first_name_person'],
            'lastname' => $regdata['last_name_person'],
            'email' => $regdata['ursa_email'],
            'username' => $this->normalize_bolid($regdata['bolid'])
        );
    }

}
