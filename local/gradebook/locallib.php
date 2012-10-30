<?php
/**
 *  UCLA Global functions.
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;

//Grade Book functions.
/**
 * Checks if any gradebook updates failed and retries them
 *
 * @param int $courseid (optional) - The course whose grades should be resent
 */
function retry_myucla_failed_updates($courseid = null) {
    global $CFG, $DB;
    $maxpacketsize = 1000000;   //Maximum number of bytes MySQL can handle

    $select = '(attempts <= 3 OR nextattempt <= '.time().')';
    if (isset($courseid)) {

        $select .= ' AND courseid IN ('.$courseid.')';

    }

    $locknum = uniqid('', true);

    $selectlocked = "$select AND locked='$locknum'";
    $selectunlocked = "$select AND locked=0";

    //Lock desired rows
    $sql = "UPDATE {ucla_grade_failed_updates} 
           SET locked = '$locknum'
           WHERE $selectunlocked";
    $DB->execute($sql);

    try {
        //Select locked rows (these belong to current thread)
        //  and get associated information in single query for performance
        //TODO: Optimize string processing (although prob. not bottleneck)
        $sql = <<<SQL
            SELECT updates.*, c.id as cid, u.id AS uid, u.firstname, u.lastname, u.idnumber
            FROM {ucla_grade_failed_updates} AS updates
            JOIN {course} AS c ON courseid = c.id
            JOIN {user} AS u ON userid = u.id
            WHERE $selectlocked
SQL;
        if ($records = $DB->get_records_sql($sql)) {
            $items = array();
            $grades = array();
            foreach ($records as $record) {
                if ($record->type === 'item') {
                    $items[] = $record;
                }
                elseif ($record->type === 'grades') {
                    $grades[] = $record;
                }
            }

            $successes = array();
            $failures = array();
            $interval = 86400;  //one day in seconds
            $aborted = false;

            //First send over grade items
            foreach ($items as $record) {
                $error = grade_object::DATABASE_ERROR;

                //Create course and user objects
                $course = ucla_get_course_info($record->cid);

                $user = new Object();
                $user->id = $record->uid;
                $user->firstname = $record->firstname;
                $user->lastname = $record->lastname;
                $user->idnumber = $record->idnumber;

                $othergradeitem = new grade_item(array('id' => $record->foreignid));
                $error = $othergradeitem->send_course_to_myucla($course[0],
                    $user, $record->transactionid, false);

                if ($error == grade_object::SUCCESS) {
                    $successes[] = $record->id;
                } elseif ($error < grade_object::CONNECTION_ERROR) {
                    $failures[] = $record->id;
                } else {
                    //Update immediately because it has different error code
                    $record->attempts++;
                    $record->nextattempt = time() + $interval;
                    $record->errortype = grade_object::CONNECTION_ERROR;
                    $record->locked = 0;
                    update_record('ucla_grade_failed_updates', $record);
                    $aborted = true;
                    break;  //subsequent attempts probably won't work either
                }
            }

            //Perform Bulk SQL operations for performance
            if (!empty($successes)) {
                //Make sure $ids doesn't get too long for MySQL to handle
                $ids = implode($successes, ',');

                $offset = 0;
                $length = strlen($ids);
                while ($length - $offset > $maxpacketsize) {
                    $index = $offset + $maxpacketsize;

                    //Look for last occurence of comma within $maxpacketsize chars
                    while ($index >= $offset && $ids[$index] != ',') {
                        $index--;
                    }
                    if ($index < $offset) {
                        //No comma, so send the rest and hope for best
                        break;
                    }
                    //now index points to the last comma

                    $someids = substr($ids, $offset, $index - $offset);
                    $DB->delete_records_select('ucla_grade_failed_updates', "id in ($someids)");
                    $offset =  $index + 1;
                }
                $remainingids = substr($ids, $offset);
                $DB->delete_records_select('ucla_grade_failed_updates', "id IN ($remainingids)");
            }

            if (!empty($failures)) {
                //TODO: Make sure $ids doesn't get too long for MySQL to handle
                $ids = implode($failures, ',');

                $nexttime = time() + $interval; //Try again later
                //All other errors are BAD_REQUEST because first CONNECTION_ERROR causes abort
                $errorcode = grade_item::BAD_REQUEST;
                $sql = "UPDATE {ucla_grade_failed_updates}
                    SET attempts = attempts+1, nextattempt = $nexttime,
                    errortype = $errorcode, locked = 0
                    WHERE id in ($ids)";
                $DB->execute($sql);
            }

            //Now send over grades
            if (!$aborted) {
                //Bulk-send
                $failures = ucla_grade_grade::bulk_send_to_myucla($grades);

                if ($failures === 'all') {
                    //There is a connection issue when sending multiple grades to MyUCLA
                    $nexttime = time() + $interval; //Try again later

                    $sql = "UPDATE {ucla_grade_failed_updates}
                        SET attempts = attempts+1, nextattempt = $nexttime,
                        errortype = ".grade_object::CONNECTION_ERROR.", locked = 0
                        WHERE ($selectlocked)";
                    $DB->execute($sql);
                } else {
                    if (!empty($failures)) {
                        //At least one failure

                        //Group by error type so we can perform bulk SQL query
                        $failuresbyerrortype = array();
                        foreach ($failures as $failure) {
                            if (!isset($failuresbyerrortype[$failure->error])) {
                                $failuresbyerrortype[$failure->error] = array();
                            }
                            $failuresbyerrortype[$failure->error][] = $failure->id;
                        }

                        //Note - Looks scary because there's a SQL query inside
                        //a loop, but loop can only execute max 3 times.
                        //In reality, I expect loop only executed once.
                        //DATABASE_ERROR - not really expecting this to happen
                        //BAD_REQUEST - These errors are handled here
                        //CONNECTION_ERROR - More likely to be executed above under "failures === all"
                        foreach($failuresbyerrortype as $errorcode=>$failedids) {
                            //TODO: Make sure $ids doesn't get too long for MySQL to handle
                            $ids = implode($failedids, ',');

                            $nexttime = time() + $interval; //Try again later
                            $sql = "UPDATE {ucla_grade_failed_updates}
                                SET attempts = attempts+1, nextattempt = $nexttime,
                                errortype = $errorcode, locked = 0
                                WHERE id in ($ids)";
                            $DB->execute($sql);
                        }
                    }

                    $DB->delete_records_select('ucla_grade_failed_updates', $selectlocked);
                }
            }
        }
        //Unlock rows
        $sql = "UPDATE {ucla_grade_failed_updates}
               SET locked = 0
               WHERE ($selectlocked)";
        $DB->execute($sql);
    }
    catch (Exception $e) {
        //Unlock rows
        $sql = "UPDATE {ucla_grade_failed_updates}
               SET locked = 0
               WHERE ($selectlocked)";
        $DB->execute($sql);
    }
}

