<?php
/**
 * Library for use by the Datasource Syncronization Scripts of the Bruincast 
 * (CCLE-2314), Library reserves (CCLE-2312), and Video furnace (CCLE-2311)
 *
 * See CCLE-2790 for details.
 **/

// Satisfy Moodle's requirement for running CLI scripts
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Returns an array of raw CSV data from the CSV file at datasource_url.
 * @param $datasource_url The URL of the CSV data to attempt to retrieve.
 **/
function get_csv_data($datasource_url) {

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
 * @param $table_name The moodle DB table name against which to validate the field labels of the CSV data.
 * @note Currently only works with the Bruincast update script at ./bruincast_dbsync.php  May cause undefined behaviour if used with other datasets.
 **/
function cleanup_csv_data($data_array, $table_name) {

    // get global variables
    global $CFG;
    global $DB;

    $incoming_data = &$data_array; // mmm... memory savings.....
    $posfields = array();

    // Automatically ignore fields
    $curfields = $DB->get_records_sql("DESCRIBE {$CFG->prefix}" . $table_name);

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
            die("\nCould not find any lines that match any field in the DB!\n");
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

            $data = $incoming_data[$line][$tab_num];
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
            ucla_send_mail($CFG->bruincast_errornotify_email, 
                    'BruinCast Data Issues (' . date('r') . ')', $invalid_restrictions);
        }
    }


    return $data_incoming;
}
