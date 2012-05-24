<?php
/**
 * Command line script to parse, verify, and update Video Furnace entries in the Moodle database.
 *
 * $CFG->libraryreserves_data is defined in the plugin configuration at 
 * Site administration->Plugins->Blocks->Library reserves
 *
 * See CCLE-2311 for details
 **/
require_once('lib.php');
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');

handle_cfgs();

// Begin database update
update_videofurnace_db();

function update_videofurnace_db() {
    // get global variables
    global $CFG, $DB;
    $datasource_url = 'http://164.67.141.31/~guest/VF_LINKS.TXT'; //get_config('block_video_furnace', 'source_url');

    echo get_string('vfstartnoti', 'tool_ucladatasourcesync');

    $incoming_data = &get_tsv_data($datasource_url);
    //$data = &cleanup_csv_data($data, "ucla_video_furnace");

    $vidfurn_table = 'ucla_video_furnace';
    # create mail data array for storing email contents
    $mail_data = array();  
    for ($row = 2; $row < sizeof($incoming_data); $row++) {
        /* An array of all the data extracted from the datasource for this particular row. 
         * Used to check for duplicate entries in the table.
         */
        $row_data = $incoming_data[$row];
        
    	# check if the row has the correct number of columns, skip it and log an error if it does not
        # if the row is empty, skip it but don't log an error
        if ( (sizeof($row_data) == 1) && ($row_data['term'] == "") ) {
            continue;
        } else if(sizeof($row_data) != 8) {
            echo get_string('errinvalidrowlen', 'tool_ucladatasourcesync') . "\n";
            continue;
        }
        
        fix_data_format($row_data);
        
        $row_data_dup_check = array($DB->sql_compare_text('term') => $row_data['term']); //.' AND', 'srs' => $row_data['srs']);
            /*.' AND', 'start_date' => $row_data['start_date'].' AND',
                        'stop_date' => $row_data['stop_date'].' AND', 'class' => $row_data['class'].' AND', 'instructor' => $row_data['instructor'].' AND', 'video_title' => $row_data['video_title'].' AND',
                        'video_url' => $row_data['video_url']);    */  
        # check to see if the row exists in the existing data
        $result = $DB->get_records('ucla_video_furnace', $row_data_dup_check);
		
        if(empty($result) == false) {
            echo "wat";
            # if it does mark it so as not to delete it
            $DB->set_field($vidfurn_table, '_del_flag', false, $row_data_dup_check);
        } else {
             echo "wa2t";
            # if it does not then insert it
            $DB->insert_record('ucla_video_furnace', $row_data);
            //mail_data[] = create_mail_object($row_data);
        }
    }

    # delete out-of-date rows according to the delete flag, then reset all of the delete flags
    $DB->delete_records($vidfurn_table, array('_del_flag'=>true)); 
    $DB->set_field_select($vidfurn_table, '_del_flag', true, 'true'); 
    
    /*if ($CFG->videofurnace_send_emails && !empty($mail_data)) {
        send_mail_data($mail_data);
    } */
    
    echo get_string('vfsuccessnoti', 'tool_ucladatasourcesync') . "\n";
 } 

 /* 
  * INCOMPLETE DO NOT USE
  */
 function create_mail_object($row_data){
                /*
            # check if the movie is from a class in moodle, and add it to the class_info DB            
            $termsrs = $row_data[0] . '-' . $row_data[1];
            $check_mdl_class_stmt->bindParam(1, $termsrs);
            $check_mdl_class_stmt->execute();
            $result = $check_mdl_class_stmt->fetchAll();
            
            if ($result) {
                $class_info = $result[0];
                $idnum = $class_info['idnumber'];
                # if this is the first movie from this class create the contents and find the profs' emails
                if (!isset($mail_data[$idnum])) {
                    # create the subject
                    $mail_data[$idnum]['email_subject'] = $row_data[4] . ' (' 
                        . $row_data[0] . '): '
                        . 'VideoFurnace movies added to your class website.';
                    # find and store the instructors' emails for the course for later
                    $courseid = $class_info['id'];
                    $context = get_context_instance(CONTEXT_COURSE, $courseid);
                    $get_emails_stmt->bindParam(1, $context->id);
                    $get_emails_stmt->execute();
                    $result = $get_emails_stmt->fetchAll();
                    foreach ($result as $value) {
                        if (!isset($mail_data[$idnum]['instruct_emails'])) {
                            $mail_data[$idnum]['instruct_emails'] = $value['email'];
                        } else {
                            $mail_data[$idnum]['instruct_emails'] .= ', ' . $value['email'];
                        }
                    }

                    # store the instructors name for use later
                    $mail_data[$idnum]['instruct_name'] = $row_data[5];
                    # create the email contents
                    $mail_data[$idnum]['email_contents'] = $row_data[4] . ' (' . $row_data[0] 
                                    . '): ' . $webroot . '/course/view/' . $class_info['shortname'] . "\n"
                                    . 'Movies may be found at: '
                                    . $webroot . '/course/view/' . $class_info['shortname'] . "?page=vidfurn\n"
                                    . 'New movies:' . "\n\n";
                }   

                # add the movie to the email contents
                $mail_data[$idnum]['email_contents'] .= "\t" . $row_data[6] . "\n\t\t"
                                                     . 'Start: ' . date('Y-M-d', $row_data[2]) . "\n\t\t"
                                                     . 'End: ' . date('Y-M-d', $row_data[3]) . "\n\n";
            } */
 }
 
 
