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
if (!isset($CFG->libraryreserves_data))
    die("\n".get_string('errlrmsglocation','tool_datasourcesync')."\n");

// Begin database update
update_libraryreserves_db();

function update_libraryreserves_db(){
   // get global variables
   global $CFG, $DB;
   
   $datasource_url = $CFG->libraryreserves_data;

   $data = &get_csv_data($datasource_url);
   $data = &cleanup_csv_data($data, "ucla_libraryreserves");
   

  // Drop table and refill with data
   $DB->delete_records('ucla_libraryreserves');
   
    for ($line = 0; $line < count($data); $line++){
        foreach ($data[$line] as $field => $fieldvalue){
            echo $fieldvalue;
        }
    }
        
}


