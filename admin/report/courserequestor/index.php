<?php
/*
 * Course Requestor form
 * Integrated into moodle as a plugin
 *
 * Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
 *
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// connect to Registrar
$db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser , $CFG->registrar_dbpass) 
    or die( "ERROR: Connection to Registrar failed.");

$term = $CFG->currentterm; 

require_login();
global $USER;
global $ME;

// BEGIN CCLE MODIFICATION CCLE-1723
// Adding 'Support Admin' capability to course requestor
if (!has_capability('report/courserequestor:view', get_context_instance(CONTEXT_SYSTEM))) {
    error(get_string('adminsonlybanner'));
}
// END CCLE MODIFICATION


// Initialize $PAGE
$PAGE->set_url('/admin/report/courserequestor/index.php');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_heading(get_string('courserequestor', 'report_courserequestor'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface

admin_externalpage_setup('courserequestor');
echo $OUTPUT->header();

?>


<div class="headingblock header crqpaddingbot" >
    <?php echo get_string('coursereqbuildclass', 'report_courserequestor') ?>
</div>
<div class="generalbox categorybox box crqdivwrapper" style="display:block;" >
    <div class="crqcenterbox">
<?php
$course_requestor = $CFG->wwwroot."/admin/report/courserequestor/index.php";
$addCrosslist = $CFG->wwwroot."/admin/report/courserequestor/addcrosslist.php";

echo "<a href=\"$course_requestor\">".get_string('buildcourse', 'report_courserequestor')."</a> | ";
echo "<a href=\"$addCrosslist\">".get_string('addcrosslist', 'report_courserequestor')."</a> ";


// End UCLA Modification
?>
</div>
<div class="divclear" >
<?php
// build individual course
require_once(dirname(__FILE__).'/class_form.php');
$srsform = null;
$classform = new class_form();
if($srsformobj = $classform->get_data()){
    $srsform = (array) $srsformobj;
    $classform->display();
} else {
    $classform->display();
}
// build/view department courses
require_once(dirname(__FILE__).'/view_dept_form.php');
$selected_term = optional_param('term',NULL,PARAM_ALPHANUM) ? 
    optional_param('term',NULL,PARAM_ALPHANUM) : $CFG->classrequestor_selected_term;
$qr= odbc_exec($db_conn, "EXECUTE CIS_subjectAreaGetAll '$selected_term'");
$row = array();
$rows = array();
while (odbc_fetch_into($qr, $row))
{
    $rows[] = $row;
}
odbc_free_result($qr);
$viewdeptform = null;
$classform2 = new view_dept_form($rows);
$row = null;
$rows = null;
if($viewdeptobj = $classform2->get_data()){
    $viewdeptform = (array) $viewdeptobj;
    $classform2->display();
} else {
    $classform2->display();
}
?>
<div>
    <fieldset class="crqformeven">
        <legend></legend>
        SRS# Lookup <a href="http://www.registrar.ucla.edu/schedule/" 
        target="_blank">Registrar's Schedule of Classes</a>
    </fieldset>
</div>
<div class="crqdivclear" >
<?php
// SRS Look up form for live and built courses
require_once(dirname(__FILE__).'/build_form.php');
$buildform = null;
$dept = $DB->get_records_sql("select distinct department from ".$CFG->prefix."ucla_request_classes 
    order by department");
$deptform2 = new build_form($dept);
if($buildformobj = $deptform2->get_data()){
    $buildform = (array) $buildformobj;
    $deptform2->display();
}
else {
    $deptform2->display();
}
?>
</div>

<div class="crqdivclear" >
    <div class="crqfrmoutput" align="center" >

<?php

if(!empty($srsform))
{
    $crs = $DB->get_records('ucla_request_classes', array('term'=>$srsform['group1']['term'], 
        'action'=>'build'), null, 'srs');
    foreach($crs as $rows) {
        $srs=rtrim($rows->srs);
        $existingcourse[$srs]=1;
    }

    $crs2 = $DB->get_records('ucla_request_crosslist', 
    array('term'=>$srsform['group1']['term']), null, 'aliassrs');
    foreach($crs2 as $rows) {
        $srs=rtrim($rows->aliassrs);
        $existingaliascourse[$srs]=1;
    }
    if($srsform['action']=='fillform') {
        echo "<table style=\"width:90%\" ><tbody>";
        $term = rtrim($srsform['group1']['term']);
        $srs = rtrim($srsform['group1']['srs']);

        if((isset($existingcourse[$srs]) && $existingcourse[$srs]) 
            || (isset($existingaliascourse[$srs]) && $existingaliascourse[$srs])) {
                echo "<tr><td class=\"crqtableodd\" colspan=\"4\"><div class=\"crqerrormsg\">";
                echo "THIS SRS NUMBER HAS BEEN SUBMITTED TO CREATE A COURSE. ";
                echo "<br /> PLEASE ENTER A NEW SRS NO.</div></td></tr></tbody></table>";
        } else {
            $query = "EXECUTE ccle_CourseInstructorsGet '$term', '$srs' ";
            $inst = odbc_exec ($db_conn, $query);
            $inst_full="";

            $unlisted_count = 0;
            while ($row=odbc_fetch_object($inst) ) {
                $last_name_trim = trim($row->last_name_person);
                $first_name_trim = trim($row->first_name_person);

                if ($last_name_trim == "" && $first_name_trim == ""){
                    $unlisted_count++;
                    continue;
                }
                
                if ($last_name_trim != "") {
                    $inst_full .= " ".$last_name_trim;
                }
                
                if ($last_name_trim != "" && $first_name_trim != ""){
                    $inst_full .= ",";
                }
         
                if ($first_name_trim != "") {
                    $inst_full .= " ".$first_name_trim." ";
                }
            }
            
            $inst_full_display = $inst_full;
                        
            
            if ($inst_full == ""){
                $inst_full = "Not Assigned";
            }
            odbc_free_result($inst);
            $query1= "EXECUTE ccle_getClasses '$term','$srs'" ;
            $result = odbc_exec ($db_conn, $query1);
            
            if ($row1=odbc_fetch_object($result)) {
                $subj = rtrim($row1->subj_area);
                $type = rtrim($row1->acttype);
                $num = rtrim($row1->coursenum);
                $sect = rtrim($row1->sectnum);
                $title = rtrim($row1->coursetitle);
                $description  = rtrim($row1->crs_desc);
                $url = $row1->URL;
                $url=str_replace(" ","+",$url);
                $subj=preg_replace('/[\s&]/', '', $subj);
                $description=str_replace(" ","",$description);
                $session = rtrim($row1->session);
                $course = $subj.$num.'-'.$sect;
            }                     
            
            odbc_free_result($result);
            
            $mailinst_default = $CFG->classrequestor_mailinst_default;
            $forceurl_default = $CFG->classrequestor_forceurl_default;
            $nourlupd_default = $CFG->classrequestor_nourlupd_default;
            $hidden_default = get_config('moodlecourse')->visible;

            echo "<tr><td class=\"crqtableodd\" colspan=\"2\">";
            
            if (!isset($subj) || $subj == "") {
                echo "<div class=\"crqerrormsg\">PLEASE CHECK THE TERM AND SRS AGAIN.</div>";		
            } else {
                echo "</td></tr><tr><td class=\"crqtableeven\" valign=\"top\">";
                echo "<form method=\"POST\" action=\"".$PAGE->url."\">";
                echo "<input type=\"hidden\" name=\"srs\" value=\"".$srsform['group1']['srs']."\">";
                echo "<input type=\"hidden\" name=\"action\" value=\"courserequest\">";
                echo "<input type=\"hidden\" name=\"term\" value=\"".$srsform['group1']['term']."\">";
                echo "<input type=\"hidden\" name=\"instname\" value=\"$inst_full\">";

                echo "<div class=\"crqtableodd\">Instructor: <strong>" . $inst_full_display . "</strong>
                    </div>";
                echo "<div class=\"crqtableeven\"><label>Action: <select name=\"actionrequest\">";
                echo "<option value='build' selected>Build</option></select></label>
                    <label><input type=checkbox name=hidden value=1 " . (!$hidden_default? "checked" : '') . ">
                    &nbsp;Build as Hidden</label>
                    </div>";
                echo "<div class=\"crqtableodd\">
                    <label><input type=checkbox name=forceurl value=1 " . ($forceurl_default? "checked" : '') . ">
                    &nbsp;Force URL Update</label>
                    <label><input type=checkbox name=nourlupd value=1 " . ($nourlupd_default? "checked" : '') . ">
                    &nbsp;Prevent URL Update</label>				
                    </div>";
                if ($subj == "") { 
                    $subj="NULL";
                }
                
                echo "<div class=\"crqtableeven\"><label>Department:<input type=\"text\" ";
                echo "name=\"department\" value = $subj width=\"10\"></label></div>";
                if ($course == "") { 
                    $course="NULL";
                }
                
                echo "<div class=\"crqtableodd\"><label>Course:<input type=\"text\" name=\"course\" ";
                echo "value = $course width=\"10\"></label></div>";
                echo "</td><td class=\"crqtableeven\"> <div class=\"crqtableodd\">(Optional)</div>";
                echo "<div class=\"crqtableeven\">Crosslist";

                // CHECKING FOR CROSSLISTS
                $xlist_info = file("http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/getConSched?user=ssc&pass=zx12as&term=$srsform[group1][term]&SRS=$srsform[group1][srs]");
                $i = count($xlist_info) - 1;
                $xlistexists=0;
                while ($i >0) {
                    if(preg_match('/^[0-9]{9}$/',$xlist_info[$i])) {
                            $xlistexists=1;
                    }
                    $i--;
                }
                $i = count($xlist_info);
                if(!$xlistexists) {
                    $aliascount=5;
                    echo "<label><input type=\"radio\" name=\"xlist\" value = \"1\" >yes</label> <label>
                        <input type=\"radio\" name=\"xlist\" value = \"0\" checked>no</label></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias1\" 
                        size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableeven\"> Alias SRS<input type=\"text\" name=\"alias2\" 
                        size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias3\" 
                        size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableeven\"> Alias SRS<input type=\"text\" name=\"alias4\" 
                        size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias5\" 
                        size=\"20\" maxlength=\"9\"></div>";
                    echo "<input type=\"hidden\" name=\"aliascount\" value = \"$aliascount\" >";
                } else {
                    echo "<label><input type=\"radio\" name=\"xlist\" value = \"1\" checked>yes</label> 
                        <label><input type=\"radio\" name=\"xlist\" value = \"0\">no</label><br>";
                    echo "<br>Select SRS below to crosslist.<br><span style=\"color:red\" >Please 
                        uncheck the SRS you dont want crosslisted</span><br><br>";
                    $aliascount=0;
                    while ($i!=0) {
                        if(isset($xlist_info[$i]) && preg_match('/^[0-9]{9}$/',$xlist_info[$i])) {
                            $aliascount++;
                            $srs=ltrim($xlist_info[$i]);
                            $srs=rtrim($srs);
                            $srs=substr($srs,5,9);
                            $query3= "EXECUTE ccle_getClasses '$term','$srs'";
                            $result3 = odbc_exec ($db_conn, $query3);
                            while ($row3=odbc_fetch_object($result3)) {
                                $subj1 = rtrim($row3->subj_area);
                                $num1 = rtrim($row3->coursenum);
                                $sect1 = rtrim($row3->sectnum);
                                $course1 = $subj1.$num1.'-'.$sect1;
                            }
                            odbc_free_result($result3);
                            echo "<label><input type=\"checkbox\" name=\"alias$aliascount\" 
                                value=\"$srs\" checked> $course1 <span style=\"color:green\"> 
                                (SRS: $srs) </span></label><br>";
                        }
                        $i--;
                    }
                    echo "<input type=\"hidden\" name=\"aliascount\" value = \"$aliascount\" >";
                }
                echo "</td></tr><tr><td class=\"crqtableodd\">";
                echo "<label>Contact Info:<input style=\"color:gray;\" 
                    id=\"crqemail\" type=\"text\" ";
                echo "name=\"contact\" width=\"10\" value=\"Enter email\" 
                    onfocus=\"if(this.value=='Enter email')";
                echo "{this.value='';this.style.color='black'}\" onblur=\"if(this.value=='')
                    {this.value='Enter email';this.style.color='gray'}\"></label><br>";
                $default = $CFG->classrequestor_mailinst_default;
                echo "<label><input type=checkbox name=mailinst 
                    value=1 " . ($mailinst_default? "checked" : '') . ">
                    &nbsp;Send Email to Instructor(s)</label><br>\n";

                echo "</td><td class=\"crqtableodd\" style=\"text-align:right\" >
                    <input type=\"submit\" ";
                echo "value=\"Submit Course\" onclick=\"if(form.crqemail.value=='Enter email')
                    form.crqemail.value=''\" ";
                if($subj == "NULL")echo "disabled=\"true\" ";
                echo "></form>";
                echo "</td></tr>";
            }
            echo "</tbody></table>";
        }
    }
}

if(!empty($viewdeptform)) {
    get_course_in_dept($viewdeptform['group2']['term'],$viewdeptform['group2']['subjarea'],$db_conn);
}

function display_build_live_classes($build_or_live, $buildform){
    global $CFG;
    global $PAGE;
    global $DB;
    $recflag=0;
    $department=$buildform['group2']['department'];
    $term = $buildform['group2']['term'];
    if($department == '0') {
        if($build_or_live==1){ // from build queue
            if($rs=$DB->get_records_sql("select * from ".$CFG->prefix."ucla_request_classes where 
                action like 'build' and (status like 'pending' or status like 'processing') 
                and term like '$term' ")) {
                $recflag=1;
            }
        } else { // from live queue
            if($rs=$DB->get_records('ucla_request_classes',array('status'=>'done', 
                'term'=>$term), 'department,course')){
                $recflag=1;
            }
        }
    } else {
        if($build_or_live==1){
            if($rs=$DB->get_records_sql("SELECT * FROM `".$CFG->prefix."ucla_request_classes` 
                WHERE `department` LIKE '$department'  AND  `action` LIKE 'build'  AND (status like 
                'pending' or status like 'processing') order by 'department' ")) {
                $recflag=1;
            }
        }
        else {
            if($rs=$DB->get_records('ucla_request_classes', array('department'=>$department, 
                'status'=>'done', 'term'=>$term),'department')){
                $recflag=1;
            }
        }
    }
    if($recflag = 0)
    {
        echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">The 
            queue is empty.</div></td></tr></tbody></table>";
    } else {
        echo <<< END

    <table>
        <tbody>
            <tr>
                <td class="crqtableeven" colspan="6" align="center">
END;
        if($build_or_live==1){
            echo get_string('viewtobebuilt', 'report_courserequestor');
        } else {
            echo get_string('viewlivecourses', 'report_courserequestor');
        }
        echo <<< END
                </td>
            </tr>
            <tr>
                <td class="crqtableodd" width="100" ><strong>SRS</strong></td>
                <td class="crqtableodd" width="150" ><strong>COURSE</strong></td>
                <td class="crqtableodd"><strong>DEPARTMENT</strong></td>
                <td class="crqtableodd" width="150"><strong>INSTRUCTOR</strong></td>
                <td class="crqtableodd"><strong>TYPE</strong></td>
                <td class="crqtableodd"></td>
            </tr>
END;
        foreach($rs as $row2) {
            $srs = rtrim($row2->srs);
            echo "<form method=\"POST\" action=\"".$PAGE->url."\">";
            if ($build_or_live==1) {
                echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
            }
            echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
            echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
            echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
            $xlist="";
            if ($row2->crosslist == 1) {
                $xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
            }
            if ($build_or_live==1) {
                $coursetype=" <span class=\"crqbedlive\">$row2->status</span>";
            } else {
                $coursetype=" <span class=\"crqbedlive\">Live</span>";
            }
            
            echo "<tr class=\"crqtableunderline\"><td>".rtrim($row2->srs)."</td>
                <td>".rtrim($row2->course)."</td>";
            echo "<td>".rtrim($row2->department)."</td><td>".rtrim($row2->instructor)."</td>";
            if ($build_or_live==1) {
                echo "<td>".$coursetype.$xlist."</td><td><input type=\"submit\" value=\"Delete\">
                    </td></tr></form>";
            } else {
                echo "<td>".$coursetype.$xlist."</td></tr></form>";
            }
        }

        echo "</tbody></table>";
    }
}

if(!empty($buildform)) {
    if(strcmp((string)$buildform['group2']['livebuild'], 'live')==0){
        display_build_live_classes(0, $buildform);
    } else {
        display_build_live_classes(1, $buildform);
    }
}

if(optional_param('action',NULL,PARAM_ALPHANUM)=="deletecourse") {
    delete_course_in_queue();
}

if(optional_param('action',NULL,PARAM_ALPHANUM)=="courserequest") {
    $term =  optional_param('term',NULL,PARAM_ALPHANUM);
    $srs = optional_param('srs',NULL,PARAM_ALPHANUM);
    if((isset($existingcourse[$srs]) && $existingcourse[$srs]) 
        || (isset($existingaliascourse[$srs]) && $existingaliascourse[$srs])) {
    echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">";
    echo "THIS SRS NUMBER HAS BEEN SUBMITTED TO CREATE A COURSE. <BR> PLEASE ENTER 
        A NEW SRS NO.</div></td></tr></tbody></table>";
    } else{
        $instructor=optional_param('instname',NULL,PARAM_TEXT);
        
        if(optional_param('hidden',NULL,PARAM_ALPHANUM)) {
            $hidden = optional_param('hidden',NULL,PARAM_ALPHANUM);
        } else {
            $hidden = 0;
        }
        
        if(optional_param('mailinst',NULL,PARAM_ALPHANUM)) {
            $mailinst = optional_param('mailinst',NULL,PARAM_ALPHANUM);
        } else {
            $mailinst = 0;
        }
        
        if(optional_param('forceurl',NULL,PARAM_ALPHANUM)) {
            $forceurl = optional_param('forceurl',NULL,PARAM_ALPHANUM);
        } else {
            $forceurl = 0;
        }
        
        if(optional_param('nourlupd',NULL,PARAM_ALPHANUM)) {
            $nourlupd = optional_param('nourlupd',NULL,PARAM_ALPHANUM);
        } else {
            $nourlupd = 0;
        }
        
        $crosslist=optional_param('xlist',NULL,PARAM_ALPHANUM);
        $action=optional_param('actionrequest',NULL,PARAM_ALPHANUM);
        $aliascount=optional_param('aliascount',NULL,PARAM_ALPHANUM);
        $department=optional_param('department',NULL,PARAM_ALPHANUM);
        $course=optional_param('course',NULL,PARAM_ALPHANUM);
        $contact=optional_param('contact',NULL,PARAM_EMAIL);
        $ctime=time();
                
        $recorddata->term = $term;
        $recorddata->srs = $srs;
        $recorddata->course = $course;
        $recorddata->department = $department;
        $recorddata->instructor = addslashes($instructor);
        $recorddata->contact = addslashes($contact);
        $recorddata->crosslist = $crosslist;
        $recorddata->added_at = $ctime;
        $recorddata->action = $action;
        $recorddata->status = 'pending';
        $recorddata->mailinst = $mailinst;
        $recorddata->hidden = $hidden;
        $recorddata->force_urlupdate = $forceurl;
        $recorddata->force_no_urlupdate = $nourlupd;
        $DB->insert_record('ucla_request_classes', $recorddata);
        
        // CROSSLISTING: MANUAL or MULTIPLE host-alias ENTRY
        if($crosslist == 1) {
            while($aliascount >= 1) {
                $alias="alias".$aliascount;
                $aliassrs=optional_param($alias,NULL,PARAM_ALPHANUM);
                if($aliassrs != "" && !is_null($aliassrs)) {
                    $crosslistdata->term = $term;
                    $crosslistdata->srs = $srs;
                    $crosslistdata->aliassrs = $aliassrs;
                    $crosslistdata->type = 'joint';
                    $DB->insert_record('ucla_request_crosslist', $crosslistdata);
                    
                }
                $aliascount--;
            }

        }
        echo "<table><tr><td>";
        echo "<div class=\"crqbluemsg\">COURSES IN QUEUE TO BE BUILT</div></td></tr>";
                echo "<tr><td>";
        get_courses_to_be_built();
        echo "</td></tr></table>";

        $message = "$srs  of term $term needs to be built";

        $message = "your request for creating a new CCLE course ($srs) has been submitted. Thanks.";

    }
}


function get_courses_to_be_built()
{
    global $DB;
    global $CFG;
    global $PAGE;
    $recflag=0;
    $department=optional_param('department',NULL,PARAM_ALPHANUM);
    $term = optional_param('term',NULL,PARAM_ALPHANUM);
    if($department == '0') {
        if($rs=$DB->get_records_sql("select * from ".$CFG->prefix."ucla_request_classes 
            where action like 'build' and (status like 'pending' or status like 'processing') 
            and term like '$term' ")){
            $recflag=1;
        }
    }
    else {
        if($rs=$DB->get_records_sql("SELECT * FROM `".$CFG->prefix."ucla_request_classes` 
            WHERE `department` LIKE '$department'  AND  `action` LIKE 'build'  
            AND (status like 'pending' or status like 'processing') order by 'department' ")){
            $recflag=1;
        }
    }
    if($recflag = 0) {
        echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">The 
            queue is empty. All courses have been built as of now.</div></td></tr></tbody></table>";
    } else {
        echo <<< END
    <table>
        <tbody>
            <tr>
                <td class="crqtableeven" colspan="6" align="center">
END;
        echo get_string('viewtobebuilt', 'report_courserequestor');
        echo <<< END
                </td>
            </tr>
            <tr>
                <td class="crqtableodd" width="100" ><strong>SRS</strong></td>
                <td class="crqtableodd" width="150" ><strong>COURSE</strong></td>
                <td class="crqtableodd"><strong>DEPARTMENT</strong></td>
                <td class="crqtableodd" width="150"><strong>INSTRUCTOR</strong></td>
                <td class="crqtableodd"><strong>TYPE</strong></td>
                <td class="crqtableodd"></td>
            </tr>
END;
        foreach($rs as $row2)
        {
            $srs = rtrim($row2->srs);
            echo "<form method=\"POST\" action=\"".$PAGE->url."\">";
            echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
            echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
            echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
            echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
            $xlist="";
            if ($row2->crosslist == 1)
            {
                $xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
            }
            $coursetype=" <span class=\"crqbedlive\">$row2->status</span>";
            
            echo "<tr class=\"crqtableunderline\"><td>".rtrim($row2->srs)."</td>
                <td>".rtrim($row2->course)."</td><td>".rtrim($row2->department)."</td>
                <td>".rtrim($row2->instructor)."</td><td>".$coursetype.$xlist."</td>
                <td><input type=\"submit\" value=\"Delete\"></td></tr></form>";
        }

            echo "</tbody></table>";
    }
}

function delete_course_in_queue()
{
    global $DB;
    global $CFG;
    $srs = optional_param('srs',NULL,PARAM_ALPHANUM);
    $DB->delete_records('ucla_request_classes', array('srs'=>$srs));
    $DB->delete_records('ucla_request_crosslist', array('srs'=>$srs));
    
    get_courses_to_be_built();
}

function get_course_in_dept($term,$subjarea,$db_conn){

global $CFG;
global $PAGE;
$term=rtrim($term);
$subjarea=rtrim($subjarea);

$qr= odbc_exec($db_conn, "EXECUTE CIS_courseGetAll '$term','$subjarea'") or die('access denied');

$rows = array();

while ($row=odbc_fetch_object($qr))
{
$rows[] = $row;
}
odbc_free_result($qr);

$mailinst_default = $CFG->classrequestor_mailinst_default;
    $forceurl_default = $CFG->classrequestor_forceurl_default;
    $nourlupd_default = $CFG->classrequestor_nourlupd_default;
    $hidden_default = get_config('moodlecourse')->visible;
    
echo "<form method=\"POST\" action=\"".$PAGE->url."\">";
echo <<< END

<table>
<thead>
    <tr>
            
    <td class="crqtableodd" colspan="4">DEPARTMENT: <strong> $subjarea </strong></td>
    </tr>
    <tr>
        <td class="crqtableeven">
END;
echo "<label><input type=checkbox name=mailinst value=1 " . ($mailinst_default? "checked" : '') . ">
&nbsp;Send Email to Instructor(s)</label>
    </td>
    <td class=\"crqtableeven\">
        <label><input type=checkbox name=hidden value=1 " . (!$hidden_default? "checked" : '') . ">
        &nbsp;Build as Hidden</label>
    </td>";
echo <<< END
    <td class="crqtableeven" colspan="2"  align="right">
        <label>
        Department Contact:<input style="color:gray;" type=test name=contact 
        value='Enter email' id="crqemail" onfocus="if(this.value=='Enter email')
        {this.value='';this.style.color='black'}" onblur="if(this.value=='')
        {this.value='Enter email';this.style.color='gray'}" >
        </label>
    </td>
    </tr>
    <tr >
    <td class="crqtableeven" colspan="1">
END;

echo "<label><input type=checkbox name=forceurl value=1 " . ($forceurl_default? "checked" : '') . ">
    &nbsp;Force URL Update</label>
    </td>";
echo <<< END
    <td class="crqtableeven" colspan="1">
END;

echo "<label><input type=checkbox name=nourlupd value=1 " . ($nourlupd_default? "checked" : '') . ">
    &nbsp;Prevent URL Update</label>
    </td>";
echo <<< END
    <td class="crqtableeven" colspan="2" align="right">
    <input type="submit" value="Build Department" 
        onclick="if(form.crqemail.value=='Enter email')form.crqemail.value=''">
    
    </td>
    </tr>
</thead>
<tbody>
    <tr>
    <td class="crqtableodd" width="210"><strong>INSTRUCTOR</strong></td>
    <td class="crqtableodd" width="150"><strong>COURSE</strong></td>
    <td class="crqtableodd"><strong>CROSSLISTED WITH</strong></td>
    <td class="crqtableodd"><strong>BUILD</strong></td>
    </tr>


END;

$totalrows =count($rows);
$count=1;

foreach ($rows as $row)
{
    get_course_details($term,$row->srs,$count,$db_conn);
    $count++;
}
echo "</tbody>";
echo "<tfoot>";
echo "<tr><td colspan=\"4\" class=\"crqtableodd\">";
echo "<input type=\"hidden\" name=\"count\" value=\"$totalrows\">";
echo "<input type=\"hidden\" name=\"action\" value=\"builddept\">";

echo "</td></tr>";
echo "</tfoot>";
echo "</table>";
echo "</form>";
}

function get_course_details($term,$srs,$count,$db_conn)
{
    global $CFG;
    global $PAGE;
    $xlistexists = 0;
    $xlist_info = file( "http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/getConSched?user=ssc&pass=zx12as&term=$term&SRS=$srs");
    $i=0;
    $i = count($xlist_info);

    while ($i >0 && isset($xlist_info[$i])) {
        if(preg_match('/^[0-9]{9}$/',$xlist_info[$i])) {
            $xlistexists=1;
        }
        $i--;
    }

    $i = count($xlist_info);

    $qr= odbc_exec ($db_conn, "EXECUTE ccle_CourseInstructorsGet '$term', '$srs' ");
    $inst_full="";

    $rows = array();

    while ($row=odbc_fetch_object($qr)) {
        $rows[]=$row;
    }

    odbc_free_result($qr);

    foreach($rows as $row) {
        if ($row->last_name_person != "" AND $row->first_name_person != ""){
            $inst_full .= " ".trim($row->last_name_person).", ".trim($row->first_name_person)." ";
        }
    }

    if ($inst_full == ""){$inst_full = "Not Assigned";}

    $result = odbc_exec ($db_conn,"EXECUTE ccle_getClasses '$term','$srs'");
    $rows = array();
    while ($row=odbc_fetch_object($result)) {
        $rows[] = $row;
    }
    odbc_free_result($result);

    foreach ($rows as $row) {
        $subj = rtrim($row->subj_area);
        $subj=preg_replace('/\s/','',$subj);
        $type = rtrim($row->acttype);
        $num = rtrim($row->coursenum);
        if ($num > 495){ continue;}
        if ($subj == "PHYSICS" && $num > 295) {continue;}
        $sect = rtrim($row->sectnum);
        $title = rtrim($row->coursetitle);
        $description  = rtrim($row->crs_desc);
        $url = $row->URL;
        $url=preg_replace('/\s/','+',$url);
        $session = rtrim($row->session);
        $course = $subj.$num.'-'.$sect;

        echo "<input type=\"hidden\" name=\"srs$count\" value=$srs>";
        echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
        echo "<input type=\"hidden\" name=\"inst$count\" value=\"$inst_full\">";
        echo "<input type=\"hidden\" name=\"department\" value=\"$subj\">";
        echo "<input type=\"hidden\" name=\"course$count\" value=\"$course\">";
        echo "<tr class=\"crqtableunderline\" ><td>$inst_full";      // INSTRUCTOR COLUMN

        if($course ==""){$course="NULL";}

        echo "</td><td>$course";

        if($xlistexists){
            echo "</td><td>";
            $aliascount=0;

            while ($i!=0 && isset($xlist_info[$i]))
            {
                if(preg_match('/^[0-9]{9}$/',$xlist_info[$i]))
                {
                $aliascount++;
                $srs=ltrim($xlist_info[$i]);
                $srs=rtrim($srs);
                $srs=substr($srs,5,9);

                $result1 = odbc_exec ($db_conn, "EXECUTE ccle_getClasses '$term','$srs' ");

                $rows = array();
                while ($row=odbc_fetch_object($result1)) {
                    $rows[] = $row;
                }
                odbc_free_result($result1);

                foreach ($rows as $row1) {
                    $subj1 = rtrim($row1->subj_area);
                    $num1 = rtrim($row1->coursenum);
                    $sect1 = rtrim($row1->sectnum);

                    $course1 = $subj1.$num1.'-'.$sect1;
                }

                echo "<input type=\"checkbox\" name=\"alias$aliascount$count\" value=\"$srs\" 
                    checked> $course1 <br>";
                }
                $i--;
            }
            echo "<input type=\"hidden\" name=\"aliascount$count\" value = \"$aliascount\" >";
        }
        else{
            echo "</td><td><input type=\"text\" name=\"alias1$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"text\" name=\"alias2$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"text\" name=\"alias3$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"hidden\" name=\"aliascount$count\" value = \"3\" >";
        }
        echo "</td><td><input type=\"checkbox\" name=\"addcourse$count\" value=$srs checked>";
        echo "</td></tr>";
    }
}

$cnt=1;
if(optional_param('action',NULL,PARAM_ALPHANUM)){
    if(optional_param('action',NULL,PARAM_ALPHANUM)=="builddept") {
        $count = optional_param('count',NULL,PARAM_ALPHANUM);
        $crse = optional_param("course$cnt",NULL,PARAM_ALPHANUM);

        if(optional_param('hidden',NULL,PARAM_ALPHANUM)) {
            $hidden = optional_param('hidden',NULL,PARAM_ALPHANUM);
        } else {
            $hidden = 0;
        }
        
        if(optional_param('mailinst',NULL,PARAM_ALPHANUM)) {
            $mailinst = optional_param('mailinst',NULL,PARAM_ALPHANUM);
        } else {
            $mailinst = 0;
        }
        
        if(optional_param('forceurl',NULL,PARAM_ALPHANUM)) {
            $forceurl = optional_param('forceurl',NULL,PARAM_ALPHANUM);
        } else {
            $forceurl = 0;
        }
        
        if(optional_param('nourlupd',NULL,PARAM_ALPHANUM)) {
            $nourlupd = optional_param('nourlupd',NULL,PARAM_ALPHANUM);
        } else {
            $nourlupd = 0;
        }
        
        while($cnt<$count && optional_param("srs$cnt",NULL,PARAM_ALPHANUM)) {
            $aliascount=optional_param("aliascount$cnt",NULL,PARAM_ALPHANUM);
            $isxlist=0;
            $y =0 ;
            $r=1;

            $term = optional_param('term',NULL,PARAM_ALPHANUM);
            $srs = optional_param("srs$cnt",NULL,PARAM_ALPHANUM);
            $instructor = optional_param("inst$cnt",NULL,PARAM_TEXT);
            $department = optional_param('department',NULL,PARAM_ALPHANUM);
            $course = optional_param("course$cnt",NULL,PARAM_ALPHANUM);
            $contact = optional_param('contact',NULL,PARAM_EMAIL);		
            
            if(optional_param("addcourse$cnt",NULL,PARAM_ALPHANUM)) {
                $addcourse = optional_param("addcourse$cnt",NULL,PARAM_ALPHANUM);
            } else {
                $addcourse = "";
            }
            
            
            $ctime=time();

            if($addcourse != ""){	
                if(isset($existingcourse[$srs]) || isset($existingaliascourse[$srs])) {
                    echo "<table><tr ><td ><div class=\"crqerrormsg\">$course has either 
                        been submitted for course creation or is a child course</div></td></tr></table>";
                } else {
                    $isxlist=0;
                    while($r<=$aliascount) {
                        $value="alias".$r.$cnt;
                        if($_POST[$value] != "") {
                            if(preg_match('/^[0-9]{9}$/',$_POST[$value])) {
                                $isxlist=1;
                            }
                        }
                        $r++;
                    }

                    $recorddata->term = $term;
                    $recorddata->srs = $srs;
                    $recorddata->course = $course;
                    $recorddata->department = $department;
                    $recorddata->instructor = addslashes($instructor);
                    $recorddata->contact = addslashes($contact);
                    $recorddata->crosslist = $isxlist;
                    $recorddata->added_at = $ctime;
                    $recorddata->action = 'build';
                    $recorddata->status = 'pending';
                    $recorddata->mailinst = $mailinst;
                    $recorddata->hidden = $hidden;
                    $recorddata->force_urlupdate = $forceurl;
                    $recorddata->force_no_urlupdate = $nourlupd;
                    $DB->insert_record('ucla_request_classes', $recorddata);
                    

                    $existingcourse[$srs]=1;

                    echo "<table><tr ><td ><div class=\"crqgreenmsg\">$course submitted to be built
                        </div></td></tr></table>";

                    if($isxlist==1) {
                        $r=1;
                        while($r<=$aliascount)
                        {
                            $value="alias".$r.$cnt;
                            $als = optional_param($value,NULL,PARAM_ALPHANUM);
                            //create a check so that the alias being entered 
                            //is not a host for some other crosslist
                            //also check that the host srs is not an alias for some other crosslist
                            if(isset($existingcourse[$als]) || isset($existingaliascourse[$als])){	
                                echo "<table><tr ><td ><div class=\"crqerrormsg\">Requested crosslist 
                                    $als for $course is already submitted - Individually OR as a child course
                                    </div></td></tr></table>";
                            } else if($als != ""){
                                $query1 = "INSERT INTO ".$CFG->prefix."ucla_request_crosslist
                                    (term,srs,aliassrs,type) values ('$term','$srs','$als','joint')";
                                $DB->execute($query1);

                                $existingaliascourse[$als]=1;

                                echo "<table><tr ><td ><div class=\"crqgreenmsg\">$course - 
                                    submitted for crosslisting with $als</div></td></tr></table>";
                            }
                            $r++;
                        }

                    }
                }
            }
            $cnt++;
        }

    }
}

?>
        </div>
    </div> <!-- end form output -->
</div> <!-- end course requestor -->
<?php
echo $OUTPUT->footer();
?>