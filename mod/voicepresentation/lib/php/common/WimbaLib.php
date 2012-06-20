<?php

define('WIMBA_INFO',  'info');
define('WIMBA_WARN',  'warn');
define('WIMBA_ERROR', 'error');
define('WIMBA_DEBUG', 'debug');
define('WIMBA_DIR', $CFG->dataroot . "/blackboard_collaborate");
/**
 * Returns the keys of the general parameters passed by GET or POST
 */

function getKeysOfGeneralParameters()
{
    return array(
        array("value" => "enc_course_id", "type" => PARAM_INT, "default_value" => null),
        array("value" => "enc_email", "type" => PARAM_CLEAN, "default_value" => null),
        array("value" => "enc_firstname", "type" => PARAM_CLEAN, "default_value" => null),
        array("value" => "enc_lastname", "type" => PARAM_CLEAN, "default_value" => null),
        array("value" => "enc_role", "type" => PARAM_ALPHA, "default_value" => null),
        array("value" => "time", "type" => PARAM_INT, "default_value" => null),
        array("value" => "signature", "type" => PARAM_ALPHANUM, "default_value" => null),
        array("value" => "product", "type" => PARAM_ALPHA, "default_value" => null),
        array("value" => "type", "type" => PARAM_ALPHA, "default_value" => null),
        array("value" => "studentView", "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "action", "type" => PARAM_ALPHANUM, "default_value" => null),
        array("value" => "resource_id", "type" => PARAM_CLEAN, "default_value" => "0"),
        array("value" => "gradeId", "type" => PARAM_ALPHANUM, "default_value" => "-1"),
        array("value" => "error", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "messageAction", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "messageProduct", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "messageProductName", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "filter_screen_name", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "rid_audio", "type" => PARAM_CLEAN, "default_value" => "0")
        );
} 

function getKeyWimbaClassroomForm()
{
    return array (
        array("value" => "longname", "type" => PARAM_CLEAN, "default_value" => null),
        array("value" => "description", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "led", "type" => PARAM_ALPHA, "default_value" => "instructor"),
        array("value" => "hms_two_way_enabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enable_student_video_on_startup", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "hms_simulcast_restricted", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "video_bandwidth", "type" => PARAM_CLEAN, "default_value" => "medium"),
        array("value" => "video_window_size_on_startup", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "video_window_encoding_size", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "video_default_bit_rate", "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "status_appear", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enabled_breakoutrooms", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "archiveEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enabled_student_eboard", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enabled_students_breakoutrooms", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enabled_students_mainrooms", "type" => PARAM_BOOL, "default_value" => "0"),   
        array("value" => "enabled_status", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "appshareEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "pptEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "chatEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "privateChatEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "accessAvailable", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "privateChatEnabled", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "userlimit", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "guests", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "userlimitValue", "type" => PARAM_INT, "default_value" => -1),
        array("value" => "can_download_mp3", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "can_download_mp4", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "enable_archives", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "auto_open_archive", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "display_archive_reminder", "type" => PARAM_BOOL, "default_value" => "0"),
        array("value" => "mp4_encoding_type", "type" => PARAM_CLEAN, "default_value" => "standard"),
        array("value" => "mp4_media_priority", "type" => PARAM_CLEAN, "default_value" => "content_focus_with_video"),
        array("value" => "mp4_media_priority_content_include_video", "type" => PARAM_BOOL, "default_value" => "0")
        );
} 

function getKeyWimbaVoiceForm()
{
    return array (
        array("value" => "default",      "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "longname",    "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "description", "type" => PARAM_CLEAN, "default_value" => "dd"),
        array("value" => "led",         "type" => PARAM_ALPHA, "default_value" => "student"),

        array("value" => "audio_format", "type" => PARAM_CLEAN, "default_value" => "medium"),
        array("value" => "max_length",   "type" => PARAM_INT,   "default_value" => "0"),
        array("value" => "delay",        "type" => PARAM_INT,   "default_value" => "0"),

        array("value" => "short_title",   "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "chrono_order",  "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "show_reply",    "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "show_forward",  "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "show_compose",  "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "filter",        "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "grade",        "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "points_possible",        "type" => PARAM_CLEAN, "default_value" => ""),
        array("value" => "grades",        "type" => PARAM_RAW, "default_value" => null),
        array("value" => "accessAvailable", "type" => PARAM_BOOL,  "default_value" => "0"),
        array("value" => "start_date",      "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "start_month",     "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "start_day",       "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "start_year",      "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "start_hr",        "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "start_min",       "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "end_date",        "type" => PARAM_ALPHA, "default_value" => "false"),
        array("value" => "end_month",       "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "end_day",         "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "end_year",        "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "end_hr",          "type" => PARAM_ALPHANUM, "default_value" => "0"),
        array("value" => "end_min",         "type" => PARAM_ALPHANUM, "default_value" => "0"),
        );
} 

function getListOfWeeks($course, $current)
{
    $i = 1;
    $weekdate = $course->startdate; // this should be 0:00 Monday of that week
    $weekdate += 7200; // Add two hours to avoid possible DST problems
    $weekofseconds = 604800;
    $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
    $string = '<OPTION selected value=0>Week 0 : Introduction section</OPTION>';

    while ($weekdate < $course->enddate) 
    {
        $nextweekdate = $weekdate + ($weekofseconds);
        $weekday = userdate($weekdate, '%d %b') ;
        $endweekday = userdate($weekdate + 518400, '%d %b');
        if ($current == $i) 
        {
            $string .= '<OPTION selected value=' . $i . '>' . "Week " . $i . " : " . $weekday . ' - ' . $endweekday . '</OPTION>';
        } 
        else 
        {
            $string .= '<OPTION value=' . $i . '>' . "Week " . $i . " : " . $weekday . ' - ' . $endweekday . '</OPTION>';
        } 
        $i++;
        $weekdate = $nextweekdate;
    } 
    return $string;
} 

function getListOfTopics($course, $current)
{
    global $DB;
    $section = 0;
    while ($section <= $course->numsections) 
    {
        if (!$thissection = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>$section)))
        {
            notify('Error getting course_sections!');
        } 
        $desc = format_text($thissection->summary, FORMAT_MOODLE, null, $course->id);
        $textByLines=explode("<br />",$desc)  ;  //we use <br /> to explode because the wysiwig editor add this element for line return
        $descTxt = strip_tags($textByLines[0]);
        $minidesc = substr($descTxt, 0, 20);

        if (($thissection->summary != null) && (strlen($descTxt) > 20)) 
        {
            $minidesc .= "...";
        } 
        else if ($thissection->summary == null) 
        {
            $minidesc = "Topic";
        } 

        if ($current == $section) 
        {
            echo '<OPTION selected value=' . $section . '>' . $section . ". " . $minidesc . '</OPTION>';
        } 
        else 
        {
            echo '<OPTION value=' . $section . '>' . $section . ". " . $minidesc . '</OPTION>';
        } 
        $section++;
    } 
} 

function redirection($url)
{
    header('Location:' . $url);
    exit ();
} 
function parentRedirection($url)
{
    echo '<script> window.top.location="' . $url . '"; </script>';
    exit ();
} 

function manage_error($errno, $error, $file, $line, $context)
{
    global $error_wimba;
    if ($errno == E_USER_ERROR  or $errno ==  E_ERROR) 
    {
        wimba_add_log(WIMBA_ERROR,"general",$error ." in ".$file." line ".$line);
        $error_wimba = true;
    } 
    else if ($errno < E_USER_NOTICE)
    {
        wimba_add_log(WIMBA_DEBUG,"general",$error ." in ".$file." line ".$line);
    }

} 

function txt($string)
{
    $result = str_replace("<br />", "" , $string);
    $result = str_replace("<p>", "" , $result);
    $result = str_replace("</p>", "" , $result);
    return $result;
} 

function isSwitch()
{
    global $USER;

    if ((isset($USER->studentview) && $USER->studentview == 1) || (!empty($USER->switchrole))) 
    {
        return true;
    } 
    return false;
} 

function getRoleForWimbaTools($courseId, $userId)
{
    global $CFG;
    global $USER;
    $role = "";
    if (strstr($CFG->release, "1.7")) 
    {
        $context = get_context_instance(CONTEXT_COURSE, $courseId) ;
    } 
    // the role of the current user is switched
    if ((isset($USER->studentview) && $USER->studentview == 1) ||
            (isset($context) && isset($USER->switchrole) && !empty($USER->switchrole) && $USER->switchrole[$context->id] > 3)) 
    {
        $role = 'StudentBis';
    } 
    else 
    {
        if (isstudent($courseId)) 
        { // Student
            $role = 'Student';
        } 
        else if (isadmin() || isteacher($courseId, $USER->id)) 
        { // Admin, Teacher
            $role = 'Instructor';
        } 

        if (strstr($CFG->release, "1.7")) 
        { // 1.7.* version
            if (iscreator()) 
            { // Course Creator
                $role = 'Instructor';
            } 
            else if (!isteacheredit($courseId)) 
            { // Non-editing Teacher
                $role = 'Student';
            } 
        } 
    } 
    return $role;
} 

/*
    * Give the parameters with the signature md5 to give to the frame 
    *  @param $courseid : the id of the current course
    * return a string with all the parameters to give to the url
    */
function get_url_params($courseid)
{
    global $USER;
    global $CFG;

    $role = getRoleForWimbaTools($courseid, $USER->id);
    $signature = md5($courseid . $USER->email . $USER->firstname . $USER->lastname . $role);
    $url_params = "enc_course_id=" . wimbaEncode($courseid) .
                  "&enc_email=" . wimbaEncode($USER->email) .
                  "&enc_firstname=" . wimbaEncode($USER->firstname) .
                  "&enc_lastname=" . wimbaEncode($USER->lastname) .
                  "&enc_role=" . wimbaEncode($role) .
                  "&signature=" . wimbaEncode($signature);
    return $url_params;
} 

/*
 * return the students enrolled in the course
 */
function getStudentsEnrolled($courseid)
{
  $context = get_context_instance(CONTEXT_COURSE, $courseid);
  $adminUsers = get_users_by_capability($context,'mod/voicepresentation:presenter');
  $allUsers = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $courseid));

  //$users also contain the users which have this capabilities at the system level
  if(function_exists('array_diff_key')) { //PHP 5+
    $students=array_diff_key($allUsers,$adminUsers);//we get the student by getting the diff of the two arrays
  } else { //PHP 4.x
    $students=array_diff_assoc($allUsers,$adminUsers);//we get the student by getting the diff of the two arrays
  }
  return $students;        
}

