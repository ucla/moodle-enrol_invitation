<?php

require_once($CFG->libdir . '/grade/grade_item.php');

class ucla_grade_item extends grade_item {

    public function __construct($params = NULL) {
        global $DB;

        $userid = $params['userid'];
        unset($params['userid']);
        
        parent::__construct($params);

        $this->_course = $DB->ger_record('course', array('id' => $this->courseid));
        $this->_user = $DB->ger_record('user', array('id' => $userid));
    }

    public function webservice_handler() {
        global $CFG;
        
        if (!empty($CFG->gradebook_send_updates)) {
            
            if(empty($this->itemmodule) || empty($this->iteminstance)) {
                return true;
            }
            
            // We don't want to send grades for 'course' or 'category' itemtypes
            // Only for modules...
            // grade_item->itemtype -- we're only sending 'mod' grades 
            if (isset($this->grade_item) && isset($this->grade_item->itemtype) &&
                ($this->grade_item->itemtype === 'course' || $this->grade_item->itemtype === 'category')) {
                
                return true;
            } else {

                $result = $this->send_to_myucla();
                
                return ($result === grade_reporter::SUCCESS);
            }
        }
        
        return true;;
    }
    
    protected function get_transactionid() {
        global $DB;
        
        $history = $DB->get_records($this->table . '_history', 
                array('oldid' => $this->id), 'id DESC', 'id', 0, 1);
        return array_shift($history)->id;
    }

    function send_to_myucla() {
        
        $course_obj = $this->_course;

        // Want the transaction ID to be the last record in the _history table
        $transactionid = grade_reporter::get_transactionid($this->table, $this->id);
        $log = grade_reporter::prepare_log($this->_course->id, $this->iteminstance, $this->itemmodule, $this->_user->id);

        // Get crosslisted SRS, and send update for each SRS
        $courses = ucla_get_course_info($course_obj->id);

        foreach ($courses as $c) {
            $param = $this->make_myucla_parameters($c, $transactionid);

            if ($param) {
                try {
                    $client = grade_reporter::get_instance();

                    $result = $client->moodleItemModify($param);

                    if (!$result->moodleItemModifyResult->status) {
                        throw new Exception($result->moodleItemModifyResult->message);
                    }
                    
                    $log['action'] = get_string('itemsuccess', 'local_gradebook');
                    $log['info'] = $result->moodleItemModifyResult->message;
                    
                    grade_reporter::add_to_log($log);
                    
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
        }
        
        return grade_reporter::SUCCESS;
    }


    function make_myucla_parameters($course, $transactionid) {
        global $CFG;
        
        $course_obj = $this->_course;
        $user_obj = $this->_user;

        if (empty($user_obj->idnumber) && empty($CFG->gradebook_debugging)) {
            return false;
        }

        $parentcategory = $this->get_parent_category();
        $categoryname = empty($parentcategory) ? '' : $parentcategory->fullname;

        //In a crosslisted course, the parentcourse's id is required
        $url = $CFG->wwwroot . '/grade/edit/tree/item.php?courseid=' .
                $course_obj->id . '&id=' . $this->id .
                '&gpr_type=edit&gpr_plugin=tree&gpr_courseid=' . $course_obj->id;

        //Need the individual child course
        if (!$course || !is_string($course->subj_area) || !is_string($course->crsidx)
                || !is_string($course->secidx)) {
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
                'itemDue' => empty($this->locktime) ? null : $this->locktime,
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
                'userUID' => empty($user_obj->idnumber) ? '000000000' : $user_obj->idnumber,
                'userName' => "$user_obj->firstname $user_obj->lastname",
                'userIpAddress' => empty($user_obj->lastip) ? '0.0.0.0' : $user_obj->lastip,
                'moodleTransactionID' => $transactionid,
            )
        );
    }

}