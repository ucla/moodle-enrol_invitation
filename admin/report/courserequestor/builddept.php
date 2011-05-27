<?php
/*
Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
*/

require_once("../../../config.php");
require_once($CFG->libdir.'/adminlib.php');
//require_once($CFG->dirroot.'/admin/report/configmanagement/configmanagementlib.php');

// Connect to Registrar
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


/*** DEPRECATED FROM MOODLE 1.9
// Prepare and load Moodle Admin interface
$adminroot = admin_get_root();

// This requests the default page which at this point is
// Keep this the same so that we're able to switch
admin_externalpage_setup('courserequestor');
admin_externalpage_print_header($adminroot);

****/
?>

<link rel="stylesheet" href="requestor.css" />

<div class="headingblock header crqpaddingbot" >
<?php echo get_string('coursereqbuilddept', 'report_courserequestor') ?>
</div>

<div class="generalbox categorybox box crqdivwrapper" >
<div class="crqcenterbox">
<?php
	$string=$CFG->wwwroot;
	$build_dept = $string."/admin/report/courserequestor/builddept.php";
	$course_requestor =  $string."/admin/report/courserequestor/index.php";
	$addCrosslist = $string."/admin/report/courserequestor/addcrosslist.php";

	echo "<a href=\"$course_requestor\">".get_string('buildcourse', 'report_courserequestor')."</a> | ";
	echo "<a href=\"$build_dept\">".get_string('builddept', 'report_courserequestor')."</a> | ";
	echo "<a href=\"$addCrosslist\">".get_string('addcrosslist', 'report_courserequestor')."</a> ";
?>
</div>

<div>
	<?php
	// Begin Term & Subject Area form
	?>
	<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<fieldset class="crqformodd" >
		<legend></legend>

		<label>TERM:
		<?php print_term_pulldown_box(); ?>
		</label>
		<label>SUBJECT AREA:
		<select name="subjarea">
		<?php

		if(isset($_POST['term'])){
		if ($_POST['term'] == "") {$_POST['term']="$CFG->currentterm";}
		$term = $_POST['term'];
			}
		$qr= odbc_exec($db_conn, "EXECUTE CIS_subjectAreaGetAll '$term'");
		$row = array();
		$rows = array();

		while (odbc_fetch_into($qr, $row))
		{
			$rows[] = $row;
		}
		odbc_free_result($qr);

		foreach ($rows as $row)
		{
			echo "<option value='$row[0]'>$row[0] - $row[1]</option>";
		}
		?>
		</select>
		</label>

		<input type="hidden" name="action" value="viewdept">
		<input type="submit" value="View Department">
	</fieldset>
	</form>
</div>
<div class="crqdivclear">
	<?php
	// Begin View Live Courses form
	?>
	<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
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
	// Begin View Courses to be built Form
	?>
	<form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
	<fieldset class="crqformodd">
		<legend></legend>

	<label>TERM:
		<?php print_term_pulldown_box(); ?>
	</label>
	<label>
		DEPARTMENT:
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
<!-- <?php /**
	// Begin View Preview Courses Form
	?>
	<form method="POST" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
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

		foreach ($rs as $row) {
			echo "<option value='$row->department'>$row->department</option>";
		}
		**/
		?>
		</select>
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

