<?php

require_once($CFG->libdir . '/grade/grade_item.php');

class ucla_grade_item extends grade_item {    
    /**
     * Only called by event handler
     * 
     * @global type $CFG
     * @return boolean 
     */
    public function webservice_handler() {
        global $CFG;
        
        if (!empty($CFG->gradebook_send_updates)) {
            
            // A grade item update will sometimes happen twice -- observed 
            // when clicking 'show' icon for module, but not when clicking 'hide'
            // When this happens a second time, these params are missing, and 
            // they are needed for logging purposes later...
            if(empty($this->itemmodule) || empty($this->iteminstance)) {
                return true;
            }

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
    function send_to_myucla() {
        global $CFG, $DB;
        
        // We don't want to send grades for 'course' or 'category' itemtypes
        // Only for modules...
        // grade_item->itemtype -- we're only sending 'mod' grades
        if ($this->itemtype === 'course' || $this->itemtype === 'category') {
            return grade_reporter::NOTSENT;
        }
        
        // Want the transaction ID to be the last record in the _history table
        list($transactionid, $loggeduser) =
                grade_reporter::get_transactionid($this->table, $this->id);

        // Get the user that made the last grade edit.  When called by the 
        // event handler, this will be stored in the $this->_user property
        $transaction_user = grade_reporter::get_transaction_user($this,
                        $loggeduser);

        $log = grade_reporter::prepare_log($this->courseid, $this->iteminstance,
                $this->itemmodule, $transaction_user->id);

        // Get crosslisted SRS, and send update for each SRS
        $courses = ucla_get_course_info($this->courseid);

        foreach ($courses as $c) {
            $param = $this->make_myucla_parameters($c, $transactionid);
            try {
                $client = grade_reporter::get_instance();
                $result = $client->moodleItemModify($param);
                if (!$result->moodleItemModifyResult->status) {
                    throw new Exception($result->moodleItemModifyResult->message);
                }

                // Success is logged conditionally
                if(!empty($CFG->gradebook_log_success)) {
                    $log['action'] = get_string('itemsuccess', 'local_gradebook');
                    $log['info'] = $result->moodleItemModifyResult->message;
                    grade_reporter::add_to_log($log);
                }

            } catch (SoapFault $e) {
                $log['action'] = get_string('connectionfail', 'local_gradebook');
                $log['info'] = get_string('itemconnectionfailinfo', 'local_gradebook', $this->id);

                grade_reporter::add_to_log($log);                    
                return grade_reporter::CONNECTION_ERROR;
            } catch (Exception $e) {
                $log['action'] = get_string('itemfail', 'local_gradebook');
                $log['info'] = get_string('itemfailinfo', 'local_gradebook', $this->id);

                grade_reporter::add_to_log($log);
                return grade_reporter::BAD_REQUEST;
            }
        }
        
        return grade_reporter::SUCCESS;
    }

    /**
     * Creates array of values to be used when creating message about
     * grade_items to MyUCLA
     *
     * @global object $CFG
     * @param object $course
     * @param int $transactionid
     * @return array    Returns an array to create the SOAP message that will
     *                  be sent to MyUCLA
     */
    function make_myucla_parameters($course, $transactionid) {
        global $CFG;

        // person who made/changed grade item
        $transaction_user = grade_reporter::get_transaction_user($this);

        $parentcategory = $this->get_parent_category();
        $categoryname = empty($parentcategory) ? '' : $parentcategory->fullname;

        // In a crosslisted course, the parentcourse's id is required
        $url = $CFG->wwwroot . '/grade/edit/tree/item.php?courseid=' .
                $this->courseid . '&id=' . $this->id .
                '&gpr_type=edit&gpr_plugin=tree&gpr_courseid=' . $this->courseid;

        //Create array with all the parameters and return it
        return array(
            'mInstance' => array(
                'miID' => $CFG->gradebook_id,
                'miPassword' => $CFG->gradebook_password
            ),
            'mItem' => array(
                'itemID' => $this->id,
                'itemName' => $this->itemname,
                'categoryID' => $this->categoryid,
                'categoryName' => $categoryname,
                'itemReleaseScores' => !($this->hidden),
// itemDue shouldn't be sent right now, but in the future change this to be
// the real due date for an activity
//                'itemDue' => empty($this->locktime) ? null : $this->locktime,
                'itemURL' => $url,
                'itemComment' => isset($this->iteminfo) ? $this->iteminfo : '',
            ),
            'mClassList' => array(
                array(
                    'term' => $course->term,
                    'subjectArea' => $course->subj_area,
                    'catalogNumber' => $course->crsidx,
                    'sectionNumber' => $course->secidx,
                    'srs' => $course->srs
                )
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
