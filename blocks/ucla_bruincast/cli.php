<?php



/**
 * Updates Bruincast DB from CSV at $CFG->bruincast_data URL
 */
function update_bruincast_db(){
    global $CFG, $DB;
    
    echo get_string('bcstartnoti','tool_ucladatasourcesync');

    $data = &get_csv_data($CFG->bruincast_data);

    // We know for the bruincast DATA that the first two rows are a 
    // timestamp and the column titles, so get rid of them
    unset($data[0]);
    unset($data[1]);

    $clean_data = array();

    // This is expected bruincast mapping
    $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');
        
    // create an array of table record objects to insert
    foreach($data as $d) {
        $obj = new stdClass();
        
        foreach($keymap as $k => $v) {
            if($k == 2) {   // skip 'class' field
                continue;
            }
            $obj->$v = $d[$k];
        }
        
        // Use SRS as key, might be handy
        $clean_data[$obj->srs] = $obj;
    }
    
    // Drop table and refill with data
    $DB->delete_records('ucla_bruincast');

    // Insert records
    $errcount = 0;
    try {
        foreach($clean_data as $cd) {
            $DB->insert_record('ucla_bruincast', $cd);
        }
    } catch (dml_exception $e) {
        // Report a DB insert error
        echo "\n".get_string('errbcinsert','tool_ucladatasourcesync')."\n";
        $errcount++;
    }
    
    // Get total inserts
    $insert_count = count($clean_data) - $errcount;

    echo "\n... ".$insert_count." ".get_string('bcsuccessnoti','tool_ucladatasourcesync')."\n" ;

    check_crosslists($clean_data);
    // need get_course_info and other course functions first
}

/**
 * Checks for crosslist issues and emails $CFG->bruincast_errornotify_email to fix.
 * 
 * @param $data_incoming The array of CSV data to check
 * @todo Finish when uclalib registrar query functions are done
 *
 */
function check_crosslists(&$data) {
    global $CFG, $DB;
    
    $problem_courses = array();
    
    // Find crosslisted courses.  
    foreach($data as $d) {
        // Get the courseid for a particular TERM-SRS
        $courseid = ucla_map_termsrs_to_courseid($d->term, $d->srs);
        
        // Find out if it's crosslisted 
        $courses = ucla_map_courseid_to_termsrses($courseid);
        
        // Enforce:
        // If for a crosslisted course, any of the bruincast urls are restricted, 
        //   then all of the courses need to have access to the bruincast.
        if(count($courses) > 1) {
            if(strtolower($d->restricted) == 'restricted') {
                foreach($courses as $c) {
                    if(empty($data[$c->srs])) {
                        $msg = "There is a restricted bruincast URL that is not \n"
                                . "associated with crosslisted coures:"
                                . "url: " . $d->bruincast_url . "\n"
                                . "srs: " . $d->srs . "\n"
                                . "affected course srs: " . $c->srs . "\n";
                        
                        $problem_courses[] = $msg;
                    }
                }
            }
        }
    }
    
    $mail_body = implode('\n', $problem_courses);
    
    // Send problem course details if we have any
    if (!isset($CFG->bruincast_errornotify_email) || $CFG->quiet_mode) {
        echo $mail_body;
    } else if (trim($mail_body) != '') {
        mail($CFG->bruincast_errornotify_email, 'BruinCast Data Issues (' . date('r') . ')', $mail_body);
    }
}
