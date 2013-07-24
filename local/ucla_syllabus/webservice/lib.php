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
 * Class and function library for syllabus plugin.
 * 
 * @package     local_ucla_syllabus
 * @subpackage  webservice
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Syllabus webservice item class.
 * 
 * A class for containing the various requests that could be
 * encountered by our web service.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_ws_item {
    /** @var int Number of times a service will be requested before reporting an error. */
    const MAX_ATTEMPTS = 3;

    /** @var mixed Data that may be sent for use during a service request. */
    private $_data;

    /** @var mixed Criteria that must be met by/for a web service to complete. */
    private $_criteria;

    /** @var int Number of requests for service that have been attempted. */
    private $_attempt;

    /**
     * Create a Web Service object out of the given data.
     * 
     * @param mixed $record
     * @param mixed $criteria
     */
    public function __construct($record, $criteria) {
        $this->_data = $record;
        $this->_criteria = $criteria;
        $this->_attempt = 0;
    }

    /**
     * POST $payload to specified URL if the criteria matches.
     *
     * @param   object $payload
     * @return  bool, true if successful, false if we run out of tries
     */
    public function notify($payload) {

        if ($this->_match_criteria()) {

            // Attempt to POST at most MAX_ATTEMPTS times.
            while (self::MAX_ATTEMPTS > $this->_attempt) {

                if ($this->_post($payload)) {
                    return true;
                    break;
                }

                $this->_attempt++;
            }

            // If we kept trying and ran out of tries, then report.
            if ($this->_attempt == self::MAX_ATTEMPTS) {
                $this->_contact($payload);
                return false;
            }
        }

        return true;
    }

    /**
     * Sends an email to the specified recipient.
     *
     * @param   array $payload contains message, subject, and recipient
     * @return  bool, true if email sent successfully
     */
    private function _contact($payload) {

        // Send email message.
        $payload['service'] = $this->_data->url;

        $message = get_string('email_msg', 'local_ucla_syllabus', $payload);
        $subject = get_string('email_subject', 'local_ucla_syllabus');

        $to = $this->_data->contact;

        return ucla_send_mail($to, $subject, $message);
    }


    /**
     * Checks to see if all service criteria are met.
     * 
     * @return bool, true if met
     */
    private function _match_criteria() {
        return $this->_match_subject() || $this->_match_srs();
    }

    /**
     * Checks to see if the subject area data are within the criteria.
     * 
     * @return bool, true if they are
     */
    private function _match_subject() {
        if (!empty($this->_data->subjectarea) && !empty($this->_criteria['subjectarea'])) {
            return intval($this->_data->subjectarea) === intval($this->_criteria['subjectarea']);
        }

        return false;
    }

    /**
     * Check to see if the SRS data is within the criteria.
     * 
     * @return bool, true if they are
     */
    private function _match_srs() {
        if (!empty($this->_data->leadingsrs) && !empty($this->_criteria['srs'])) {
            return (strpos($this->_criteria['srs'], $this->_data->leadingsrs) === 0);
        }

        return false;
    }

    /**
     * Perform a curl request regarding the web service.
     * 
     * @param   object $payload
     * @return  bool, true if request is successful
     */
    private function _post($payload) {
        $ch = curl_init();

        $sig = '';

        // Encode token if needed.
        if (!empty($this->_data->token)) {
            $sig = $this->_hash_payload(base64_encode($this->_data->token));
        }

        $data = $payload;
        $data['algorithm'] = 'sha256';
        $data['token'] = $sig;

        // Set up curl POST.
        curl_setopt($ch, CURLOPT_URL, $this->_data->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute curl.
        $result = curl_exec($ch);

        curl_close($ch);

        // Verify that we got a 'success' message.
        if (strtolower(trim(substr($result, 0, 8))) === "success") {
            return true;
        }

        return false;
    }

    /**
     * Hashes the web service data.
     * 
     * @param   object $payload the data to be hashed
     * @return  string the encoded hash of payload
     */
    private function _hash_payload($payload) {
        $sig = hash_hmac('sha256', $payload, $this->_data->token);
        return base64_encode($sig);
    }
}

/**
 * Syllabus webservice manager class.
 * 
 * Class for managing a course's syllabus, including handling
 * any events.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_ws_manager {
    /** @var int Constant encoding for transfer action. */
    const ACTION_TRANSFER = 0;

    /** @var int Constant encoding for alert action. */
    const ACTION_ALERT = 1;

    /** @var int Constant encoding for successful status. */
    const STATUS_OK = 0;

    /** @var int Constant encoding for failed status. */
    const STATUS_FAIL = 1;

    /**
     * Handle an event action.  
     *
     * @param   object $event
     * @param   object $criteria
     * @param   object $payload
     * @return  bool
     */
    static public function handle($event, $criteria, $payload) {
        global $DB;

        $records = $DB->get_records('ucla_syllabus_webservice',
                array('enabled' => 1, 'action' => $event));

        $result = true;

        // Process actions.
        foreach ($records as $rec) {

            $notifications = new syllabus_ws_item($rec, $criteria);
            $result &= $notifications->notify($payload);
        }

        return $result;
    }

    /**
     * Setup a new course for the syllabus plugin.
     * 
     * @param   object $course
     * @return  array of $criteria and $payload
     */
    static public function setup($course) {
        global $DB;

        $srs = $course->srs;
        $term = $course->term;

        // TODO: put this in a single SQL statement.
        $classinfo = ucla_get_reg_classinfo($term, $srs);
        $subjarea = $DB->get_record('ucla_reg_subjectarea',
                array('subjarea' => $classinfo->subj_area));

        $criteria = array(
            'srs' => $srs,
            'subjectarea' => $subjarea->id,
        );

        $payload = array(
            'srs' => $srs,
            'term' => $term,
        );

        return array($criteria, $payload);
    }

    /**
     * Set up syllabus criteria.
     * 
     * Given a syllabus object, setup the criteria for which 
     * subscribers to the webservice will be notified and set up
     * the payload that they are expecting.
     *
     * @param   object $syllabus
     * @param   object $course
     * @return  array of $criteria and $payload 
     */
    static public function setup_transfer($syllabus, $course) {

        list($criteria, $payload) = self::setup($course);

        $file = $syllabus->stored_file;

        // Ugly way of getting the file path.
        $cr = new stdClass();
        $file->add_to_curl_request($cr, 'file');
        $path = $cr->_tmp_file_post_params['file'];

        $payload['file'] = $path;
        $payload['file_name_real'] = $file->get_filename();

        // Some organizations might need the filenamehash to locate the file.
        $filenamehash = $file->get_contenthash();
        $payload['file_name'] = $filenamehash;

        return array($criteria, $payload);
    }

    /**
     * Set up the alerts for a course.
     * 
     * @param   object $course
     * @return  array of $criteria and $payload
     */
    static public function setup_alert($course) {
        global $CFG;

        $hostcourse = ucla_map_termsrs_to_courseid($course->term, $course->srs);

        $criteria = array(
            'srs' => $course->srs,
            'subjectarea' => -1,
        );
        $payload = array(
            'srs' => $course->srs,
            'term' => $course->term,
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $hostcourse,
        );

        return array($criteria, $payload);
    }

    /**
     * Set up the deletion of a course.
     * 
     * @param   object $course
     * @return  array of $criteria and $payload
     */
    static public function setup_delete($course) {
        list($criteria, $payload) = self::setup($course);
        $payload['deleted'] = 'true';

        return array($criteria, $payload);
    }

    /**
     * Add a new subscription to the webservice.
     *
     * @param   object $data
     * @return  bool returns to escape function
     */
    static public function add_subscription($data) {
        global $DB;

        // If nothing to do, then skip it.
        if (empty($data->subjectarea) && empty($data->leadingsrs)) {
            return false;
        }

        // Enable by default.
        $data->enabled = 1;

        // Save record of the subscription.
        $DB->insert_record('ucla_syllabus_webservice', $data);
    }

    /**
     * Return list of events we're handling.
     * 
     * @return array
     */
    static public function get_event_actions() {
        $actions = array(
            self::ACTION_TRANSFER => get_string('action_transfer', 'local_ucla_syllabus'),
            self::ACTION_ALERT => get_string('action_alert', 'local_ucla_syllabus')
        );

        return $actions;
    }

    /**
     * Return list of subject areas.
     *
     * @return array 
     */
    static public function get_subject_areas() {
        global $DB;
        $records = $DB->get_records('ucla_reg_subjectarea', null, '', 'id, subj_area_full ');
        foreach ($records as &$r) {
            $r = ucwords(strtolower($r->subj_area_full));
        }
        return array_merge(array(0 => 'Select subject area'), $records);
    }

    /**
     * Return object containing list of web service subscriptions.
     * 
     * @return object result of records query
     */
    static public function get_subscriptions() {
        global $DB;
        return $DB->get_records('ucla_syllabus_webservice');
    }

    /**
     * Update the web service subscriptions.
     * 
     * @param object $record
     */
    static public function update_subscription($record) {
        global $DB;
        $DB->update_record('ucla_syllabus_webservice', $record);
    }

    /**
     * Delete a web service subscription.
     * 
     * @param int $id the subscription to delete
     */
    static public function delete_subscription($id) {
        global $DB;
        $DB->delete_records('ucla_syllabus_webservice', array('id' => $id));
    }

    /**
     * Checks if a given course is subscribed to syllabus web service.
     * 
     * @param int $courseid the course to check
     */
    static public function is_subscribed($courseid) {
        global $DB;

        // Retrive all the courses associated with this courseID.
        $courses = ucla_map_courseid_to_termsrses($courseid);

        $course = new stdClass();
        // Get SRS.
        foreach ($courses as $c) {
            if ($c->hostcourse) {
                $course->srs = $c->srs;
                $course->term = $c->term;
            }
        }

        // For instructional collab sites.
        if (empty($course->srs) || empty($course->term)) {
            return false;
        }

        // Get subject area.
        $query = "SELECT rs.id
                    FROM {ucla_reg_classinfo} AS urc
                    JOIN {ucla_reg_subjectarea} AS rs ON rs.subjarea = urc.subj_area
                   WHERE urc.srs = :srs AND urc.term = :term";

        $course->subjarea = $DB->get_field_sql($query,
                array('srs' => $course->srs, 'term' => $course->term));

        // Get all the web service subscribers.
        $subscribers = $DB->get_records('ucla_syllabus_webservice',
                array('enabled' => 1, 'action' => self::ACTION_TRANSFER));

        // Check if the course is subscribed.
        foreach ($subscribers as $s) {
            // Try to match by SRS.
            if (!empty($s->leadingsrs) && strpos($course->srs, $s->leadingsrs) === 0) {
                return true;
            }

            // Try to match subject area.
            if (!empty($s->subjectarea) && $course->subjarea === $s->subjectarea) {
                return true;
            }
        }

        return false;
    }
}
