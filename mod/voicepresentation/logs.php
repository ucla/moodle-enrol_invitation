<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2008  Wimba, All Rights Reserved.                       *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 *                                                                            *
 ******************************************************************************/

//This page is to show the logs types and errors, and to explain how resolve the errors!
require_once("../../config.php");
global $CFG;

require_once($CFG->libdir.'/datalib.php');
require_once($CFG->dirroot.'/course/lib.php');

if(isset($_GET['type'])) {
	$type = $_GET['type'];	
}
if(isset($_GET['time'])){
	$date = $_GET['time'];
}
if(isset($_GET['voicetoolsId'])) {
	if (! $voicetool = $DB->get_record("voicetools", array("id" => $_GET['voicetoolsId']))) {
        return false;
  }
	if (! $course = $DB->get_record("course", array("id" => $voicetool->course))) {
	       return false;
	}
}
else if (isset($_GET['courseId'])) {
	if (! $course = $DB->get_record("course", array("id" => $_GET['courseId']))) {
	       return false;
	}
}

if(isset($_GET['name'])) {
	$name = $_GET['name'];	
}

$dateinfo = userdate($date, get_string('strftimedaydate'));

$REMOTE_ADDR = getremoteaddr();

 switch ($type)
        {
        		
            case "addInstance":
                $message = "Activity \"".$voicetool->name."\" has been added with success";
                $information = "Activity \"".$voicetool->name."\" creation";
								break;
			case "errorAddInstance":
                $information = "Activity \"".$voicetool->name."\" creation";
                $message = "Error : URL ".$CFG->voicetools_servername." creation failed.";
                break;   
            case "updateInstance":
            		$information = "Activity \"".$voicetool->name."\" update";
                $message = "Activity \"".$voicetool->name."\" has been updated with success";
                break;
            case "deleteInstance":
            		$information = "Activity \"".$voicetool->name."\" suppression";
                $message = "Activity \"".$voicetool->name."\" a has been deleted with success.";
                break;
            case "errorDeleteInstance":
            		$information = "Error : Activity \"".$voicetool->name."\" suppression";
                $message = "Error : Activity \"".$voicetool->name."\" has not been deleted";
                break;
           
                
            
        }


?>
<html>

	<head>
		<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1">
		<title>Log : Voice Tools</title>
	</head>

	<body bgcolor="#ffffff">

	<font size="-1" face="Arial,Helvetica,Geneva">
		<table width="100%" border="0" cellspacing="15" cellpadding="0">
				<tr>
				<td>
					<table width="100%" border="0" cellspacing="10" cellpadding="0">
						<tr>
							<td width="50%">
								<div align="left">
										<b>Log : Voice Tools</b></div>

							</td>
							<td>
								<div align="right">
										<b><?php echo $dateinfo; ?></b></div>
							</td>
						</tr>
					</table>
						<hr>

					</td>
			</tr>
				<tr>
				<td>
					<table width="100%" border="0" cellspacing="10" cellpadding="0" bgcolor="#e6edf2">
						<tr>
								<td width="50%">
									<div align="right">
										<font size="-1">

										
										IP Address :</font></div>
								</td>
								<td width="50%">
								<div align="left">
										<font size="-1">
								<?php		echo $REMOTE_ADDR ; ?></font></div>
							</td>
							</tr>

							<tr>
								<td width="50%">
									<div align="right">
										<font size="-1">
										
										Course :</font></div>
								</td>
								<td width="50%">
								<div align="left">

										<font size="-1">
										<?php echo $course->shortname; ?></font></div>
							</td>
							</tr>
						<tr>
								<td width="50%">
									<div align="right">
										<font size="-1">

										
										Full Name :</font></div>
								</td>
								<td width="50%">
								<div align="left">
										<font size="-1">
										<?php echo $course->fullname; ?></font></div>
							</td>
							</tr>
<!--
						<tr>
								<td width="50%">
									<div align="right">
										<font size="-1">...          </font></div>
								</td>
								<td width="50%">
								<div align="left">
										<font size="-1">    ....</font></div>

							</td>
							</tr>
		-->
						<tr>
								<td width="50%">
									<div align="right">
										<font size="-1">
										
										Information : </font></div>
								</td>

								<td width="50%">
								<div align="left">
										<font size="-1">
									<?php	echo $information; ?></font></div>
							</td>
							</tr>
					</table>
				</td>

			</tr>
				<tr>
					<td>
						
					</td>
				</tr>
				<tr>
				<td>
				
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<font size="-1" face="Courier New,Courier,Monaco">

							<tr bgcolor="green" height="1">
								<td width="1" height="1"></td>
								<td height="1"></td>
								<td width="1" height="1"></td>
							</tr>
							<tr bgcolor="green">
								<td bgcolor="green" width="1"></td>
								<td bgcolor="#d0ffd0"><font size="-1" face="Courier New,Courier,Monaco"><?php echo $message; ?></font>

										<p></p>
									</td>
								<td bgcolor="green" width="1"></td>
							</tr>
							<tr bgcolor="green" height="1">
								<td width="1" height="1"></td>
								<td height="1"></td>
								<td width="1" height="1"></td>
							</tr>

						</font>
					</table>
					
				
				</td>
			</tr>
<?php		
if(isset($tip)) {	
?>		
			<tr>
				<td>
									<table width="100%" border="0" cellspacing="0" cellpadding="0">
									<font size="-1" face="Courier New,Courier,Monaco">

						<tr bgcolor="yellow" height="1">
							<td width="1" height="1"></td>
							<td height="1"></td>
							<td width="1" height="1"></td>
						</tr>
						<tr bgcolor="yellow">
							<td bgcolor="yellow" width="1"></td>
							<td bgcolor="#ffffd0"><font size="-1" face="Courier New,Courier,Monaco"><?php echo $tip; ?>
</font>

										<p></p>
									</td>
							<td bgcolor="yellow" width="1"></td>
						</tr>
						<tr bgcolor="yellow" height="1">
							<td width="1" height="1"></td>
							<td height="1"></td>
							<td width="1" height="1"></td>
						</tr>

						</font>
					</table>
				</td>
			</tr>
<?php }
?>
		</table>
		</font>
	</body>

</html>