// CREATE A HASH OF ALREADY REQUESTED OR BUILT COURSES

	if((isset($_POST['term']))){
	$crs = $DB->get_records_sql("select srs from mdl_ucla_request_classes where term like '$_POST[term]' and action like 'build' ");
	foreach ($crs as $rows)
	{
		$srs=rtrim($rows->srs);
		$existingcourse[$srs]=1;
	}
	}
	
	if((isset($_POST['term']))){
	$crs1 = $DB->get_records_sql("select aliassrs from mdl_ucla_request_crosslist where term like '$_POST[term]' ");
	foreach ($crs1 as $rows)
	{
	
		$srs=rtrim($rows->aliassrs);
		$existingaliascourse[$srs]=1;
	}
	}
	
	if((isset($_POST["action"]))){
	if($_POST["action"]=="viewdept")
	{
		if(isset($_POST['subjarea']))
		getCoursesInDept($_POST['term'],$_POST['subjarea'],$db_conn);
	}
	}
	
	$cnt=1;
	
	if((isset($_POST["action"])))
	if($_POST["action"]=="builddept")
	{
		$count = $_POST["count"];
		$crse = $_POST["course$cnt"];

		/*
		if($_POST["preview"])
		{
			$preview = '1';
		}
		else
		{
			$preview = '0';
		}
	*/
		if(isset($_POST["hidden"]))
			$hidden = $_POST["hidden"];
			else
			$hidden = 0;
		
			if(isset($_POST["mailinst"]))
			$mailinst = $_POST["mailinst"];
			else
			$mailinst = 0;
		
			if(isset($_POST["forceurl"]))
			$forceurl = $_POST["forceurl"];
			else
			$forceurl = 0;
		
			if(isset($_POST["nourlupd"]))
			$nourlupd = $_POST["nourlupd"];
			else
			$nourlupd = 0;
		
		
		while($cnt<$count && isset($_POST["srs$cnt"]))
		{
			$aliascount=$_POST["aliascount$cnt"];
			$isxlist=0;
			$y =0 ;
			$r=1;

			$term = $_POST["term"];
			$srs = $_POST["srs$cnt"];
			$instructor=$_POST["inst$cnt"];
			$department=$_POST["department"];
			$course=$_POST["course$cnt"];
			$contact=$_POST["contact"];		
			
			if(isset($_POST["addcourse$cnt"]))
	$addcourse = $_POST["addcourse$cnt"];
			else
			$addcourse = "";
			
			
			$ctime=time();

			if($addcourse != ""){
					
				if(isset($existingcourse[$srs]) || isset($existingaliascourse[$srs]))
				{
					//echo "<BR><FONT COLOR=RED>$course has either been submitted for course creation or is a child course </font>";
					echo "<table><tr ><td ><div class=\"crqerrormsg\">$course has either been submitted for course creation or is a child course</div></td></tr></table>";
				}
				else
				{
					$isxlist=0;
					while($r<=$aliascount)
					{
					$value="alias".$r.$cnt;
					if($_POST[$value] != "")
					{
						if(eregi('[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]',$_POST[$value]))
						{
						$isxlist=1;
						}
					}
					$r++;
					}

					$query = "INSERT INTO mdl_ucla_request_classes(term,srs,course,department,instructor,contact,crosslist,added_at,action,status,mailinst,hidden,force_urlupdate,force_no_urlupdate) values ('$term','$srs','$course','$department','$instructor','$contact','$isxlist','$ctime','Build','pending','$mailinst','$hidden','$forceurl','$nourlupd')";


					$DB->execute($query);

					$existingcourse[$srs]=1;

					echo "<table><tr ><td ><div class=\"crqgreenmsg\">$course submitted to be built</div></td></tr></table>";
					//echo "<FONT COLOR=GREEN>$course submitted to be built</font>";
					//echo "<br>";

					if($isxlist==1)
					{
					$r=1;
					while($r<=$aliascount)
					{
						$value="alias".$r.$cnt;
						$als = $_POST[$value];
						//create a check so that the alias being entered is not a host for some other crosslist
						//also check that the host srs is not an alias for some other crosslist
						if(isset($existingcourse[$als]) || isset($existingaliascourse[$als])){
							//echo "<br><br> <font color=red> Requested crosslist $als for $course is already submitted - Individually OR as a child course </font><br>";
						echo "<table><tr ><td ><div class=\"crqerrormsg\">Requested crosslist $als for $course is already submitted - Individually OR as a child course</div></td></tr></table>";
						}
						else if($als != ""){
						$query1 = "INSERT INTO mdl_ucla_request_crosslist(term,srs,aliassrs,type) values ('$term','$srs','$als','joint')";
						$DB->execute($query1);

						$existingaliascourse[$als]=1;

						echo "<table><tr ><td ><div class=\"crqgreenmsg\">$course - submitted for crosslisting with $als</div></td></tr></table>";
						//echo "<FONT COLOR=GREEN>$course - submitted for crosslisting with $als</font> ";
						//echo "<br>";
						}
						$r++;
					}

					}
				}
			}
			$cnt++;
		}

	}

	if((isset($_POST["action"]))){
	if($_POST["action"]=="viewcoursestobebuilt")
	{
	getCoursesToBeBuilt();
	}
	}
	
	if((isset($_POST["action"]))){
	if($_POST["action"]=="viewlivecourses")
	{
	getLiveCourses();
	}
	}
	/*
	if((isset($_POST["action"]))){
	if($_POST["action"]=="viewpreviewcourses")
	{
	getPreviewCourses();
	}
	}*/
	if((isset($_POST["action"]))){
	if($_POST["action"]=="deletecourse")
	{
		deleteCourseInQueue();
	}
	}
	/*
	function getPreviewCourses()
	{
		global $DB;
		$recflag=0;
		$department=$_POST['department'];
		echo $department;
		$term = $_POST['term'];
		if($department == "")
		{
			if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where (action like '%uild'or action like 'makelive') AND (status like 'done') and (preview = 1)  and term like '$term' order by department,course")){$recflag=1;}
		}
		else
		{
			if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE (`department` LIKE '$department') and (action like '%uild' OR action like 'makelive') AND (`preview` = 1)   AND (`status` LIKE 'done') and term like '$term' order by department ")){$recflag=1;}
		}
	if($recflag==0) // why is this checking $recflag = 0 ?
	{
		echo "<table><tr ><td ><div class=\"crqerrormsg\">No more Preview Courses in the database can be made Live</div></td></tr></table>";
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
			<td class="crqtableodd" width="120"><strong>DEPARTMENT</strong></td>
			<td class="crqtableodd" width="150"><strong>INSTRUCTOR</strong></td>
			<td class="crqtableodd"><strong>TYPE</strong></td>
			<td class="crqtableodd"></td>
		</tr>
END;

			foreach($rs as $row)
				{
					$srs=rtrim($row->srs);
					$department=trim($row->department);
					$xlist="";
					if ($row->crosslist == 1)
					{
					//$xlist= "<font color = blue><i>crosslisted</i></font>";
					$xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
					}

					echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"previewtolive\">";
					echo "<input type=\"hidden\" name=\"srs\" value=\"$srs\">";
					echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
					echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
					echo "<tr class=\"crqtableunderline\"><td>";
					$check =$row->action;
					if ($check == "makelive")
					{
					echo rtrim($row->srs)."</td>
					<td>".rtrim($row->course)."</td>
					<td>".rtrim($row->department)."</td>
					<td>".rtrim($row->instructor)."</td>
					<td>".$xlist."</td>
					<td>Request already submitted</td>
					</tr>";
					}
					else
					{
					echo rtrim($row->srs)."</td>
					<td>".rtrim($row->course)."</td>
					<td>".rtrim($row->department)."</td>
					<td>".rtrim($row->instructor)."</td>
					<td>".$xlist."</td>
					<td><input type=\"submit\" value=\"Make Live.\"></td>
					</tr>";
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
			$department=$_POST['department'];
			$term = $_POST['term'];
			if($department == "")
			{
				if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where action like '%uild' and (status like 'pending' or status like 'processing') and term like '$term' ")){$recflag=1;}
			}
			else
			{
				if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE `department` LIKE '$department'  AND  `action` LIKE 'build'  AND (status like 'pending' or status like 'processing') order by 'department' ")){$recflag=1;}
			}
			if($recflag == 0)   // $recflag = 0 ??
			{
				//echo "<p><table><tr ><td width=100 ></td><td bgcolor=black align=center><font color=white><b>The queue is empty. All courses have been built as of now.</td></tr></table>";
				echo "<table><tr ><td ><div class=\"crqerrormsg\">The queue is empty. All courses have been built as of now.</div></td></tr></table>";
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
			<td class="crqtableodd" ><strong width="100">SRS</strong></td>
			<td class="crqtableodd" ><strong width="150">COURSE</strong></td>
			<td class="crqtableodd"><strong>DEPARTMENT</strong></td>
			<td class="crqtableodd"><strong width="100" >INSTRUCTOR</strong></td>
			<td class="crqtableodd"><strong>TYPE</strong></td>
			<td class="crqtableodd"></td>
		</tr>
END;
				foreach($rs as $row2)
				{
					$srs = rtrim($row2->srs);
					echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
					echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
					echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
					echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
					$xlist="";
					if ($row2->crosslist == 1)
					{
						$xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
					}
						$coursetype=" <span class=\"crqbedlive\">Live</span>";
					/*if ($row2->preview == 0)
					{
						$coursetype=" <span class=\"crqbedlive\">Live</span>";
					}
					else
					{
						$coursetype=" <span class=\"crqbedpreview\">Preview</span>";
					}*/
					echo "<tr class=\"crqtableunderline\" >
						<td width=\"90\">".rtrim($row2->srs)."</td>
						<td width=\"100\">".rtrim($row2->course)."</td>
						<td>".rtrim($row2->department)."</td>
						<td width=\"150\">".rtrim($row2->instructor)."</td>
						<td align=\"right\">".$coursetype.$xlist."</td>
						<td><input type=\"submit\" value=\"Delete\"></td>
						</tr></form>";
				}
				echo "</tbody></table>";
			}
	}

