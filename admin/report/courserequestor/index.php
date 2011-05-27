<?php
/*
 * Course Requestor form
 * Integrated into moodle as a plugin
 *
 * Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
 *
 */

require_once("../../../config.php");
require_once($CFG->libdir.'/adminlib.php');
//require_once($CFG->dirroot.'/admin/report/configmanagement/configmanagementlib.php');

// connect to Registrar
$db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser , $CFG->registrar_dbpass) or die( "ERROR: Connection to Registrar failed.");

$term = $CFG->currentterm; 

// update from class_requestor
include("cr_lib.php");

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

/*DEPRECATED FROM 1.9
$adminroot = admin_get_root();
admin_externalpage_setup('courserequestor');
admin_externalpage_print_header($adminroot);
*/
?>

<link rel="stylesheet" href="requestor.css" />
<script language="JavaScript" type="text/javascript">

function checkform ( form )
{
  if (form.srs.value == "") {
    //alert( "Please enter srs number" );
    //form.srs.focus();
    form.srs.value = "Please enter SRS number";
    form.srs.style.border = "1px red solid";
    return false ;
  }
  return true ;
}
function clearform( btn )
{
    if(btn.value == "Please enter SRS number") {
        btn.value = "";
        btn.style.border = "1px solid gray";
    }
}
</script>

<div class="headingblock header crqpaddingbot" >
    <?php echo get_string('coursereqbuildclass', 'report_courserequestor') ?>
</div>
<div class="generalbox categorybox box crqdivwrapper" style="display:block;" >
    <div class="crqcenterbox">
<?php
    $string=$CFG->wwwroot;
    $build_dept = $string."/admin/report/courserequestor/builddept.php";
    $course_requestor =  $string."/admin/report/courserequestor/index.php";
    $addCrosslist = $string."/admin/report/courserequestor/addcrosslist.php";

    echo "<a href=\"$course_requestor\">".get_string('buildcourse', 'report_courserequestor')."</a> | ";
	echo "<a href=\"$build_dept\">".get_string('builddept', 'report_courserequestor')."</a> | ";
    echo "<a href=\"$addCrosslist\">".get_string('addcrosslist', 'report_courserequestor')."</a> ";
    
    
    // Begin UCLA Modification - CCLE 1879 - Handle empty GET variables to suppress debug errors
    if(isset($_GET['srs']) == FALSE ) {
        $_GET['srs'] = '';
    }
    if(isset($_GET['term']) == FALSE ) {
        $_GET['term'] = '';
    }
    
    if(isset($_GET['action']) == FALSE ) {
        $_GET['action'] = '';
    }
    // End UCLA Modification
