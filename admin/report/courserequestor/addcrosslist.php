<?php
/*
Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
 */

require_once("../../../config.php");
require_once($CFG->libdir.'/adminlib.php');
//require_once($CFG->dirroot.'/admin/report/configmanagement/configmanagementlib.php');

// This is needed to get the term dropdown
include("cr_lib.php");

require_login();
global $USER;
global $ME;
global $DB;

$term = $CFG->currentterm;

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

/** DEPRECATED
// Prepare and load Moodle Admin interface
$adminroot = admin_get_root();

// This requests the default page which at this point is
// defined as 'courserequestor'
// Keep this the same so that we're able to switch
admin_externalpage_setup('courserequestor');
admin_externalpage_print_header($adminroot); **/



?>

<link rel="stylesheet" href="requestor.css" />

<div class="headingblock header crqpaddingbot" >
    <?php echo get_string('coursereqaddcrosslist', 'report_courserequestor') ?>
</div>

<div class="generalbox categorybox box "  >
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

    <div >
        <form method="POST" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
            <fieldset class="crqformeven">
                <legend></legend>
                <label>SELECT THE TERM:
                    <?php print_term_pulldown_box(true); ?>
                </label>
            </fieldset>
	</form>
    </div>

    <div >
        <form method="POST" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
            <fieldset class="crqformodd">
                <legend></legend>
                <label>
                    List of <strong>to be built</strong> Courses for the term <strong><?php if( empty($_POST['term']) ){echo $CFG->classrequestor_selected_term;} else {echo "${_POST['term']}";} ?></strong><br/><br/>
                    You can add crosslists while these couses are waiting in queue to be built<br/>
                    <select name="hostsrs" >
                        <?php
			
			if(isset($_POST['term']))
			 $term = ($_POST['term'] == "") ? $CFG->classrequestor_selected_term : $_POST['term'];

                        $crs = $DB->get_records_sql("select srs,course from mdl_ucla_request_classes where term like '$term' and action like '%uild' and (status = 'processing' or status = 'pending') order by course");

                        foreach ($crs as $rows)
                        {
                            $srs=rtrim($rows->srs);
                            $course=rtrim($rows->course);
                            $existingcourse[$srs]=1;

                            echo "<option value='$srs'>$course</option>";
                        }
                        ?>
                        </select>
                </label>


            <!--
                <form method=\"POST\" action=\"".$PHP_SELF."\">
            -->

            ADD ALIASES
            <input type="hidden" name="action" value="addalias">
            <div class="crqfrmtxtboxeven" >
                <input type="text" name="alias1" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias2" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias3" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias4" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias5" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias6" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias7" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias8" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias9" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias10" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias11" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias12" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias13" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxodd">
                <input type="text" name="alias14" size="20" maxlength="9">
            </div>
            <div class="crqfrmtxtboxeven">
                <input type="text" name="alias15" size="20" maxlength="9">
            </div>
            <!-- </div> -->

            <input type="hidden" name="action" value="addalias">
            <input type="hidden" name="term" value="<?php echo "$term"; ?>"><br/>
            <input type="submit" value="Insert Aliases">
            </fieldset>
	<form>
    </div>

<div align="center">
<?php
if(isset($_POST['action']))
  if($_POST["action"]=="addalias")
  {
	  $i=1;
	  while($i<=15)
	  {
		$alias="alias".$i;
		$value=$_POST[$alias];
		if($_POST[$alias])
		{
                    if(eregi('[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]',$value))
                    {
                        $query5 = "select aliassrs from mdl_ucla_request_crosslist where aliassrs like '$value' and term like '$_POST[term]' and srs like '$_POST[hostsrs]'";

                        if($DB->get_records_sql($query5)){
                            echo "<div class=\"crqerrormsg\">";
                            echo "DUPLICATE ENTRY. Alias already inserted";
                            echo "</div>";
                        }
                        else{
                            $query1 = "INSERT INTO mdl_ucla_request_crosslist(term,srs,aliassrs,type) values ('$_POST[term]','$_POST[hostsrs]','$value','joint')";
                            $DB->execute($query1);
                            echo "<table><tr ><td ><div class=\"crqgreenmsg\">New aliases submitted for crosslisting with host: '$_POST[hostsrs]'</div></td></tr></table>";

                            $query2 = "update mdl_ucla_request_classes set crosslist=1 where srs like '$_POST[hostsrs]' ";
                            $DB->execute($query2);
                            echo "<table><tr ><td ><div class=\"crqgreenmsg\">Submitted for crosslisting</div></td></tr></table>";

                            $message = "New aliases submitted to be crosslisted with host: '$_POST[hostsrs]' ";
                            //mail('nthompson@oid.ucla.edu', 'CCLE:New Crosslist Request', $message);
                        }
                    }
                    else
                    {
                            echo "<div class=\"crqerrormsg\">";
                            echo "Please check your srs input. It has to be a 9 digit numeric value";
                            echo "</div>";
                    }
		}

	 $i++;
	 }
  }
echo "</div>
</div>";

echo $OUTPUT->footer();
?>