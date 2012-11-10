<?php

require_once($CFG->libdir . '/grade/grade_item.php');

class ucla_grade_item extends grade_item {

    public function __construct($params = NULL) {
        global $DB;

        $userid = $params['userid'];
        unset($params['userid']);
        
        parent::__construct($params);

        $this->_course = $DB->get_record('course', array('id' => $this->courseid));
        $this->_user = $DB->get_record('user', array('id' => $userid));
    }

    public function webservice_handler() {
        global $CFG;
        
        if (!empty($CFG->gradebook_send_updates)) {
            
            // We don't want to send grades for 'course' or 'category' itemtypes
            // Only for modules...
            // grade_item->itemtype -- we're only sending 'mod' grades 
            if (isset($this->grade_item) && isset($this->grade_item->itemtype) &&
                ($this->grade_item->itemtype === 'course' || $this->grade_item->itemtype === 'category')) {
                
                return true;
            } else {

                $result = $this->send_to_myucla();
                
                switch($result) {
                    case grade_reporter::SUCCESS:
                        return true;
                    case grade_reporter::BAD_REQUEST:
                    case grade_reporter::CONNECTION_ERROR:
                        return false;
                }
            }
        }

    }
    
    protected function get_transactionid() {
        global $DB;
        
        $history = $DB->get_records($this->table . '_history', 
                array('oldid' => $this->id), 'id DESC', 'id', 0, 1);
        return array_shift($history)->id;
    }

    function send_to_myucla() {
        
        $course = $this->_course;

        // Want the transaction ID to be the last record in the _history table
        $transactionid = $this->get_transactionid();

        // Get crosslisted SRS, and send update for each SRS
        $courses = ucla_get_course_info($course->id);

        foreach ($courses as $c) {
            $param = $this->make_myucla_parameters($c, $transactionid);

            if (is_array($param)) {
                try {
                    $client = grade_reporter::get_instance();

                    $result = $client->moodleItemModify($param);
                    
                    if (!$result->moodleItemModifyResult->status) {
                        throw new Exception($result->moodleItemModifyResult->message);
                    }
                } catch (SoapFault $e) {

                    return grade_reporter::CONNECTION_ERROR;
                } catch (Exception $e) {

                    return grade_reporter::BAD_REQUEST;
                }
            }
        }
        
        return grade_reporter::SUCCESS;
    }


    function make_myucla_parameters($childcourse, $transactionid) {
        global $CFG;
        
        $course = $this->_course;
        $user = $this->_user;

        if (empty($user->idnumber) && empty($CFG->gradebook_debugging)) {
            return false;
        }

        $parentcategory = $this->get_parent_category();
        $categoryname = empty($parentcategory) ? '' : $parentcategory->fullname;

        //In a crosslisted course, the parentcourse's id is required
        $url = $CFG->wwwroot . '/grade/edit/tree/item.php?courseid=' .
                $course->id . '&id=' . $this->id .
                '&gpr_type=edit&gpr_plugin=tree&gpr_courseid=' . $course->id;

        //Need the individual child course
        if (!$childcourse || !is_string($childcourse->subj_area) || !is_string($childcourse->crsidx)
                || !is_string($childcourse->secidx)) {
            return false;
        }

        //Create array with all the parameters and return it
        return array(
            'mInstance' => array(
                'miID' => $CFG->gradebook_id,
                'miPassword' => $CFG->gradebook_password
            ),
            'mItem' => array(
                'itemID' => intval($this->id),
                'itemName' => $this->itemname,
                'categoryID' => intval($this->categoryid),
                'categoryName' => $categoryname,
                'itemReleaseScores' => !($this->hidden),
                'itemDue' => $this->locktime != '0' ? intval($this->locktime) : null, //locktime is both string and int
                'itemURL' => $url,
                'itemComment' => isset($this->iteminfo) ? $this->iteminfo : '',
            ),
            'mClassList' => array(
                array(
                    'term' => $childcourse->term,
                    'subjectArea' => $childcourse->subj_area,
                    'catalogNumber' => $childcourse->crsidx,
                    'sectionNumber' => $childcourse->secidx,
                    'srs' => $childcourse->srs
                )
            ),
            'mTransaction' => array(
                'userUID' => $user->idnumber,
                'userName' => "$user->firstname $user->lastname",
                'userIpAddress' => $user->lastip,
                'moodleTransactionID' => substr($transactionid, 0, 255)
            )
        );
    }

}