/* list the element of the pictures directory
    * return a String wich contains the pictures separated by a ,
    */
function list_dir($name, &$s)
{
    if ($dir = opendir($name)) 
    {
        while ($file = readdir($dir))
        {
            if (is_dir($name . "/" . $file) && !in_array($file, array(".", ".."))) 
            {
                list_dir($name . "/" . $file, $s);
            } 
            else if ($file != "." && $file != "..") 
            {
                $s .= $file . ";";
            } 
        } 
        closedir($dir);
    } 
} 


/**Add logs in moodledata
 * param : the level og the log ('info', 'warn','error' or 'debug'), the message to display on the log
 * return : void, store the log in MOODLEDATADIR/wimba/logs/LEVEL
 */
function wimba_add_log($level,$product,$message){

//Set the log level values.
$level_values = array(
        WIMBA_DEBUG => 1,
        WIMBA_INFO => 2,
        WIMBA_WARN => 3,
        WIMBA_ERROR => 4
);
    global $CFG, $DB;
    
    $log_product = $product."_log_level"; 
    
    if( $product == "voiceboard" || 
        $product == "voicepresentation" || 
        $product == "voicepodcaster" || 
        $product == "voiceemail" || 
        $product == "voiceauthoring" )
    {
          
        $log_product = "voicetools_log_level"; // we use the voice tools settings for all the features
    }
      
    if(!isset($CFG->$log_product)){
        $CFG->$log_product=2;//default to Info
    }
    //Write on the logs only if the configured level allows it.
    if ($product=="general" || $level_values[$level] >= $CFG->$log_product){
    
        // Gets the site shortname
        $site = $DB->get_record('course',array('id'=>SITEID));
        
        //Computes the timestamp corresponding to the day (at 00:00:00).
        $today_timestamp = @mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        
        //If it doesn't exist, create the log folder'
        @mkdir(WIMBA_DIR, 0700);
     
        @mkdir(WIMBA_DIR . "/" . $product, 0700);
        
        $wimba_logs= WIMBA_DIR . "/" . $product . "/logs";
        @mkdir($wimba_logs, 0700);
    
        //Computes the log filename. Space characters are replaced by unerscore, to have a correct filename.
        $file = $wimba_logs."/".str_replace(' ','_',$site->shortname)."-".$today_timestamp."-blackboard_collaborate.log";
        
        //Writes the message in the log, and close it
        $fh = @fopen($file, "a");
        @fwrite($fh,gmdate("Y-m-d H:i:s")." ".strtoupper($level)." ".$product." - ".$message."\n");
        @fclose($fh);
    }
}