/* 
* INCOMPLETE DO NOT USE
*/
function send_mail_data($mail_data){
   /* $currtime = date('Y-m-d H:i:s');
    $monitor_body = 'VideoFurnace movies have been added to class websites:' . "\n\n";
    foreach ($mail_data as $value) {
        $instructsubject = $value['email_subject'];
        $instructbody = $instructsubject . "\n\n";
        if ($first_run) {
            $instructbody .= 'IMPORTANT:
Your class website now has a "Video Furnace" link which contains links to all the Video Furnace movies that have been processed for your class. It is automatically updated, and you will be emailed, whenever a new movie is added.'."\n\n";            }
       $instructbody .= 'A direct link to VideoFurnace movies can be found on your class website:' . "\n";
       $instructbody .= $value['email_contents'] . "\n";

       if ($CFG->quiet_mode) {
           $email_tos = $monitor_email;
       } else {
           $email_tos = $monitor_email . ', ' . $value['instruct_emails'];
       }

       mail($email_tos, $instructsubject, $instructbody, "From: $notifier_email");
       // END SSC MODIFICATION #821
       $monitor_body .= $value['instruct_name'] . ' - ' . $value['email_contents'] . "\n";
    }

    # send the monitor's mail
    mail($monitor_email, "VideoFurnace LinkPage Updates $currtime", $monitor_body);*/
}
    
# function to format dates so that they look like standard MySQL dates
# update: function generates a int(10) unix timestamp.
function fix_date_format($date_in) {
    $temp_date = explode('/', $date_in);
    while (strlen($temp_date[0]) < 2) {
        $temp_date[0] = '0' . $temp_date[0];
    }

    while (strlen($temp_date[1]) < 2) {
        $temp_date[1] = '0' . $temp_date[1];
    }

    $temp_date[2] = '20' . $temp_date[2];
    $date_out = mktime(0, 0, 0, $temp_date[0], $temp_date[1], $temp_date[2]);

    return $date_out;
}

/*
 * Formats the raw data stored in row, and replaces row with an object containing
 * the formatted data and a new courseid field based on the raw data.
 * 
 * @param $row An array containing the raw TSV data obtained from the datasource.
 */
function fix_data_format(&$row){

    # fix all formatting issues with incoming data
    # add leading zeroes to the srs is its under 9 chars
    while (strlen($row[1]) < 9) {
        $row[1] = '0' . $row[1];
    }

    # If the term is less than 3 characters, it's probably from 
    # missing leading zeroes. We solve this by prepending zeroes.
    while (strlen($row[0]) < 3) {
        $row[0] = '0' . $row[0];
    }

    # format the start and end dates yyyy-mm-dd
    $row[2] = fix_date_format($row[2]);
    $row[3] = fix_date_format($row[3]);

    # remove quotes surrounding class names and movie titles
    $row[4] = preg_replace('/^"(.*)"$/','$1',$row[4]);
    $row[6] = preg_replace('/^"(.*)"$/','$1',$row[6]);

    # remove newlines from urls
    $row[7] = trim($row[7]);

    $data_object = array();
    //$data_object->timestamp = time();
    $data_object['term'] = $row[0];
    $data_object['srs'] = $row[1];
    $data_object['start_date'] = $row[2];
    $data_object['stop_date'] = $row[3];
    $data_object['class'] = $row[4];
    $data_object['instructor'] = $row[5];
    $data_object['video_title'] = $row[6];
    $data_object['video_url'] = $row[7];
    $data_object['delete_flag'] = false;
    
    /*$courseid = ucla_map_termsrs_to_courseid($data_object->term, $data_object->srs);
    if($courseid == false){
        echo('error, error!');
    }
    $data_object['courseid'] = ucla_map_termsrs_to_courseid($data_object->term, $data_object->srs);*/
    $row = $data_object;
}    
    
/*
 * Ensure that necessary cfg variables are initialized, and initializes some
 * cfg variables if they are initialized.
 */
function handle_cfgs(){
    

    // Check to see that config variable is initialized
    $datasource_url = 'http://164.67.141.31/~guest/VF_LINKS.TXT'; //get_config('block_video_furnace', 'source_url');

    if (empty($datasource_url)) {
        die("\n" . get_string('errvfmsglocation', 'tool_ucladatasourcesync') . "\n");
    }
    /* # SET DEBUG MODES
    # ignore checking for updated timestamp (for testing)
    $force_update = false;

    # do not send mail
    // START SSC MODIFICATION #821 : Sending emails for videofurnace
    if (!isset($CFG->quiet_mode)) {
        $CFG->quiet_mode = TRUE;
    }

    if (!isset($CFG->videofurnace_send_emails)) {
        $CFG->videofurnace_send_emails = FALSE;
        echo "Config variable videofurnace_send_emails not set, not sending any emails.";
    }

    # Open the file containing the first line from the last run
    if (isset($CFG->videofurnace_lastrun)) {
        $lastrun = $CFG->videofurnace_lastrun;
        $dupCheck = fopen($lastrun,"r");
    } else {
        # Exit if we can't find a file containing the timestamp comparison
        die("No filename given for timestamp comparison, run failed.\n");
    } */

    /*# set webroot
    $webroot = $CFG->wwwroot;

    # MAIL CONFIG #######################################
    # THESE SHOULD ALSO GO INTO MAIN CONFIG FILE
    $notifier_email = $CFG->videofurnace_notifier_email;
    $monitor_email  = $CFG->videofurnace_monitor_email;

    # set the roles we want to email
    # 1 - Administrator
    # 2 - Course creator
    # 3 - Teacher
    # 4 - Non-editing teacher
    # 5 - Student
    # 6 - Guest
    # Example: $var = "('1','2','3')";
    $roles_to_email = $CFG->videofurnace_roles_to_email;

    */
}    
   
//EOF
