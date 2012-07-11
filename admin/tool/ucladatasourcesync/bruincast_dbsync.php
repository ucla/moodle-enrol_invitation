<?php
/**
 * Command line script to parse, verify, and update Bruincast entries in the Moodle database.
 *
 * $CFG->bruincast_data, $CFG->bruincast_errornotify_email, and $CFG->quiet_mode are defined
 * in the plugin configuration at Site administration->Plugins->Blocks->Bruincast
 *
 * See CCLE-2314 for details.
 *
 */

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

function define_data_source() {
    $ret_val = array();

    $ret_val[] = array('name' => 'term',
                        'type' => 'term',
                        'min_size' => '3',
                        'max_size' => '3');
    $ret_val[] = array('name' => 'srs',
                        'type' => 'srs',
                        'min_size' => '7',
                        'max_size' => '9');
    $ret_val[] = array('name' => 'class',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '50');
    $ret_val[] = array('name' => 'restricted',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '20');
    $ret_val[] = array('name' => 'bruincast_url',
                        'type' => 'url',
                        'min_size' => '1',
                        'max_size' => '400');
    return $ret_val;
}
/**
 * Updates Bruincast DB from CSV at $CFG->bruincast_data URL
 */
function update_bruincast_db(){
    global $CFG, $DB;

    echo get_string('bcstartnoti','tool_ucladatasourcesync');

    $data = &get_csv_data($CFG->bruincast_data);
    $fields = define_data_source();

    // We know for the bruincast DATA that the first two rows are a
    // timestamp and the column titles, so get rid of them
    unset($data[0]);
    unset($data[1]);

    $clean_data = array();

    // This is expected bruincast mapping
    $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');

    // create an array of table record objects to insert
    foreach($data as $data_num => $d) {
        $obj = new stdClass();
        
        if (sizeof($d) != sizeof($fields)) {
            echo get_string('errbcinvalidrowlen', 'tool_ucladatasourcesync') ."\n";
            continue;
        }
        $invalid_fields = array();
        foreach ($fields as $field_num => $field_def) {
            // validate/clean data
            $data = validate_field($field_def['type'],
                    $d[$field_num], $field_def['min_size'],
                    $field_def['max_size']);
            if ($data === FALSE) {
                $invalid_fields[] = $field_def['name'];
            }
        }

        // give warning about errors
        if (!empty($invalid_fields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalid_fields);
            $error->line_num = $data_num;
            $error->data = print_r($row_data, true);
            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                    $error) . "\n");
        }

        foreach($keymap as $k => $v) {
            if($k == 2) {   // skip 'class' field
                continue;
            }
            $obj->$v = $d[$k];
        }

        // Use SRS as key, might be handy
        $clean_data[$obj->srs] = $obj;
    }

    // Drop table if we have new data
    if(!empty($clean_data)) {
        $DB->delete_records('ucla_bruincast');
    }

    // Insert records
    try {
        foreach($clean_data as $cd) {
            $DB->insert_record('ucla_bruincast', $cd);
        }

        // Get total inserts
        $insert_count = count($clean_data);
        echo "\n... ".$insert_count." ".get_string('bcsuccessnoti','tool_ucladatasourcesync')."\n" ;

        // Find errors in the crosslisted courses and notify
        check_crosslists($clean_data);

    } catch (dml_exception $e) {
        // Report a DB insert error
        echo "\n".get_string('errbcinsert','tool_ucladatasourcesync')."\n";
    }

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
        $courseid = match_course($d->term, $d->srs);

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

    $mail_body = implode("\n", $problem_courses);

    // Send problem course details if we have any
    if (!isset($CFG->bruincast_errornotify_email) || $CFG->quiet_mode) {
        echo $mail_body;
    } else if (trim($mail_body) != '') {
        mail($CFG->bruincast_errornotify_email, 'BruinCast Data Issues (' . date('r') . ')', $mail_body);
    }
}