/*
 * This function converts the result from the api function getAverageMessageLengthPerUser to a key/Value array
 * @return an array
 */
function convertResultFromGetAverageLengthPerUser($array){
    $result=array();      
    for ($i = 0; $i < count($array); $i++) 
    {
        $result[$array[$i]["screen_name"]]=$array[$i]["message_len"];
    }
    return $result;
}

/*
 * This function converts the result from the api function getNbMessagePerUser to a key/Value array
 * @return an array
 */
function convertResultFromGetNbMessagePerUser($array){
    $result=array();      
    for ($i = 0; $i < count($array); $i++) 
    {
        $result[$array[$i]["user"]["screen_name"]]=$array[$i]["nb_message"];
    }
    return $result;
}


/*
 * implementation of the indexOf function
 */
function indexOf($string,$search)
{
    return (!(strpos($string,$search) === false))?(strpos($string,$search)):-1;
}

    
function endsWith( $str, $sub ) {
  return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}


/*
 * Check if the server version is > 1.9.5
 */
function isGradebookAvailable(){
    global $CFG;
    list($majorRelease, $minorRelease, $maitenanceRelease) = explode(".", $CFG->release);

    if($majorRelease == 1 && $minorRelease >= 9 && substr($maitenanceRelease,0,1) >= 5)//gradebook integration available for 1.9.5+
    {
        return true;
    } else if ($majorRelease > 1) {
        return true;
    }
    return false;
}

function wimbaEncode($str) {
    // ENT_QUOTES - encode single quotes
    return rawurlencode(htmlentities($str,ENT_QUOTES,"UTF-8"));
}
    
function wimbaDecode($str) {
    // ENT_QUOTES - encode single quotes
    return html_entity_decode(rawurldecode($str),ENT_QUOTES,"UTF-8");
}

?>
