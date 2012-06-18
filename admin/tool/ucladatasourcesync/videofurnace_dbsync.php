<?php
/**
 * Command line script to parse, verify, and update Video Furnace entries in the Moodle database.
 *
 * $CFG->videofurnace_data is defined in the plugin configuration at 
 * Site administration->Plugins->Blocks->Video furnace
 *
 * See CCLE-2311 for details
 **/
require_once('lib.php');
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');

// Check to see that config variable is initialized
$datasource_url = handle_cfgs();

// Begin database update
update_videofurnace_db($datasource_url);

/**
 * Main function for updating the video furnace db.
 * 
 * @todo This function should be working properly except for the part with the mail.
 * @todo fix_data_format is currently implementing most of the functionality
 * that cleanup_csv_data is supposed to do. cleanup_csv_data needs to be 
 * modified to support this block. Note that even though this is tsv data,
 * the cleanup_csv_data works for it as well due to the nature of the get_data
 * function.
 * @todo More debug information should be added to this entire section.
 * @todo Update_mail_data and send_mail_data should be uncommented when
 * they are working correctly.
 */
function update_videofurnace_db($datasource_url) {
    // get global variables
    global $DB;

    echo get_string('vfstartnoti', 'tool_ucladatasourcesync');

    $incoming_data = &get_tsv_data($datasource_url);
    //Haven't gotten around to merging the following cleanup code into matts function. should be done once there is time.
    //$data = &cleanup_csv_data($data, "ucla_video_furnace");

	// remove old record
    $vidfurn_table = 'ucla_video_furnace';
	$DB->delete_records($vidfurn_table); 
	$insert_count = 0;

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
            echo get_string('errvfinvalidrowlen', 'tool_ucladatasourcesync') . "\n";
            continue;
        }
        
        fix_data_format($row_data);
      
		$id = FALSE;
	    $id = $DB->insert_record('ucla_video_furnace', $row_data);
        // update_mail_data($row_data, $mail_data);
		if ($id) {
			$insert_count++;
		}
    }

    echo "\n... " . $insert_count . " " . get_string('vfsuccessnoti', 'tool_ucladatasourcesync') . "\n";
	 /*if ($CFG->videofurnace_send_emails && !empty($mail_data)) {
        send_mail_data($mail_data);
    } */
} 

 /** 
  * Adds new information to the mail_data array based on the information given from row_data
  * 
  * @param array $row_data data returned from cleanup_csv_data. 
  * @param array $mail_data the array that the new mail object will be added to.
  * 
  * @todo $row_data was cleaned up in fix_data_format, so all instances of $row_data[i]
  * need to be replaced with its appropriate counterpart ($row_data[0] should be $row_data['term'], for instance.
  */
 function update_mail_data($row_data, &$mail_data){
          /*  //Check if the movie is from a class in moodle
            $term = $row_data[0];
            $srs = $row_data[1];
            $courseid = ucla_map_termsrs_to_courseid($term, $srs);
            $course = ucla_get_course_info($course_id);
           
            if ($course) {
                $class_info = $course[0];
                $idnum = $courseid;
                # if this is the first movie from this class create the contents and find the profs' emails
                if (!isset($mail_data[$idnum])) {
                    $mail_data[$idnum] = create_mail_object($row_data, $courseid, $course[0]);
                }   
                # add the movie to the email contents
                $mail_data[$idnum]['email_contents'] .= "\t" . $row_data[6] . "\n\t\t"
                                                    . 'Start: ' . date('Y-M-d', $row_data[2]) . "\n\t\t"
                                                    . 'End: ' . date('Y-M-d', $row_data[3]) . "\n\n";
            } */
 }
 
 /**
  *
  * @param array $row_data - data returned from cleanup_csv_data
  * @param int $courseid - Courseid of the course that the rowdata refers to.
  * @param course object $course_info- Course information of the course that the rowdata refers to. 
  * 
  * @todo $row_data was cleaned up in fix_data_format, so all instances of $row_data[i]
  * need to be replaced with its appropriate counterpart ($row_data[0] should be $row_data['term'], for instance.
  * @todo The sql query needs to be updated to moodle 2.0 syntax. I'm guessing that the select statement can be 
  * copied into get_records_sql, but i'm not entirely sure. https://svn.sscnet.ucla.edu/ccle/trunk/misc/videofurnace/vidfurn_dbsync.php
  * is where the original code is at, so refer to that. The relevant parts should be commented in /// below.
  * 
  */
 function create_mail_object($row_data, $courseid, $course_info){
   /* global $CFG, $DB;
    # create the subject
    $mail_object['email_subject'] = $row_data[4] . ' (' 
        . $row_data[0] . '): '
        . 'VideoFurnace movies added to your class website.';
    # find and store the instructors' emails for the course for later
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $get_emails_stmt->bindParam(1, $context->id);
    $get_emails_stmt->execute();
    
    $result = get_records_sql(stuff goes here);
    /// $result = $get_emails_stmt->fetchAll();
    ///    $get_emails_stmt = $dbh->prepare("SELECT DISTINCT $usertable.email FROM $roleassntable
    ///                                  JOIN $usertable ON $roleassntable.userid = $usertable.id
    ///                                  WHERE $roleassntable.roleid IN $roles_to_email AND $roleassntable.contextid=?");
    ///                $context = get_context_instance(CONTEXT_COURSE, $courseid);
    ///                $get_emails_stmt->bindParam(1, $context->id); 
    /// ? should be $context->id
    ///    $roles_to_email = $CFG->videofurnace_roles_to_email;
    ///    $roletable     = $prefix . 'role';
    ///    $roleassntable = $prefix . 'role_assignments';
    ///    $usertable     = $prefix . 'user';

    foreach ($result as $value) {
        if (!isset($mail_object['instruct_emails'])) {
            $mail_object['instruct_emails'] = $value['email'];
        } else {
            $mail_object['instruct_emails'] .= ', ' . $value['email'];
        }
    }

    # store the instructors name for use later
    $mail_object['instruct_name'] = $row_data[5];
    # create the email contents
    $mail_object['email_contents'] = $row_data[4] . ' (' . $row_data[0] 
                    . '): ' . $CFG->dirroot . '/course/view/' . $course_info->shortname . "\n"
                    . 'Movies may be found at: '
                    . $CFG->dirroot . '/course/view/' . $course->shortname . "?page=vidfurn\n"
                    . 'New movies:' . "\n\n";    

    return $mail_object;*/
     
 }
 
 
/**
 * E-mails people about new video furnace links based on the mail data given.
 * 
 * @todo Haven't worked on this function at all besides copy paste the relevant parts
 * from 1.9 into here. 
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
    $data_object['_del_flag'] = 0;
    
    $courseid = 1;//ucla_map_termsrs_to_courseid($data_object['term'], $data_object['srs']);
    /*if($courseid == false){
        echo('error, error!');
    }*/
    $data_object['courseid'] = $courseid;
    $row = $data_object;
}    
    
/**
 * Ensure that necessary cfg variables are initialized, and initializes some
 * cfg variables if they are initialized.
 * 
 * @todo there are a lot of config variables from 1.9 that I'm unsure about 
 * porting over. 
 */
function handle_cfgs(){

	
	// Check to see that config variable is initialized
	$datasource_url = get_config('block_ucla_video_furnace', 'source_url');
	if (empty($datasource_url)) {
		die("\n" . get_string('errvfmsglocation', 'tool_ucladatasourcesync') . "\n");
	}
	return $datasource_url;

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