function getLiveCourses()
	{
		global $DB;
		$recflag=0;
		$department=$_POST['department'];
		$term = $_POST['term'];
		if($department == "")
		{
			if($rs=$DB->get_records_sql("select * from mdl_ucla_request_classes where status like 'done' and term like '$term' order by department,course")){$recflag=1;}
		}
		else
		{
			if($rs=$DB->get_records_sql("SELECT * FROM `mdl_ucla_request_classes` WHERE `department` LIKE '$department' AND `status` LIKE 'done' and term like '$term' order by 'department' ")){$recflag=1;}
		}
		if($recflag==0 )    // why this this checking $recflag = 0 ?
		{
			//echo "<p><table><tr ><td width=100 ></td><td bgcolor=black align=center><font color=white><b>The queue is empty</td></tr></table>";
			echo "<table><tr ><td ><div class=\"crqerrormsg\">The queue is empty.</div></td></tr></table>";
		}
		else
		{
			// 5 column table
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
			<td class="crqtableodd" width="100" ><strong>INSTRUCTOR</strong></td>
			<td class="crqtableodd"><strong>TYPE</strong></td>
			<td class="crqtableodd"></td>
		</tr>

END;
				//echo "<p><table><tr><td width=100></td><td><table border=1><tr bgcolor=black><td width=100 align=center><font color=white> SRS </TD><td align=center><font color=white> COURSE </TD><td align=center><font color=white> DEPARTMENT </TD><td align=center><font color=white> INSTRUCTOR </TD><td align=center><font color = white> TYPE </td></TR><tr><td></td></tr>";
				foreach($rs as $row2)
					{
						$srs = rtrim($row2->srs);
						echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">";
						//echo "<input type=\"hidden\" name=\"action\" value=\"converttopreview\">";
						echo "<input type=\"hidden\" name=\"srs\" value=\"$srs\">";
						echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
						echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
						$xlist="";
						if ($row2->crosslist == 1)
						{
							$xlist= "<span class=\"crqbedxlist\">crosslisted</span>";
						}
						$coursetype=" <span class=\"crqbedlive\">Live</span>";
						/*
						if ($row2->preview == 0)
						{
							$coursetype=" <span class=\"crqbedlive\">Live</span>";
			}
			else
			{
			$coursetype=" <span class=\"crqbedpreview\">Preview</span>";
			}*/
						echo "<tr class=\"crqtableunderline\">
							<td width=\"90\">".rtrim($row2->srs)."</td>
							<td width=\"100\">".rtrim($row2->course)."</td>
							<td>".rtrim($row2->department)."</td>
							<td width=\"150\">".rtrim($row2->instructor)."</td>
							<td align=\"right\" colspan=\"2\">".$coursetype.$xlist."</td>
														</tr></form>";
					}
				echo "</tbody></table>";
				//echo "</table></td></tr></table>";
			}
	}