?>
    </div>
    <div class="divclear" >
        <?php
            // first form to submit the course
        ?>
        <form method="GET" onsubmit="return checkform(this)" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <fieldset class="crqformodd">
                <legend></legend>
                <label >TERM:
                    <?php print_term_pulldown_box(); ?>
                </label>
                <label>SRS:  <input type="text" name="srs" value="<?php echo $_GET['srs'] ?>" size="25"  onfocus="clearform(this)" ></label>
                <input type="hidden" name="action" value="fillform">
                <input type="submit" value="Continue"><br/>&nbsp;
            </fieldset>
        </form>
    </div>
    <div class="" >
        <fieldset class="crqformeven">
            <legend></legend>
            SRS# Lookup <a href="http://www.registrar.ucla.edu/schedule/" target="_blank">Registrar's Schedule of Classes</a>
        </fieldset>
    </div>
    <div class="crqdivclear" >
	<?php
            // SRS Look up form for Live courses
            // shouldn't we be using this??
            // echo "this".$SERVER['PHP_SELF'];
        ?>

        <form method="GET" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
            <fieldset class="crqformodd" >
                <legend></legend>
                <label>TERM:
                    <?php print_term_pulldown_box(); ?>
                </label>
                <label>DEPARTMENT:
                    <select name="department" >
                    <option value=''>ALL</option>
                    <?php
                    $rs=$DB->get_records_sql("select distinct department from mdl_ucla_request_classes order by department");

                    foreach ($rs as $row) {
                            echo "<option value='$row->department'>$row->department</option>";
                    }
                    
                    ?>
                    </select>
                </label>
                <input type="hidden" name="action" value="viewlivecourses">
                <input type="submit" value="View Live Courses ">
            </fieldset>
        </form>
    </div>
    <div class="crqdivclear">
        <?php
        // SRS Look up for Courses that are built
        ?>
        <form method="GET" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <fieldset class="crqformeven">
                <legend></legend>
                <label>TERM:
                    <?php print_term_pulldown_box(); ?>
                </label>

                <label>DEPARTMENT:
                    <select name="department" >
                        <option value=''>ALL</option>
                        <?php
                        $rs=$DB->get_records_sql("select distinct department from mdl_ucla_request_classes order by department");

                        foreach($rs as $row) {
                                echo "<option value='$row->department'>$row->department</option>";
                        }
                        ?>
                    </select>
                </label>

                <input type="hidden" name="action" value="viewcoursestobebuilt">
                <input type="submit" value="View Courses to be built">
            </fieldset>
        </form>

    </div>
    <div class="crqdivclear">
        <?php /*
            // SRS Look up for Courses that are in Preview Stage
        ?>

	<!-- <form method="GET" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <fieldset class="crqformodd">
                <legend></legend>
                <label>TERM:
                    <?php print_term_pulldown_box(); ?>
                </label>
                <label>DEPARTMENT:
                    <select name="department" >
                        <option value=''>ALL</option>
                        <?php
                        $rs=$DB->get_records_sql("select distinct department from mdl_ucla_request_classes order by department");

                        foreach($rs as $row) {
                                echo "<option value='$row->department'>$row->department</option>";
                        }
						*/
                        ?>
                <!--    </select>
                </label>
		<input type="hidden" name="action" value="viewpreviewcourses">
		<input type="submit" value="View Preview Courses">
            </fieldset>
        </form> -->

    </div>

    <div class="crqdivclear" >
        <div class="crqfrmoutput" align="center" >

<?php

// THE COURSE REQUEST FORM STARTS HERE ----------------------------(action= fillform or action= NULL)

