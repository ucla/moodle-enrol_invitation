<?php
/**
  * Command line script to parse, verify, and update Bruincast entries in the Moodle database.
  * 
  * Requires $CFG->bruincast_data, $CFG->bruincast_errornotify_email, and $CFG->quiet_mode to
  * be defined in the global configuration file.
  *
  * See CCLE-2314 for details.
  **/

require_once(dirname(dirname(dirname(__FILE__))) . "/config.php");

//satisfy moodle's requirement for cli scripts
define('CLI_SCRIPT', true);

// Check to see if config variables are initialized
if (!isset($CFG->bruincast_data)) {
    echo "\nERROR: No location set for bruincast data.\n";
    exit(4);
}

if (!isset($CFG->bruincast_errornotify_email)) {
    echo "\nERROR: No email set for bruincast error notifcation. \n";
    exit(4);
}

if (!isset($CFG->quiet_mode)) {
    echo "\nERROR: No config option quiet_mode set. \n";
    exit(4); 
}

//begin database updates 
update_bruincast_db();

/**
  * Updates Bruincast DB from CSV at $CFG->bruincast_data URL
  **/
function update_bruincast_db(){

    // get global variables
    global $CFG, $DB;
    
    echo "Starting bruincast DB update: ";

    $datasource_url = $CFG->bruincast_data;
    $data = &get_csv_data($datasource_url);

    $data = &cleanup_csv_data($data);

    // Drop table and refill with data
    $DB->delete_records('ucla_bruincast');

    $insert_count = 0;
    $line = 0;
   
    for ($line = 0; $line < count($data); $line++) {
   
        $row = new stdClass();
    
        foreach ($data[$line] as $field => $fieldvalue){
            $row->$field = $fieldvalue;
        }

        try {
            $index = $DB->insert_record('ucla_bruincast', $row);
        } catch(Exception $e) {
            // Do nothing, to handle cases where rows are invalid beyond norms.  Does not insert row.
        }

        if ($index !== FALSE) {
            $insert_count++;
        }

    }

    if ($insert_count == 0) {
        echo("\n... ERROR: no rows inserted. \n");
    } else { 
        echo("\n... used $insert_count dbqueries \n");
    }

    // check_crosslists(&$data);
    // need get_course_info and other course functions first
}

/**
  * Returns an array of raw CSV data from the CSV file at datasource_url.
  * @param $datasource_url The URL of the CSV data to attempt to retrieve.
  **/
function get_csv_data($datasource_url){

    $lines = array();
    $fp = fopen($datasource_url, 'r');

    if ($fp) {
        $lines = '';

        while (!feof($fp)) {
            $lines[] = fgetcsv($fp);
        }
    }

    if (empty($lines)) {
        echo "\n... ERROR: Could not open $datasource_url!\n";
        exit(5);
    }

    return $lines;
}

/**
  * Returns an array of cleaned and parsed CSV data from the unsafe and/or unclean input array of data.
  * @param $data The array of unsafe CSV data.
  **/
function cleanup_csv_data($data_array){
    
    // get global variables
    global $CFG;
    global $DB;

    $incoming_data = &$data_array; // mmm... memory savings.....
    $posfields = array();

    // Automatically ignore fields
    $curfields = $DB->get_records_sql("DESCRIBE {$CFG->prefix}ucla_bruincast");

    foreach ($curfields as $fieldname => $fielddata) {

        // Skip the field `id`
        if ($fieldname == 'id') {
            continue;
        }

        $posfields[$fieldname] = $fieldname;
    }

    // Assuming the field descriptor line is going to come before all the other lines
    $field_descriptors_obtained = FALSE;
    $fields_desc_line = -1;
    $total_lines = count($incoming_data);

    while (!$field_descriptors_obtained) {

        $fields_desc_line++;

        if ($fields_desc_line == $total_lines) {
            die ("Could not find any lines that match any field in the DB!");
        }

        $file_fields = array();

        foreach ($incoming_data[$fields_desc_line] as $tab_num => $field_name) {

            if (!isset($posfields[$field_name])) {
                $ignored_fields[] = $field_name;
            } else {
                $finfields[] = $field_name;
            }
    
            $file_fields[$field_name] = $field_name;
        }

        // Assume that this is the line that we want
        $field_descriptors_obtained = TRUE;

        foreach ($posfields as $fieldname => $field) {
        
            if (!isset($file_fields[$field])) {
                // This line is not the field descriptor line!
                $field_descriptors_obtained = FALSE;
            }

        }
    }

    // Reindex the data for nicer formatting
    $data_incoming = array();
    $invalid_restrictions = array();
    for ($line = $fields_desc_line + 1; $line < count($incoming_data); $line++) {

        // Make sure this line has data as we have fields
        if (count($incoming_data[$line]) != count($file_fields)) {
            echo "Line $line is badly formed!\n";
            continue;
        }
    
        foreach ($incoming_data[$fields_desc_line] as $tab_num => $field_name) {

            $data  = $incoming_data[$line][$tab_num];
            $field = trim($field_name);
    
            if (in_array($field, $ignored_fields)) {
                continue;
            } 

            // Pad the beginning with 0s
            if ($field_name == 'srs') {
                $data = sprintf('%09s', $data);
            }

            if ('restricted' == $field) {
                // make sure that restricted is a known value
                if (!in_array($data, array('Open', 'Restricted', 'See Instructor', 'Online'))) {
                    $invalid_restrictions[] = "Line $line has unknown restriction of $data. Marking it as 'Undefined'. Please add it.\n";
                    // restriction is not in lang file (for now). just set
                    // restriction as undefined
                    $data = 'Undefined';
                }
            }
        
            $data_incoming[$line][$field] = $data;
        }
    }

    if (!empty($invalid_restrictions)) {
        $invalid_restrictions = implode("\n", $invalid_restrictions);

        if (!isset($CFG->bruincast_errornotify_email) || $CFG->quiet_mode) {
            echo $invalid_restrictions;
        } else {
            mail($CFG->bruincast_errornotify_email, 'BruinCast Data Issues (' . date('r') . ')', $invalid_restrictions);
        }
    }

    
    return $data_incoming;
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
    $termsrs_c  = array();
    $courselist = array();

    // Get our corresponding course.id
    foreach ($data_incoming as $data) {
        // Crossreference with courses table
        $cterm = $data['term'];
        $csrs  = $data['srs'];

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