function getCoursesInDept($term,$subjarea,$db_conn){

	global $CFG;
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
		
	echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">";
echo <<< END

	<table>
	<thead>
		<tr>
				
		<td class="crqtableodd" colspan="4">DEPARTMENT: <strong> $subjarea </strong></td>
		</tr>
		<tr>
					<td class="crqtableeven">
END;
					echo "<label><input type=checkbox name=mailinst value=1 " . ($mailinst_default? "checked" : '') . ">&nbsp;Send Email to Instructor(s)</label>
		</td>
		<td class=\"crqtableeven\">
						<label><input type=checkbox name=hidden value=1 " . (!$hidden_default? "checked" : '') . ">&nbsp;Build as Hidden</label>
		</td>";
echo <<< END
		<td class="crqtableeven" colspan="2"  align="right">
			<label>
			Department Contact:<input style="color:gray;" type=test name=contact value='Enter email' id="crqemail" onfocus="if(this.value=='Enter email'){this.value='';this.style.color='black'}" onblur="if(this.value==''){this.value='Enter email';this.style.color='gray'}" >
			</label>
		</td>
		</tr>
		<tr >
		<td class="crqtableeven" colspan="1">
END;

		echo "<label><input type=checkbox name=forceurl value=1 " . ($forceurl_default? "checked" : '') . ">&nbsp;Force URL Update</label>
		</td>";
echo <<< END
		<td class="crqtableeven" colspan="1">
END;

		echo "<label><input type=checkbox name=nourlupd value=1 " . ($nourlupd_default? "checked" : '') . ">&nbsp;Prevent URL Update</label>
		</td>";
echo <<< END
		<td class="crqtableeven" colspan="2" align="right">
<input type="submit" value="Build Department" onclick="if(form.crqemail.value=='Enter email')form.crqemail.value=''">
		
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

//	echo "<form method=\"POST\" action=\"".$PHP_SELF."\">";
//	echo "<table><tr><td width=10><tr>";
//
//	echo "<table width=100% bordercolor=LIGHT BLUE>";
//	echo "<tr BGCOLOR=BLACK ALIGN = CENTER><FONT COLOR=BLACK><B>DEPARTMENT: $subjarea</B></FONT> </tr>";
//	echo "<tr BGCOLOR= BLACK>";
//
//	echo "<tr><td><FONT COLOR=GREEN><input type=checkbox name=preview value=1><b>Build Department as Preview</b></td>      ";
//	echo "<td><FONT COLOR=gREEN><b>Department Contact:</b><input type=test name=contact value='enter email' ></FONT></td></tr> ";
//
//	echo "<tr><FONT COLOR=RED><td bgcolor=black width=100 ALIGN=CENTER><font color=white>INSTRUCTOR<br></td><TD ALIGN=CENTER bgcolor=black><font color=white>COURSE<br></td> ";
//	echo "<td bgcolor=black ALIGN=CENTER><font color=white>CROSSLISTED WITH<br></td> <TD ALIGN=CENTER bgcolor=black><font color=white>BUILD</FONT></TD></tr>";


	$totalrows =count($rows);
	$count=1;

	foreach ($rows as $row)
	{
		getCourseDetails($_POST['term'],$row->srs,$count,$db_conn);
		$count++;
	}
	echo "</tbody>";
	echo "<tfoot>";
	echo "<tr><td colspan=\"4\" class=\"crqtableodd\">";
	echo "<input type=\"hidden\" name=\"count\" value=\"$totalrows\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"builddept\">";
	//echo "<input type=\"submit\" value=\"Build Department\"> <br/>";
	//$default = $CFG->classrequestor_mailinst_default;
	//echo "<br>Email Instructors: <input type=\"radio\" name=\"mailinst\" value = \"1\" ". ($default? "checked" : '') .">yes<input type=\"radio\" name=\"mailinst\" value = \"0\" ". (!$default?"checked":'') .">no<br>\n";
	echo "</td></tr>";
	echo "</tfoot>";
	echo "</table>";
	echo "</form>";
}

