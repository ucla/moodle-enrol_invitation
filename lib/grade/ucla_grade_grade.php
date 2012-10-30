<?php

defined('MOODLE_INTERNAL') || die();

require_once('grade_grade.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot.'/local/gradebook/locallib.php');
require_once($CFG->dirroot.'/local/gradebook/grade_reporter.php');

class ucla_grade_grade extends grade_grade {

    public function insert($source = null) {
        $result = parent::insert($source);
        
        if($result) {
            $this->update_myucla($result);
        }
        
        return $result;
    }
    
    public function update($source = null) {
        $result = parent::update($source);
        
        $this->update_myucla($result);
        
        return $result;
    }
    
    public function delete($source = null) {
        $result = parent::delete($source);
        
        if($result) {
            $this->finalgrade = null;
            $this->feedback = "Deleted";
            $this->update_myucla($result);
        }
        
        return $result;
    }
    
    public function update_myucla($transactionid = '') {
        global $CFG, $COURSE, $USER;
        
        if (!empty($CFG->gradebook_send_updates)) {
            
            // What's going on here?
            if (isset($this->grade_item) && isset($this->grade_item->itemtype) &&
                ($this->grade_item->itemtype === 'course' ||
                $this->grade_item->itemtype === 'category')) {
                return false;
            } else {
                $errorcode = $this->send_to_myucla($COURSE, $USER, $transactionid, true);
                retry_myucla_failed_updates($COURSE->id);
                return ($errorcode == self::SUCCESS);
            }
        } else {
            return false;
        }
    }
    
    public function send_to_myucla($course, $user, $transactionid, $firstattempt) {
        global $CFG, $DB, $COURSE;
        $error = self::SUCCESS;
        
        //Look up crosslisted courses
        $courses = ucla_get_course_info($course->id);
        
        try {
            //Set-up for SQL query
            $coursesrs_list = array();
            foreach ($courses as $childcourse) {
                $coursesrs_list[] = $childcourse->srs;
            }
            $srs_list = implode($coursesrs_list, ',');

            //Find the fields of the child course they're actually in
            $sql =
                "SELECT urc.id, param_term, param_srs, urc.term, urc.srs, urc.subj_area, urc.crsidx, urc.secidx
                FROM {ccle_roster_class_cache} crcc
                JOIN {user} AS u ON u.idnumber = stu_id
                JOIN {ucla_reg_classinfo} AS urc ON urc.srs = param_srs
                WHERE u.id = $this->userid
                AND param_srs IN ($srs_list)";

            $enrolledcourses = $DB->get_records_sql($sql);

            if (empty($enrolledcourses)) {
                error_log( "WARNING: User in course but could not find matching role assignments in child courses.\n");
            } elseif (count($enrolledcourses) > 1) {
                error_log("WARNING: User enrolled in more than one child course of a cross-listed course.\n");
            } else {
                //Connect to MyUCLA
                $client = grade_reporter::get_instance();

                foreach($enrolledcourses as $childcourse) {
                    $param = $this->make_myucla_parameters($childcourse, $user, $transactionid);
                    if (is_array($param)) {
                        //Get real value of transactionid. May be truncated from original.
                        $transactionid = $param['mTransaction']['moodleTransactionID'];
                        try {
                            $webservice = $client->moodleGradeModify($param);
                            if (!$webservice->moodleGradeModifyResult->status) {
                                throw new Exception($webservice->moodleGradeModifyResult->message);
                            }
                        } catch (SoapFault $e) {
                            if (self::CONNECTION_ERROR > $error) {
                                $error = self::CONNECTION_ERROR;
                            }
                            
                            myucla_grade_log($this, false, $course, true);                            

                            if ($firstattempt) {
                                insert_failed_update('grades', $childcourse->id,
                                    $this->id, $user->id, $transactionid, $error);
                            }
                            continue;
                        } catch (Exception $e) {
                            if (self::BAD_REQUEST > $error) {
                                $error = self::BAD_REQUEST;
                            }

                            myucla_grade_log($this, false, $course, true);

                            if ($firstattempt) {
                                insert_failed_update('grades', $childcourse->id,
                                    $this->id, $user->id, $transactionid, self::BAD_REQUEST);
                            }
                            continue;
                        }

                        if ($firstattempt) {
                            $DB->delete_records('ucla_grade_failed_updates', array('foreignid'=>$this->id));
                        }
                    }
                }
            }
        } catch (SoapFault $e) {
            //Catches unknown hosts and bad connections
            $error = self::CONNECTION_ERROR;

            //Skip 2nd attempt, these are handled by retry_myucla_failed_updates
            if ($firstattempt) {
                //Add failure only to the childcourse(s) user is in
                //  (the user should only be in 1)
                //Simplifies resend (especially for bulk sending)
                foreach ($enrolledcourses as $childcourse) {
                    insert_failed_update('grades', $course->id, $this->id, $user->id,
                        $transactionid, $error);
                }
            }
        }
        return $error;

    }
    
    static function bulk_send_to_myucla($graderecords) {
        try {
            //Connect to MyUCLA
            $client = grade_reporter::get_instance();
        } catch (Exception $e) {
            //Catches unknown hosts and bad connections
            $error = self::CONNECTION_ERROR;

            //Mark all as failed
            $failures = 'all';
            return $failures;
        }

        $failures = array();  //Stores grades which failed to be updated

        //Group by user
        $graderecordsbyuser = array();
        foreach ($graderecords as $graderecord) {
            if (!isset($graderecordsbyuser[$graderecord->uid])) {
                $graderecordsbyuser[$graderecord->uid] = array();
            }
            $graderecordsbyuser[$graderecord->uid][] = $graderecord;
        }

        //Iterate by user because GradeListModify only allows single user
        foreach ($graderecordsbyuser as $graderecords) {
            $param = self::make_myucla_parameters_bulk($graderecords);
            if (is_array($param)) {
                $transactionid = $param['mTransaction']['moodleTransactionID'];
                try {
                    $webservice = $client->moodleGradeListModify($param);
                    if (!$webservice->moodleGradeListModifyResult->status) {
                        throw new Exception($webservice->moodleGradeListModifyResult->message);
                    }

                } catch (SoapFault $e) {
                    $error = self::CONNECTION_ERROR;
                    
                    //Mark all grades associated with current user as failed
                    foreach ($graderecords as $graderecord) {
                        $graderecord->error = $error;
                        $failures[] = $graderecord;
                    }

                    //Skip to next user
                    continue;
                } catch (Exception $e) {
                    $error = self::BAD_REQUEST;

                    //Look at result message for which ones failed
                    $failureids = array();

                    $message = $webservice->moodleGradeListModifyResult->message;
                    $messagelength = strlen($message);

                    if (strpos($message, "Grade list appears empty") !== false) {
                        //Mark all as failed
                        foreach ($graderecords as $graderecord) {
                            $graderecord->error = $error;
                            $failures[] = $graderecord;
                        }
                    } else {
                        $startPattern = 'Failed to process the following gradeID(s): ';
                        if (strstr($message, $startPattern)){
                            $vals = str_replace('Failed to process the following gradeID(s): ', '', $message);
                            $vals = str_replace('.', '', $vals);
                            $gradeids = explode(',', $vals);
                            
                            foreach ($gradeids as $gradeid){
                                if (is_numeric($gradeid)){
                                    $failureids[trim($gradeid)] = true;
                                }
                            }
                        }

                        foreach ($graderecords as $graderecord) {
                            if (isset($failureids[$graderecord->foreignid])) {
                                $graderecord->error = $error;
                                $failures[] = $graderecord;
                            }
                        }
                    }

                    //Skip to next user
                    continue;
                }
            }
        }

        return $failures;
    }
}