<?php
/**
 * Command line script to parse, verify, and update Library reserves entries in the Moodle database.
 *
 * $CFG->libraryreserves_data is defined in the plugin configuration at 
 * Site administration->Plugins->Blocks->Library reserves
 *
 * See CCLE-2312 for details
 **/
require_once('lib.php');

// Check to see that config variable is initialized
$datasource_url = get_config('block_ucla_library_reserves', 'source_url');
if (empty($datasource_url)) {
    die("\n" . get_string('errlrmsglocation', 'tool_ucladatasourcesync') . "\n");
}

// Begin database update
update_libraryreserves_db($datasource_url);

/**
 * Sets up array to be used to validate library reserve data source.
 * 
 * Expects library reserves data to be in the following format:
 * Course Number: VARCHAR2(10)
 * Course Name: VARCHAR2(40)
 * Department Code: VARCHAR2(10)
 * Department Name: VARCHAR2(40)
 * Instructor Last Name: VARCHAR2(50)
 * Instructor First Name: VARCHAR2(40)
 * Reserves List Title: VARCHAR2(40)
 * List Effective Date: YYYY-MM-DD
 * List Ending Date: YYYY-MM-DD
 * URL: VARCHAR2
 * SRS Number: VARCHAR2(9)
 * Quarter: CHAR(3)
 * 
 * @return mixed
 */
function define_data_source() {
    $ret_val = array();
    
    /* [index in datasource] => ['name']
     *                          ['type']
     *                          ['min_size']
     *                          ['max_size']
     */    
    $ret_val[] = array('name' => 'course_number',
                        'type' => 'coursenum',
                        'min_size' => '0',
                        'max_size' => '10');
    $ret_val[] = array('name' => 'course_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $ret_val[] = array('name' => 'department_code',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '10');
    $ret_val[] = array('name' => 'department_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '50');
    $ret_val[] = array('name' => 'instructor_last_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '50');
    $ret_val[] = array('name' => 'instructor_first_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $ret_val[] = array('name' => 'reserves_list_title',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $ret_val[] = array('name' => 'list_effective_date',
                        'type' => 'date_dashed',
                        'min_size' => '10',
                        'max_size' => '10');
    $ret_val[] = array('name' => 'list_ending_date',
                        'type' => 'date_dashed',
                        'min_size' => '10',
                        'max_size' => '10');
    $ret_val[] = array('name' => 'url',
                        'type' => 'url',
                        'min_size' => '1',
                        'max_size' => '400');
    $ret_val[]= array('name' => 'srs',
                        'type' => 'srs',
                        'min_size' => '7',  // in case leading zeroes are removed
                        'max_size' => '9');
    $ret_val[]= array('name' => 'quarter',
                        'type' => 'term',
                        'min_size' => '3',
                        'max_size' => '3');
    
    return $ret_val;
}

function parse_datasource($datasource_url) 
{
    $parsed_data = array();

    // get fields that should be in data source
    $fields = define_data_source();    
    
    
    # read the file into a two-dimensional array
    $lines = file($datasource_url);
    if ($lines === FALSE) {
        die("\n" . get_string('errlrfileopen', 'tool_ucladatasourcesync') . "\n");
    }    
    
    $num_entries = 0;
    foreach ($lines as $line_num => $line) {
        # stop processing data if we hit the end of the file
        if ($line == "=== EOF ===\n") {
            break;
        }
                
        # remove the newline at the end of each line
        $line = rtrim($line);
        $incoming_data = explode("\t", $line);
        
        // Check if all entries have the correct number of columns          
        if (count($incoming_data) != count($fields)) {
            // if first line, then don't give error, just skip it
            if ($line_num != 0) {   
                echo(get_string('errinvalidrowlen', 'tool_ucladatasourcesync', 
                        $line_num) . "\n");
            }
            continue;
        }
        
        // Bind incoming data field to local database table.
        // Don't fail if fields are invalid, just note it and continue, because
        // data is very messy, but we will try to work with it when trying to 
        // match a library reserve entry with a course.
        $invalid_fields = array();
        foreach ($fields as $field_num => $field_def) {
            // validate/clean data
            $data = validate_field($field_def['type'], 
                    $incoming_data[$field_num], $field_def['min_size'], 
                    $field_def['max_size']);            
            if ($data === FALSE) {
                $invalid_fields[] = $field_def['name'];                
            }
            
            $parsed_data[$num_entries][$field_def['name']] = $data;
            ++$field_num;
        }
        
        // give warning about errors
        if (!empty($invalid_fields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalid_fields);
            $error->line_num = $line_num;
            $error->data = print_r($incoming_data, true);                        
            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync', 
                    $error) . "\n");      
        }
                
        ++$num_entries;
    }
 
    return $parsed_data;
}

function update_libraryreserves_db($datasource_url) {
    // get global variables
    global $CFG, $DB;

    echo get_string('lrstartnoti', 'tool_ucladatasourcesync') . "\n";

    $parsed_data = parse_datasource($datasource_url);    
    if (empty($parsed_data)) {
        echo get_string('lrstartnoti', 'tool_ucladatasourcesync') . "\n";        
    }

    // wrap everything in a transaction, because we don't want to have an empty
    // table while data is being updated
    $num_entries_inserted = 0;
    try {
        $transaction = $DB->start_delegated_transaction();
        
        // Drop table and refill with data
        $DB->delete_records('ucla_library_reserves');

        foreach ($parsed_data as $reserve_entry) {
            // through each entry and try to match it to a course 
            
            // cat_num might have x, indicating a sec_num
            $result = explode('x', $reserve_entry['course_number']);
            $sec_num = null;
            $cat_num = $result[0];
            if (isset($result[1])) {
                $sec_num = $result[1];
            }
            $courseid = match_course($reserve_entry['quarter'], 
                    $reserve_entry['srs'], $reserve_entry['department_code'], 
                    $cat_num, $sec_num);
            if (!empty($courseid)) {
                $reserve_entry['courseid'] = $courseid;                
            }
            
            if ($DB->insert_record('ucla_library_reserves', $reserve_entry)) {
                ++$num_entries_inserted;
            }
        }
        
        if ($num_entries_inserted == 0) {
            throw new moodle_exception('errbcinsert', 'tool_ucladatasourcesync');
        }        
        
        // Assuming the both inserts work, we get to the following line.
        $transaction->allow_commit();
    } catch(Exception $e) {
        $transaction->rollback($e);
    }    
    
    echo  get_string('lrsuccessnoti', 'tool_ucladatasourcesync', 
            $num_entries_inserted) . "\n";
}