// CREATE A HASH OF ALREADY INSERTED COURSE REQUESTS and preview courses

    $crs = $DB->get_records_sql(" SELECT srs FROM mdl_ucla_request_classes WHERE ( term LIKE '$_GET[term]' ) AND ACTION LIKE '%uild' ");
	foreach($crs as $rows)
	{
		$srs=rtrim($rows->srs);
		$existingcourse[$srs]=1;
	}

    $crs2 = $DB->get_records_sql("select aliassrs from mdl_ucla_request_crosslist where term like '$_GET[term]' ");
	foreach($crs2 as $rows)
	{
		$srs=rtrim($rows->aliassrs);
		$existingaliascourse[$srs]=1;
	}

	/*
    $crs1 = $DB->get_records_sql("select srs from mdl_ucla_request_classes where preview like '1' and term like '$_GET[term]'");
	foreach($crs1 as $rows1)
	{
		$previewcourse[rtrim($rows1->srs)]=1;
	}
	*/
	
    if($_GET["action"]=="fillform")
    {
        echo "<table style=\"width:90%\" ><tbody>";

        $term = rtrim($_GET['term']);
        $srs = rtrim($_GET['srs']);

        if((isset($existingcourse[$srs]) && $existingcourse[$srs]) 
            || (isset($existingaliascourse[$srs]) && $existingaliascourse[$srs]))
        {
                echo "<tr><td class=\"crqtableodd\" colspan=\"4\"><div class=\"crqerrormsg\">THIS SRS NUMBER HAS BEEN SUBMITTED TO CREATE A COURSE. <br /> PLEASE ENTER A NEW SRS NO.</div></td></tr></tbody></table>";
        }
        else
        {
            $query = "EXECUTE ccle_CourseInstructorsGet '$term', '$srs' ";
            $inst = odbc_exec ($db_conn, $query);
            $inst_full="";

            $unlisted_count = 0;
            $instructors = array();
            while ($row=odbc_fetch_object($inst) ) {
                $instructors[] = $row->last_name_person . ',' . $row->first_name_person . ';';
                if ($row->role == '01') {
                    if( $row->last_name_person != "" || $row->first_name_person != ""){
                        $inst_full .= rtrim($row->last_name_person).", ".rtrim($row->first_name_person)."<br>";
                    }
                    // throw a warning if there is no instructor name listed
                    if( empty($row->last_name_person ) || empty($row->first_name_person) ){
                        echo "<tr><td class=\"crqtableodd\" colspan=\"2\"><div class=\"crqerrormsg\">Warning: there is an incomplete instructor name for this SRS " . $row->srs . "<br>Make sure that your all information is complete</div></td></tr>";
                    }
                } else {
                    if (trim($row->last_name_person) == "" && trim($row->first_name_person) == ""){
                        $unlisted_count++;
                        continue;
                    }
                    
                    if ($row->last_name_person != "") {
                        $inst_full .= rtrim($row->last_name_person);
                    }
                    
                    if (trim($row->last_name_person) != "" && trim($row->first_name_person) != ""){
                        $inst_full .= ", ";
                    }

                    
                    if ($row->first_name_person != "") {
                        $inst_full .= rtrim($row->first_name_person)."<br>";
                    }
                }
            }
            
            if ($unlisted_count != 0) {
                $inst_full_display = $inst_full . "<div class=\"crqerrormsg\">$unlisted_count Unlisted Roles</div>";
                $inst_full .= "$unlisted_count Unlisted Roles";
            } else {
                $inst_full_display = $inst_full;
            }
            
            
            
            if ($inst_full == ""){$inst_full = "Not Assigned";}
            odbc_free_result($inst);
            $query1= "EXECUTE ccle_getClasses '$term','$srs'" ;
            $result = odbc_exec ($db_conn, $query1);
            
            if ($row1=odbc_fetch_object($result))
            {
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
                echo "<form method=\"GET\" action=\"".$_SERVER['PHP_SELF']."\">";
                echo "<input type=\"hidden\" name=\"srs\" value=$_GET[srs]>";
                echo "<input type=\"hidden\" name=\"action\" value=\"courserequest\">";
                echo "<input type=\"hidden\" name=\"term\" value=\"$_GET[term]\">";
                echo "<input type=\"hidden\" name=\"instname\" value=\"$inst_full\">";

                echo "<div class=\"crqtableodd\">Instructor: <strong>" . $inst_full_display . "</strong></div>";
                echo "<div class=\"crqtableeven\"><label>Action: <select name=\"actionrequest\">";
                echo "<option value='build' selected>Build</option></select></label>
				<label><input type=checkbox name=hidden value=1 " . (!$hidden_default? "checked" : '') . ">&nbsp;Build as Hidden</label>
				</div>";
                echo "<div class=\"crqtableodd\">
				<label><input type=checkbox name=forceurl value=1 " . ($forceurl_default? "checked" : '') . ">&nbsp;Force URL Update</label>
				<label><input type=checkbox name=nourlupd value=1 " . ($nourlupd_default? "checked" : '') . ">&nbsp;Prevent URL Update</label>				
				</div>";
                if ($subj == "") { 
                    $subj="NULL";
                }
                
                echo "<div class=\"crqtableeven\"><label>Department:<input type=\"text\" name=\"department\" value = $subj width=\"10\"></label></div>";
                if ($course == "") { 
                    $course="NULL";
                }
                
                echo "<div class=\"crqtableodd\"><label>Course:<input type=\"text\" name=\"course\" value = $course width=\"10\"></label></div>";
                echo "</td><td class=\"crqtableeven\"> <div class=\"crqtableodd\">(Optional)</div>";
                echo "<div class=\"crqtableeven\">Crosslist";

                // CHECKING FOR CROSSLISTS
                $xlist_info = file( "http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/getConSched?user=ssc&pass=zx12as&term=$_GET[term]&SRS=$_GET[srs]");
                $i = count($xlist_info) - 1;
                $xlistexists=0;
                while ($i >0)
                {
                        if(preg_match('/[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/',$xlist_info[$i]))
                        {
                                $xlistexists=1;
                        }
                        $i--;
                }
                $i = count($xlist_info);
                if(!$xlistexists)
                {
                    $aliascount=5;
                    echo "<label><input type=\"radio\" name=\"xlist\" value = \"1\" >yes</label> <label><input type=\"radio\" name=\"xlist\" value = \"0\" checked>no</label></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias1\" size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableeven\"> Alias SRS<input type=\"text\" name=\"alias2\" size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias3\" size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableeven\"> Alias SRS<input type=\"text\" name=\"alias4\" size=\"20\" maxlength=\"9\"></div>";
                    echo "<div class=\"crqtableodd\"> Alias SRS<input type=\"text\" name=\"alias5\" size=\"20\" maxlength=\"9\"></div>";
                    echo "<input type=\"hidden\" name=\"aliascount\" value = \"$aliascount\" >";
                }
                else
                {
                    echo "<label><input type=\"radio\" name=\"xlist\" value = \"1\" checked>yes</label> <label><input type=\"radio\" name=\"xlist\" value = \"0\">no</label><br>";
                    echo "<br>Select SRS below to crosslist.<br><span style=\"color:red\" >Please uncheck the SRS you dont want crosslisted</span><br><br>";
                    $aliascount=0;
                    while ($i!=0)
                    {
                        if(isset($xlist_info[$i]) && preg_match('/[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/',$xlist_info[$i]))
                        {
                            $aliascount++;
                            $srs=ltrim($xlist_info[$i]);
                            $srs=rtrim($srs);
                            $srs=substr($srs,5,9);
                            $query3= "EXECUTE ccle_getClasses '$term','$srs'";
                            $result3 = odbc_exec ($db_conn, $query3);
                            while ($row3=odbc_fetch_object($result3))
                            {
                                $subj1 = rtrim($row3->subj_area);
                                $num1 = rtrim($row3->coursenum);
                                $sect1 = rtrim($row3->sectnum);
                                $course1 = $subj1.$num1.'-'.$sect1;
                            }
                            odbc_free_result($result3);
                            echo "<label><input type=\"checkbox\" name=\"alias$aliascount\" value=\"$srs\" checked> $course1 <span style=\"color:green\"> (SRS: $srs) </span></label><br>";
                        }
                        $i--;
                    }
                    echo "<input type=\"hidden\" name=\"aliascount\" value = \"$aliascount\" >";
                }
                echo "</td></tr><tr><td class=\"crqtableodd\">";
                echo "<label>Contact Info:<input style=\"color:gray;\" id=\"crqemail\" type=\"text\" name=\"contact\" width=\"10\" value=\"Enter email\" onfocus=\"if(this.value=='Enter email'){this.value='';this.style.color='black'}\" onblur=\"if(this.value==''){this.value='Enter email';this.style.color='gray'}\"></label><br>";
                // moved in from class_requestor
                $default = $CFG->classrequestor_mailinst_default;
                echo "<label><input type=checkbox name=mailinst value=1 " . ($mailinst_default? "checked" : '') . ">&nbsp;Send Email to Instructor(s)</label><br>\n";

                echo "</td><td class=\"crqtableodd\" style=\"text-align:right\" ><input type=\"submit\" value=\"Submit Course\" onclick=\"if(form.crqemail.value=='Enter email')form.crqemail.value=''\" ";
                if($subj == "NULL")echo "disabled=\"true\" ";
                echo "></form>";
                echo "</td></tr>";
                }
            echo "</tbody></table>";
        }//if not repeat srs
	}
	if($_GET["action"]=="viewcoursestobebuilt")
	{
		getCoursesToBeBuilt();
	}
	if($_GET["action"]=="viewlivecourses")
	{
		getLiveCourses();
	}
	/*
	if($_GET["action"]=="viewpreviewcourses")
	{
		getPreviewCourses();
	}*/
	/*
	if($_GET["action"]=="previewtolive")
	{
		makeLive();
	}*/
	
	if($_GET["action"]=="deletecourse")
	{
		deleteCourseInQueue();
	}
	/*
	if($_GET["action"]=="converttopreview")
	{
		makePreview();
	}
	*/
	if($_GET["action"]=="courserequest")
	{
		$term =  $_GET["term"];
		$srs = $_GET["srs"];
		if((isset($existingcourse[$srs]) && $existingcourse[$srs]) || (isset($existingaliascourse[$srs]) && $existingaliascourse[$srs])) {
	 	echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">THIS SRS NUMBER HAS BEEN SUBMITTED TO CREATE A COURSE. <BR> PLEASE ENTER A NEW SRS NO.</div></td></tr></tbody></table>";
	}
	else
	{
		$instructor=$_GET["instname"];
		// $preview=$_GET["preview"];
		
		if(isset($_GET["hidden"]))
		$hidden = $_GET["hidden"];
		else
		$hidden = 0;
		
		if(isset($_GET["mailinst"]))
		$mailinst = $_GET["mailinst"];
		else
		$mailinst = 0;
		
		if(isset($_GET["forceurl"]))
		$forceurl = $_GET["forceurl"];
		else
		$forceurl = 0;
		
		if(isset($_GET["nourlupd"]))
		$nourlupd = $_GET["nourlupd"];
		else
		$nourlupd = 0;	
		
		$crosslist=$_GET["xlist"];
		$action=$_GET["actionrequest"];
		$aliascount=$_GET["aliascount"];
		$department=$_GET["department"];
		$course=$_GET["course"];
		$contact=$_GET["contact"];
		$ctime=time();
                
                $query = "INSERT INTO mdl_ucla_request_classes(term,srs,course,department,instructor,contact,crosslist,added_at,action,status,mailinst,hidden,force_urlupdate,force_no_urlupdate) values ('$term','$srs','$course','$department','".addslashes($instructor)."','" .addslashes($contact). "',$crosslist,'$ctime','$action','pending','$mailinst','$hidden','$forceurl','$nourlupd')";

                $DB->execute($query);
		// CROSSLISTING: MANUAL or MULTIPLE host-alias ENTRY
		if($crosslist == 1) // why was this ($crosslist = 1) ?
		{
                    while($aliascount >= 1)
                    {
                        $alias="alias".$aliascount;
                        $aliassrs=$_GET[$alias];
                        if($aliassrs != "")
                        {
                            $query = "INSERT INTO mdl_ucla_request_crosslist(term,srs,aliassrs,type) values ('$term','$srs','$aliassrs','joint')";

                            $DB->execute($query);
                        }
                        $aliascount--;
                    }

		}
		echo "<table><tr><td>";
		echo "<div class=\"crqbluemsg\">COURSES IN QUEUE TO BE BUILT</div></td></tr>";
                echo "<tr><td>";
		getCoursesToBeBuilt();
		echo "</td></tr></table>";
		//echo "</td></tr></table>";
		$message = "$srs  of term $term needs to be built";
		//mail('nthompson@oid.ucla.edu', 'CCLE:New Course Request ', $message);
		$message = "your request for creating a new CCLE course ($srs) has been submitted. Thanks.";

	}
 }

	function makeLive()
	{
			global $DB;
            $srs = $_GET['srs'];
            $DB->execute("update mdl_ucla_request_classes set action = 'makelive', status='pending' where srs like '$srs' ");
            //getPreviewCourses();
            //$message = "$srs needs to be converted from preview to live";
            //mail('nthompson@oid.ucla.edu', 'CCLE:Preview to Live Request', $message);
	}

	function deleteCourseInQueue()
	{
			global $DB;
            $DB->execute("delete from mdl_ucla_request_classes where srs like '$_GET[srs]' ");
			$DB->execute("delete from mdl_ucla_request_crosslist where srs like '$_GET[srs]' ");
            getCoursesToBeBuilt();
	}

	/*
	function makePreview()
	{
            //$query= "update mdl_ucla_request_classes set action='makepreview', status='pending' where srs like '$_GET[srs]'";
            $query= "update mdl_ucla_request_classes set action='makepreview', status='pending' where srs like '$_GET[srs]'";
            $DB->execute($query);
            getLiveCourses();
	}

	function getPreviewCourses()
	{
		global $DB;
		$recflag=0;
		$department=$_GET['department'];

                echo $department;

                $term = $_GET['term'];

                if($department == "")
		{
                        if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where (action like '%uild'or action like 'makelive') AND (status like 'done') and (preview = 1)  and term like '$term' order by department,course")){$recflag=1;}
		}
		else
		{
                        if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE (`department` LIKE '$department') and (action like '%uild' OR action like 'makelive') AND (`preview` = 1)   AND (`status` LIKE 'done') and term like '$term' order by department ")){$recflag=1;}
		}

                if($recflag=0)
		{
			echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">No more Preview Courses in the database can be made Live</div></td></tr></tbody></table>";
		}
                else
		{
                    echo <<< END

            <table>
                <tbody>
                    <tr>
                        <td class="crqtableeven" colspan="6" align="center">
END;
                           echo get_string('viewpreviewcourses', 'report_courserequestor');
echo <<< END
                        </td>
                    </tr>
                    <tr>
                        <td class="crqtableodd" width="100" ><strong>SRS</strong></td>
                        <td class="crqtableodd" width="150" ><strong>COURSE</strong></td>
                        <td class="crqtableodd"><strong>DEPARTMENT</strong></td>
                        <td class="crqtableodd" width="150"><strong>INSTRUCTOR</strong></td>
                        <td class="crqtableodd" width="75"><strong>TYPE</strong></td>
                        <td class="crqtableodd"></td>
                    </tr>
END;

			foreach($rs as $row)
                        {
                            $srs=rtrim($row->srs);
                            $department=trim($row->department);
                            $xlist="";
                            if ($row->crosslist = 1)
                            {
                                    //$xlist= "<font color = blue><i>crosslisted</i></font>";
                                    $xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
                            }
                            echo "<tr class=\"crqtableunderline\"><td>";
                            echo "<form method=\"GET\" action=\"".$_SERVER['PHP_SELF']."\">";
                            echo "<input type=\"hidden\" name=\"action\" value=\"previewtolive\">";
                            echo "<input type=\"hidden\" name=\"srs\" value=\"$srs\">";
                            echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
                            echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
                            $check =$row->action;
                            if ($check == "makelive")
                            {
                                    echo rtrim($row->srs)."</td><td>".rtrim($row->course)."</TD><TD>".rtrim($row->department)."</TD><TD>".rtrim($row->instructor)."</TD><TD>".$xlist."</TD><td>Request already submitted</td></TR>";
                            }
                            else
                            {
                                    echo rtrim($row->srs)."</td><td>".rtrim($row->course)."</TD><TD>".rtrim($row->department)."</TD><TD>".rtrim($row->instructor)."</TD><TD>".$xlist."</TD><td><input type=\"submit\" value=\"Make Live.\"></td></TR>";
                            }
                            echo "</form>";
                        }
                        //echo "</table></td></tr></table>";
                        echo "</tbody></table>";
		}
	}
	*/

        function getCoursesToBeBuilt()
	{
			global $DB;
			$recflag=0;
			$department=$_GET['department'];
			$term = $_GET['term'];
			if($department == "")
			{
                            if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where action like '%uild' and (status like 'pending' or status like 'processing') and term like '$term' ")){$recflag=1;}
			}
			else
			{
                            if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE `department` LIKE '$department'  AND  `action` LIKE 'build'  AND (status like 'pending' or status like 'processing') order by 'department' ")){$recflag=1;}
			}
			if($recflag = 0)
			{
				echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">The queue is empty. All courses have been built as of now.</div></td></tr></tbody></table>";
			}
			else
			{

				//echo "<p><table><tr><td width=100></td><td><table border=1><tr bgcolor=black><td width=100 align=center><font color=white> SRS </TD><td align=center><font color=white> COURSE </TD><td align=center><font color=white> DEPARTMENT </TD><td align=center><font color=white> INSTRUCTOR </TD><td align=center><font color = white> TYPE </td></TR><tr><td></td></tr>";
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
					echo "<form method=\"GET\" action=\"".$_SERVER['PHP_SELF']."\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
					echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
					echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
					echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
					$xlist="";
					if ($row2->crosslist == 1)
					{
						//$xlist= "<font color = blue><i> - crosslisted</i></font>";
                                                $xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
					}
					$coursetype=" <span class=\"crqbedlive\">Live</span>";
					/*
					if ($row2->preview == 0)
					{
						//$coursetype=" <font color=green><i>(Live)</i></font>";
                                                $coursetype=" <span class=\"crqbedlive\">Live</span>";
					}
					else
					{
						//$coursetype=" <font color=brown><i>(Preview)</i></font>";
                                                $coursetype=" <span class=\"crqbedpreview\">Preview</span>";
					}*/
					echo "<tr class=\"crqtableunderline\"><td>".rtrim($row2->srs)."</td><td>".rtrim($row2->course)."</td><td>".rtrim($row2->department)."</td><td>".rtrim($row2->instructor)."</td><td>".$coursetype.$xlist."</td><td><input type=\"submit\" value=\"Delete\"></td></tr></form>";
				}
				//echo "</table></td></tr></table>";
                                echo "</tbody></table>";
			}
	}

        function getLiveCourses()
	{
		global $DB;
		$recflag=0;
		$department=$_GET['department'];
		$term = $_GET['term'];
		if($department == "")
                {
                        if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where status like 'done' and term like '$term' order by department,course")){$recflag=1;}
                }
		else
                {
                        if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE `department` LIKE '$department' AND `status` LIKE 'done' and term like '$term' order by 'department' ")){$recflag=1;}
                }
		if($recflag=0 )
                {
                        echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">The queue is empty</div></td></tr></tbody></table>";
                }
		else
                {
                        //echo "<p><table><tr><td width=100></td><td><table border=1><tr bgcolor=black><td width=100 align=center><font color=white> SRS </TD><td align=center><font color=white> COURSE </TD><td align=center><font color=white> DEPARTMENT </TD><td align=center><font color=white> INSTRUCTOR </TD><td align=center><font color = white> TYPE </td></TR><tr><td></td></tr>";
                        echo <<< END

            <table>
                <tbody>
                    <tr>
                        <td class="crqtableeven" colspan="6" align="center">
END;
                           echo get_string('viewlivecourses', 'report_courserequestor');
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
                                        echo "<form method=\"GET\" action=\"".$_SERVER['PHP_SELF']."\">";
                                        //echo "<input type=\"hidden\" name=\"action\" value=\"converttopreview\">";
                                        echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
                                        echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
                                        echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
                                        $xlist="";
                                        if ($row2->crosslist == 1)
                                        {
                                                //$xlist= "<font color = blue><i> - crosslisted </i></font>";
                                                $xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
                                        }
										$coursetype=" <span class=\"crqbedlive\">Live</span>";
										/*
                                        if ($row2->preview == 0)
                                        {
                                                //$coursetype=" <font color=green><i>(Live)</i></font>";
                                                $coursetype=" <span class=\"crqbedlive\">Live</span>";
                                        }
                                        else
                                        {
                                                //$coursetype=" <font color=brown><i>(Preview)</i></font>";
                                                $coursetype=" <span class=\"crqbedpreview\">Preview</span>";
                                        }
										*/
                                        echo "<tr class=\"crqtableunderline\"><td>".rtrim($row2->srs)."</td><td>".rtrim($row2->course)."</td><td>".rtrim($row2->department)."</td><td colspan='2'>".rtrim($row2->instructor)."</td><td>".$coursetype.$xlist."</td></TR></form>";
                                }
				//echo "</table></td></tr></table>";
                                echo "</tbody></table>";
			}
	}


?>
        </div>
    </div> <!-- end form output -->
</div> <!-- end course requestor -->
<?php
echo $OUTPUT->footer();

?>