function getCourseDetails($term,$srs,$count,$db_conn){

	$xlistexists = 0;
	$xlist_info = file( "http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/getConSched?user=ssc&pass=zx12as&term=$term&SRS=$srs");
	$i=0;
	$i = count($xlist_info);

	while ($i >0 && isset($xlist_info[$i]))
	{
	if(eregi('[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]',$xlist_info[$i]))
	{
		$xlistexists=1;
	}
	$i--;
	}

	$i = count($xlist_info);

	$qr= odbc_exec ($db_conn, "EXECUTE ccle_CourseInstructorsGet '$term', '$srs' ");
	$inst_full="";

	$rows = array();

	while ($row=odbc_fetch_object($qr))
	{
		$rows[]=$row;
	}

	odbc_free_result($qr);
	//echo var_dump($rows[0]);
	foreach($rows as $row)
	{
		if ($row->last_name_person != "" AND $row->first_name_person != ""){
			$inst_full .= rtrim($row->last_name_person).", ".rtrim($row->first_name_person)."<br>";
		}
	}

	if ($inst_full == ""){$inst_full = "Not Assigned";}

	$result = odbc_exec ($db_conn,"EXECUTE ccle_getClasses '$term','$srs'");
	$rows = array();
	while ($row=odbc_fetch_object($result))
	{
			$rows[] = $row;
	}
	odbc_free_result($result);

	foreach ($rows as $row)
	{
	$subj = rtrim($row->subj_area);
	$subj=ereg_replace(" ","",$subj);
	$type = rtrim($row->acttype);
	$num = rtrim($row->coursenum);
	if ($num > 495){ continue;}
	if ($subj == "PHYSICS" && $num > 295) {continue;}
	$sect = rtrim($row->sectnum);
	$title = rtrim($row->coursetitle);
	$description  = rtrim($row->crs_desc);
	$url = $row->URL;
	$url=ereg_replace(" ","+",$url);
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
				if(eregi('[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]',$xlist_info[$i]))
				{
				$aliascount++;
				$srs=ltrim($xlist_info[$i]);
				$srs=rtrim($srs);
				$srs=substr($srs,5,9);

				$result1 = odbc_exec ($db_conn, "EXECUTE ccle_getClasses '$term','$srs' ");

				$rows = array();
				while ($row=odbc_fetch_object($result1))
				{
					$rows[] = $row;
				}
				odbc_free_result($result1);

				foreach ($rows as $row1)
				{
					$subj1 = rtrim($row1->subj_area);
					$num1 = rtrim($row1->coursenum);
					$sect1 = rtrim($row1->sectnum);

					$course1 = $subj1.$num1.'-'.$sect1;
				}

				echo "<input type=\"checkbox\" name=\"alias$aliascount$count\" value=\"$srs\" checked> $course1 <br>";
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

	function deleteCourseInQueue()
	{
	global $DB;
		$DB->execute("delete from mdl_ucla_request_classes where srs like '$_POST[srs]' ");
		$DB->execute("delete from mdl_ucla_request_crosslist where srs like '$_POST[srs]' ");
		getCoursesToBeBuilt();
	}
?>
	</div>
</div><!-- end form output -->
</div><!-- end course requestor -->

<?php
echo $OUTPUT->footer();

?>