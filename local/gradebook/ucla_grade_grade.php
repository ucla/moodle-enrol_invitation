<?php

require_once($CFG->libdir . '/grade/grade_grade.php');

class ucla_grade_grade extends grade_grade {
    /**
     * Returns the courseid for the given grade object
     */
    public function get_courseid() {
        if (empty($this->grade_item)) {
            $this->load_grade_item();
        }
        return $this->grade_item->courseid;
    }

    /**
     * Handler for grade updates.  This should only be called by a grade udpate
     * event handler.  
     * 
     * @global type $CFG
     * @return boolean 
     */
    public function webservice_handler() {
        global $CFG;
        
        if (!empty($CFG->gradebook_send_updates)) {            
            $result = $this->send_to_myucla();

            if ($result !== grade_reporter::SUCCESS &&
                    $result !== grade_reporter::NOTSENT) {
                // report failure if there was a problem on MyUCLA's end
                // NOTSENT is if a grade item isn't suppose to be sent via
                // processing on our end
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validates grade, queries for student data, and then sends info to the
     * MyUCLA gradebook service.
     *
     * @global object $DB
     * @global object $CFG
     * @return int          Returns a status value
     */
    public function send_to_myucla() {
        global $DB, $CFG;

        // don't push certain grade types
        if ($this->grade_item->itemtype === 'course' ||
                $this->grade_item->itemtype === 'category') {
            return grade_reporter::NOTSENT;
        }

        // Get crosslisted SRS list
        $courses = ucla_get_course_info($this->get_courseid());

        if (empty($courses)) {
            // course was not a srs course, so skip it
            return grade_reporter::NOTSENT;
        }

        $srs_list = implode(',', array_map(function($o) {return $o->srs;}, $courses));
        $term = $courses[0]->term;

        // If this is a crosslisted course, find out through what SRS he/she 
        // enrolled in.  This info is in the ccle_roster_class_cache table
        $sql = "SELECT  urc.id, urc.term, urc.srs,
                        urc.subj_area, urc.crsidx, urc.secidx,
                        u.idnumber as uidstudent
                FROM    {ccle_roster_class_cache} crcc, 
                        {ucla_reg_classinfo} AS urc,
                        {user} AS u
                WHERE   u.id = $this->userid AND
                        u.idnumber = crcc.stu_id AND
                        urc.term = crcc.param_term AND
                        urc.srs = crcc.param_srs AND
                        crcc.param_term = '$term' AND
                        crcc.param_srs IN ($srs_list)";
        $enrolledcourses = $DB->get_records_sql($sql);

        // We should only get one record, but we should handle multiple
        if (empty($enrolledcourses)) {
            // user is most likely the Instructor or TA or manually added guest
            // just skip user
            return grade_reporter::NOTSENT;
        } 

        // Want the transaction ID to be the last record in the _history table
        list($transactionid, $loggeduser) =
                grade_reporter::get_transactionid($this->table, $this->id);

        $transaction_user = grade_reporter::get_transaction_user($this,
                        $loggeduser);

        $log = grade_reporter::prepare_log($this->get_courseid(),
                $this->grade_item->iteminstance,
                $this->grade_item->itemmodule, $transaction_user->id);

        foreach ($enrolledcourses as $course) {
            if (empty($course->uidstudent)) {
                // ignore users with no uid
                return grade_reporter::NOTSENT;
            }

            $param = $this->make_myucla_parameters($course, $transactionid);
            try {
                //Connect to MyUCLA and send data
                $client = grade_reporter::get_instance();
                $result = $client->moodleGradeModify($param);

                // Check for status error
                if (!$result->moodleGradeModifyResult->status) {
                    throw new Exception($result->moodleGradeModifyResult->message);
                }

                // Success is logged conditionally
                if(!empty($CFG->gradebook_log_success)) {
                    $log['action'] = get_string('gradesuccess', 'local_gradebook');
                    $log['info'] = $result->moodleGradeModifyResult->message;
                    grade_reporter::add_to_log($log);
                }                

            } catch (SoapFault $e) {
                // Catch a SOAP failure
                $log['action'] = get_string('connectionfail', 'local_gradebook');
                $log['info'] = get_string('gradeconnectionfailinfo', 'local_gradebook', $this->id);

                grade_reporter::add_to_log($log);
                return grade_reporter::CONNECTION_ERROR;
            } catch (Exception $e) {
                // Catch a 'status' failure
                $log['action'] = get_string('gradefail', 'local_gradebook');
                $log['info'] = get_string('gradefailinfo', 'local_gradebook', $this->id);

                grade_reporter::add_to_log($log);
                return grade_reporter::BAD_REQUEST;
            }
        }
        
        return grade_reporter::SUCCESS;
    }

    /**
     * Creates array of values to be used when creating message about
     * grade_grades to MyUCLA
     *
     * @global object $CFG
     * @param object $course
     * @param int $transactionid
     * @return array    Returns an array to create the SOAP message that will
     *                  be sent to MyUCLA
     */
    private function make_myucla_parameters($course, $transactionid) {
        global $CFG;
        
        // person who made/changed grade
        $transaction_user = grade_reporter::get_transaction_user($this);
        
        // Trim long feedback
        $comment = '';
        if (isset($this->feedback)) {
            if (strlen($this->feedback) > grade_reporter::MAX_COMMENT_LENGTH) {
                $comment = trim(substr($this->feedback, 0,
                        grade_reporter::MAX_COMMENT_LENGTH)) .
                        get_string('continue_comments', 'local_gradebook');
            } else {
                $comment = trim($this->feedback);
            }
        }
        
        // Set variables to notify deletion
        if(!empty($this->deleted)) {
            $this->finalgrade = null;
            $this->feedback = 'Deleted';
        }

        //Create array with all the parameters and return it
        return array(
            'mInstance' => array(
                'miID' => $CFG->gradebook_id,
                'miPassword' => $CFG->gradebook_password
            ),
            'mGrade' => array(
                'gradeID' => $this->id,
                'itemID' => $this->itemid,
                'term' => $course->term,
                'subjectArea' => $course->subj_area,
                'catalogNumber' => $course->crsidx,
                'sectionNumber' => $course->secidx,
                'srs' => $course->srs,
                'uidStudent' => $course->uidstudent,
                'viewableGrade' => $this->finalgrade,
                'comment' => $comment,
                'excused' => $this->excluded != '0'
            ),
            'mTransaction' => array(
                'userUID' => empty($transaction_user->idnumber) ?
                    '000000000' : $transaction_user->idnumber,
                'userName' => fullname($transaction_user, true),
                'userIpAddress' => empty($transaction_user->lastip) ?
                    '0.0.0.0' : $transaction_user->lastip,
                'moodleTransactionID' => $transactionid,
            )
        );
    }
}