/**
 * Inserts an initial row for a failed update for a given course for current grade item.
 * @param string $type - Either 'item' or 'grades'
 * @param int $courseid - The course.id field of the (parent) course to process
 * @param int $foreignid - The grade_grades.id field or grade_item.id field
 * @param int $userid - The user.id field of the user entering the request
 * @param string $transactionid (optional) - id of the transaction with MyUCLA
 *      Same as id of grade_items_history table
 * @return bool - success
 */
function insert_failed_update($type, $courseid, $foreignid, $userid, $transactionid, $errorlevel) {
    global $CFG, $DB;

    $nextattempt = time() + 86400; //Try again tomorrow

    //Overwrite existing rows
    //Assumes type, courseid, foreignid form a unique
    $sql = "INSERT INTO {ucla_grade_failed_updates} SET
           type = '$type', courseid = $courseid, foreignid = $foreignid,
           userid = $userid, transactionid = '$transactionid', attempts = 1,
           nextattempt = $nextattempt, errortype = $errorlevel, locked = 0
           ON DUPLICATE KEY UPDATE attempts = attempts+1, nextattempt =
           $nextattempt, userid = $userid, transactionid = '$transactionid',
           errortype = $errorlevel";

    return $DB->execute($sql);
}

/**
 *  Function will do a log of a MyUCLA Gradebook push attempt.
 **/
function myucla_grade_log($grade, $webservice, $course, $superfail=false) {
    global $CFG, $USER;

    $important = array('itemname', 'itemmodule', 'iteminstance');

    $courseid = $course->id;

    $url = '';

    if (isset($grade->grade_item)) {
        $gi = $grade->grade_item;
        $gradetype = 'grade_grade';
    } else {
        $gi = $grade;
        $gradetype = 'grade_item';
    }

    $results = array();
    foreach ($important as $var) {
        if (isset($gi->$var)) {
            $results[$var] = $gi->$var;
        }
    }

    $itemmod = $results['itemmodule'];

    $module = $itemmod;

    $failtext = "Gradebook push failure";
    if ($superfail) {
        $action = $failtext;

        $info = "MyUCLA gradebook service failed to connect";
    } else if (!$webservice) {
        $action = $failtext;

        if (empty($USER->idnumber)) {
            $info = "{$USER->firstname} {$USER->lastname} does not have a valid idnumber";
        } else {
            $info = "course does not have a valid {$CFG->prefix}_course.idnumber [{$course->idnumber}]";
        }
    } else {
        if (!$webservice->status) {
            $action = $failtext;
        } else {
            $action = "Gradebook push success";
        }

        $info = "transactionID: {$webservice->moodleTransactionID}: {$webservice->message}";
    }

    $info .= " $itemmod name: [" . $results['itemname'] . "]";

    add_to_log($courseid, $module, $action, $url, $info, $results['iteminstance']);
}

// EOF
