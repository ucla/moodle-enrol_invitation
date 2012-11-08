<?php

require_once($CFG->libdir . '/grade/grade_grade.php');

class ucla_grade_grade extends grade_grade {

    public function __construct($params) {
        global $DB;
        
        $courseid = $params['courseid'];
        unset($params['courseid']);
        
        parent::__construct($params);

        $this->_course = $DB->ger_record('course', array('id' => $courseid));
        $this->_user = $DB->ger_record('user', array('id' => $this->userid));
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
                
                return ($result === grade_reporter::SUCCESS);
            }
        }
        
        return true;
    }
    
    public function send_to_myucla() {
        global $DB;
        
        $course_obj = $this->_course;
        
        // Want the transaction ID to be the last record in the _history table
        $transactionid = grade_reporter::get_transactionid($this->table, $this->id);
        $log = grade_reporter::prepare_log($this->_course->id, $this->grade_item->iteminstance, $this->grade_item->itemmodule, $this->_user->id);
        
        // Get crosslisted SRS list
        $courses = ucla_get_course_info($course_obj->id);
        $srs_list = implode(',', array_map(function($o) {return $o->srs;}, $courses));

        // If this is a crosslisted course, find out through what SRS he/she 
        // enroled through.  This info is in the ccle_roster_class_cache table
        $sql = "SELECT urc.id, param_term, param_srs, urc.term, urc.srs, urc.subj_area, urc.crsidx, urc.secidx
                    FROM {ccle_roster_class_cache} crcc
                    JOIN {user} AS u ON u.idnumber = stu_id
                    JOIN {ucla_reg_classinfo} AS urc ON urc.srs = param_srs
                WHERE u.id = $this->userid
                    AND urc.term = param_term
                    AND param_srs IN ($srs_list)";

        $enrolledcourses = $DB->get_records_sql($sql);

        // We should only have a single record 
        if (empty($enrolledcourses)) {
            error_log(get_string('nousers', 'local_gradebook'));
            
        } elseif (count($enrolledcourses) > 1) {
            error_log(get_string('badenrol', 'local_gradebook'));
            
        } else {
            // We should only have a single course when when we get here
            $course = array_pop($enrolledcourses);

            $param = $this->make_myucla_parameters($course, $transactionid);
            
            if ($param) {
                try {
                    
                    //Connect to MyUCLA and send data
                    $client = grade_reporter::get_instance();
                    $result = $client->moodleGradeModify($param);

                    // Check for status error
                    if (!$result->moodleGradeModifyResult->status) {
                        throw new Exception($result->moodleGradeModifyResult->message);
                    }
                    
                    $log['action'] = get_string('gradesuccess', 'local_gradebook');
                    $log['info'] = $result->moodleGradeModifyResult->message;
                    
                    grade_reporter::add_to_log($log);

                    return grade_reporter::SUCCESS;
                    
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

                    return  grade_reporter::BAD_REQUEST;
                    
                }
            }
        }
        
        return grade_reporter::DATABASE_ERROR;
    }
    
    private function make_myucla_parameters($course, $transactionid) {
        global $CFG, $DB;
        
        $user_obj = $this->_user;

        // idnumber => BOL ID
        // We want to allow dev environment to get through
        if (empty($user_obj->idnumber) && empty($CFG->gradebook_debugging)) {
            return false;
        }

        $comment = '';
        
        // Trim comment
        if (isset($this->feedback)) {
            $comment = trim(substr($this->feedback, 0, grade_reporter::MAX_COMMENT_LENGTH)) 
                    . get_string('continue_comments', 'local_gradebook');
        }
        
        // Set variables to notify deletion
        if(!empty($this->deleted)) {
            $this->finalgrade = null;
            $this->feedback = "Deleted";
        }

        $uidstudent = $DB->get_field('user', 'idnumber', array('id' => $this->userid));

        //Create array with all the parameters and return it
        return array(
            'mInstance' => array(
                'miID' => $CFG->gradebook_id,
                'miPassword' => $CFG->gradebook_password
            ),
            'mGrade' => array(
                'gradeID' => intval($this->id),
                'itemID' => intval($this->itemid),
                'term' => $course->term,
                'subjectArea' => $course->subj_area,
                'catalogNumber' => $course->crsidx,
                'sectionNumber' => $course->secidx,
                'srs' => $course->srs,
                'uidStudent' => empty($uidstudent) ? '000000000' : $uidstudent,
                'viewableGrade' => "$this->finalgrade",
                'comment' => $comment,
                'excused' => $this->excluded != '0'
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
