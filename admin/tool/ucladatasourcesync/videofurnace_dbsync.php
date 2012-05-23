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
require_once('/../../../local/ucla/lib.php');
// Check to see that config variable is initialized
$datasource_url = get_config('block_video_furnace', 'source_url');

if (empty($datasource_url)) {
    die("\n" . get_string('errvfmsglocation', 'tool_ucladatasourcesync') . "\n");
}

// Begin database update
update_videofurnace_db($datasource_url);

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
        
        $data_object = new stdClass();
        $data_object->term = $row[0];
        $data_object->srs = $row[1];
        $data_object->start_date = $row[2];
        $data_object->stop_date = $row[3];
        $data_object->class = $row[4];
        $data_object->instructor = $row[5];
        $data_object->video_title = $row[6];
        $data_object->video_url = $row[7];
        $row = $data_object;
}

function update_videofurnace_db($datasource_url) {
    // get global variables
    global $CFG, $DB;

    echo get_string('vfstartnoti', 'tool_ucladatasourcesync');

    $incoming_data = array();

    if ($lines === FALSE) {
        die("\n" . get_string('errvffileopen', 'tool_ucladatasourcesync') . "\n");
    }    

    $datasource_url = $CFG->video_furnace_data;
    $data = &get_csv_data($datasource_url);
    $data = &cleanup_csv_data($data, "ucla_video_furnace");

 } 
    
##################################################
# moodle_dbsync.php
# Script to sync up the VF table in moodle with the incoming data from
#    VideoFurnace. The script will email the instructors for a course
#    when new movies are added to for that course.
# It needs to be edited for local moodle root location, contact info, etc.

# get CFG
require_once(dirname(dirname(dirname(__FILE__))) . "/moodle/config.php");

function printToFile($filePointer, $stringToPrint) {
    if ($filePointer != -1) {
        fwrite($filePointer, $stringToPrint);
    }
}

ini_set('auto_detect_line_endings', true);

# SET DEBUG MODES
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
}

# set webroot
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


########## MAIN ##########
# read the file into a two-dimensional array
$lines = file($filename);
foreach ($lines as $line_num => $line) {
    $line = trim($line);
    $incoming_data[$line_num] = explode("\t", $line); 
}

# Grab the first line of the new file and the last run
if ($dupCheck) {
    $prevRun = fgets($dupCheck);
    fclose($dupCheck);
} else {
    $prevRun = "No previous data";
    printToFile($f, $prevRun);
    printToFile($f, "\n");
}

$firstLine = $lines[0];

# check to see if the table has been updated
if(!$force_update) {
    try {
        # Compare the first line of the input file with the saved first line from the last run
        # If the 2 lines match then we exit
        if ($prevRun == $lines[0]) {
            printToFile($f,"No change, last data feed change inserted on ");
            printToFile($f, date("Y/m/d H:i ", filemtime($CFG->videofurnace_lastrun)));
            printToFile($f,"\n");
            fclose($f);
            exit(0);
        }
    } catch(PDOException $e) {
        fclose($f);
        die("Could not validate/update timestamp.\n");
    }
}

# Save the new update time
$dupCheck = fopen($lastrun,"w");
printToFile($dupCheck, $firstLine);
fclose($dupCheck);

# Set timestamp to now
$timestamp = time();

# sync the db
try {
    $table = 'ucla_video_furnace';
    # prepare all of the statements for syncing the db
    $insert_stmt = $dbh->prepare("INSERT INTO $vidfurntable 
            (timestamp,term,srs,start_date,stop_date,class,instructor,video_title,video_url,_del_flag)
            VALUES (?,?,?,?,?,?,?,?,?,?)");
    $DB->insert_record('ucla_video_furnace', $incoming_data[$row]);
    $check_dups_stmt = $dbh->prepare("SELECT * FROM $vidfurntable WHERE
                                                term=? AND srs=? AND start_date=?
                                                AND stop_date=? AND class=? AND instructor=?
                                                AND video_title=? AND video_url=?");
    $DB->get_records('ucla_video_furnace', array('term' => $row_data[0], 'srs' => $row_data[1]. 'start_date' => $row_data[2].
                        'stop_date' => $row_data[3], 'class' => $row_data[4], 'instructor' => $row_data[5], 'video_title' => $row_data[6],
                        'video_url' => $row_data[7]));
    $update_row_stmt = $dbh->prepare("UPDATE $vidfurntable SET _del_flag=false WHERE 
                                                term=? AND srs=? AND start_date=?
                                                AND stop_date=? AND class=? AND instructor=?
                                                AND video_title=? AND video_url=?");
    
    $check_mdl_class_stmt = $dbh->prepare("SELECT * FROM $coursetable WHERE idnumber=?");

    $get_emails_stmt = $dbh->prepare("SELECT DISTINCT $usertable.email FROM $roleassntable
                                      JOIN $usertable ON $roleassntable.userid = $usertable.id
                                      WHERE $roleassntable.roleid IN $roles_to_email AND $roleassntable.contextid=?");

    # create mail data array for storing email contents
    $mail_data = array();
    
    printToFile($f,"Size of incoming data was: ");
    printToFile($f,sizeof($incoming_data));
    printToFile($f," lines\n");

    for ($row = 2; $row < sizeof($incoming_data); $row++) {
        $row_data = $incoming_data[$row];
    	# check if the row has the correct number of columns, skip it and log an error if it does not
        # if the row is empty, skip it but don't log an error
        # echo "Row $row number of cols=",sizeof($row_data),"\n";
        if ((sizeof($row_data) == 1) && ($row_data[0] == "")) {
            continue;
        } elseif (sizeof($row_data) != 8) {
            echo("Incorrectly formed input data at row $row.\nRow Contents:\n");
            printToFile($f,"Incorrectly formed input data at row $row.\nRow Contents:\n");
            print_r($row_data);
            continue;
        }
        
        fix_data_format($row_data);

        # check to see if the row exists in the existing data
        $result = $DB->get_records('ucla_video_furnace', array('term' => $row_data[0], 'srs' => $row_data[1]. 'start_date' => $row_data[2].
                        'stop_date' => $row_data[3], 'class' => $row_data[4], 'instructor' => $row_data[5], 'video_title' => $row_data[6],
                        'video_url' => $row_data[7]));
		
        if ($result.empty() == false) {
            # if it does mark it so as not to delete it
            $update_row_stmt->execute($row_data);
        } else {
            # if it does not then insert it
            $insert_stmt->execute(array_merge(array($timestamp),$row_data,array(false)));
            printToFile($f,"ADD " . $row_data[0] . "-" . $row_data[1] 
                . " " . $row_data[4] . " " . $row_data[5] . " " 
                . $row_data[6] . " " . $row_data[7] . "\n");

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
            } 

        }
    }

    # delete out-of-date rows according to the delete flag, then reset all of the delete flags
    $stmt = $dbh->prepare("DELETE FROM $vidfurntable WHERE _del_flag=true");
    $stmt->execute();
    printToFile($f, "Deleted " . $stmt->rowCount() . " row(s)\n");
    $stmt = $dbh->prepare("UPDATE $vidfurntable SET _del_flag=true");
    $stmt->execute();
    fclose($f);
} catch(PDOException $e) {
    echo $e;
    fclose($f);
    die("Could not add new data to the table.\n");
}

if ($CFG->videofurnace_send_emails && !empty($mail_data)) {
    $currtime = date('Y-m-d H:i:s');
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
    mail($monitor_email, "VideoFurnace LinkPage Updates $currtime", $monitor_body);
} 

# close the database connection
$dbh = null;

########## ND MAIN ##########
    
}

