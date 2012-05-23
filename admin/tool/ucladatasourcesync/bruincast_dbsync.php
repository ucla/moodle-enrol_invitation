<?php
/**
* Command line script to parse, verify, and update Bruincast entries in the Moodle database.
*
* $CFG->bruincast_data, $CFG->bruincast_errornotify_email, and $CFG->quiet_mode are defined
* in the plugin configuration at Site administration->Plugins->Blocks->Bruincast
*
* See CCLE-2314 for details.
**/

require_once(dirname(__FILE__).'/lib.php');

// Check to see if config variables are initialized
if (!isset($CFG->bruincast_data)) {
    die("\n".get_string('bcerrmsglocation','tool_ucladatasourcesync')."\n");
}

if (!isset($CFG->bruincast_errornotify_email)) {
    die("\n".get_string('bcerrmsgemail','tool_ucladatasourcesync')."\n");
}

if (!isset($CFG->quiet_mode)) {
    die("\n".get_string('bcerrmsgquiet','tool_ucladatasourcesync')."\n");
}

// Begin database update
update_bruincast_db();

/**
* Updates Bruincast DB from CSV at $CFG->bruincast_data URL
**/
function update_bruincast_db(){

    // get global variables
    global $CFG, $DB;
    
    echo get_string('bcstartnoti','tool_ucladatasourcesync');

    $datasource_url = $CFG->bruincast_data;
    $data = &get_csv_data($datasource_url);

    $data = &cleanup_csv_data($data, "ucla_bruincast");

    // Drop table and refill with data
    $DB->delete_records('ucla_bruincast');

    $insert_count = 0;
    $line = 0;
    $index = false;
   
    for ($line = 0; $line < count($data); $line++) {
   
        $row = new stdClass();
    
        foreach ($data[$line] as $field => $fieldvalue){
            $row->$field = $fieldvalue;
        }

        try {
            $index = $DB->insert_record('ucla_bruincast', $row);
        } catch(Exception $e) {
            // Do nothing, to handle cases where rows are invalid beyond norms. Does not insert row.
        }

        if ($index) {
            $insert_count++;
        }

    }

    if ($insert_count == 0) {
        echo "\n".get_string('bcerrinsert','tool_ucladatasourcesync')."\n";
    } else {
        echo "\n... ".$insert_count." ".get_string('bcsuccessnoti','tool_ucladatasourcesync')."\n" ;
    }

    // check_crosslists(&$data);
    // need get_course_info and other course functions first
}

/**
* Checks for crosslist issues and emails $CFG->bruincast_errornotify_email to fix.
* @param $data_incoming The array of CSV data to check
* @todo Finish when uclalib registrar query functions are done
**/
function check_crosslists($data_incoming)
{
    global $CFG;
    global $DB;
    
    // Now we do all this stuff to make sure the data is cool
    $termsrs_c = array();
    $courselist = array();

    // Get our corresponding course.id
    foreach ($data_incoming as $data) {
        // Crossreference with courses table
        $cterm = $data['term'];
        $csrs = $data['srs'];

        // Mark that this term-srs has an entry
        $termsrs_c[$cterm . '-' . $csrs] = $data;

        $courses = $DB->get_records_sql(" idnumber LIKE '{$cterm}-{$csrs}' OR idnumber LIKE '{$cterm}-Master_{$csrs}'");

        // Check if the other child courses also has a link
        if (empty($courses)) {
           //echo "No course: $cterm-$csrs\n";
        } else {
            foreach ($courses as $cinfo) {

                if (course_in_meta($cinfo)) {
                    // This branch means that this term-srs is that of a child course

                    // I could not find a function to get all parent courses
                    $pcourses = $DB->get_records_sql(
                        "SELECT parent_course FROM {$CFG->prefix}course_meta
WHERE child_course = '{$cinfo->id}'"
                    );
                
                    foreach ($pcourses as $pcourse) {

                        // We're going to run get_course_info of all the parent courses
                        if (!isset($courselist[$pcourse->parent_course])) {
                            $courselist[$pcourse->parent_course]
                                = get_course_info($pcourse->parent_course);
                        }
                    }
                }
            }
        }
    }

    if (empty($courselist)) {
        die;
    }

    

    /** Check crosslists and accesses and notify via EMAIL **/
    ob_start();
    foreach ($courselist as $mcourse => $tcourse) {
        $setcheck = array();
        $usetcheck = array();
        $dsetcheck = array();
        $accesscheck = array();

        foreach ($tcourse as $gcourse) {
            $gt = $gcourse['term'];
            $gs = $gcourse['srs'];

            if (isset($termsrs_c[$gt . '-' . $gs])) {
                $dt = $termsrs_c[$gt . '-' . $gs];

                $bcurl = $dt['bruincast_url'];
                $setcheck[$bcurl] = strtolower($dt['restricted']);
                $usetcheck[$bcurl . '-' . $gt . '-' . $gs] = TRUE;
                $accesscheck[$bcurl][] = $gcourse;
            } else {
                $dsetcheck[$gt . '-' . $gs] = $gcourse;
            }
        }

        if (!empty($setcheck)) {
            // Every course has its own unique data feed
            if (count($setcheck) == count($tcourse)) {
                continue;
            }

            // There is only one unique feed for the course, and each course has its own feed
            if (count($setcheck) == 1 && count($usetcheck) == count($tcourse)) {
                continue;
            }

            // Not every course has its own data, we may have bruincast data that is not
            // accessible to everyone in the course
            foreach ($setcheck as $url => $rest) {
                if ($rest != 'open') {
                    // This is bad, we have restricted bc data unavailable to all courses
                    echo "There is a restricted BruinCast URL:\n$url\nThis URL is accessible only to students in:\n";

                    foreach ($accesscheck[$url] as $cid) {
                        echo $cid['shortname'] . " (" . $cid['term'] . '-' . $cid['srs'] . ")\n";
                    }

                    echo "\n";
                    echo "We have this course associated with the following courses, and those courses are not listed "
                    . "in your data; therefore your students in the following courses will come screaming at you "
                    . "for access:\n";

                    foreach ($dsetcheck as $tsrs => $course) {
                        echo $course['shortname'] . " ($tsrs)\n";
                    }

                    echo "\n";
            
                    echo "Site URL: " . $CFG->wwwroot . "/course/view.php?id=" . $mcourse . "\n";

                    echo "\n";
                }
            }
        }
    }

    $mail_body = ob_get_clean();

    if (!isset($CFG->bruincast_errornotify_email) || $CFG->quiet_mode) {
        echo $mail_body;
    } else if (trim($mail_body) != '') {
        mail($CFG->bruincast_errornotify_email, 'BruinCast Data Issues (' . date('r') . ')', $mail_body);
    }
}
