<?php
/*
Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

function print_term_pulldown_box($submit_on_change=false) {
    global $CFG;

    $selected_term = optional_param('term',NULL,PARAM_ALPHANUM) ? 
    optional_param('term',NULL,PARAM_ALPHANUM) : $CFG->classrequestor_selected_term;

    $pulldown_term = "<select name=\"term\"" . ($submit_on_change ? " 
        onchange=\"this.form.submit()\"" : "") . ">\n";

    foreach ($CFG->classrequestor_terms as $term) {
        if ($term == $selected_term) {
            $pulldown_term .= "<option value=\"$term\" SELECTED>$term</option>\n";
        } else {
            $pulldown_term .= "<option value=\"$term\">$term</option>\n";
        }
    }
    $pulldown_term .= "</select>\n";
    print $pulldown_term;
}

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

$PAGE->set_url('/admin/report/courserequestor/addcrosslist.php');
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
    <?php echo get_string('coursereqaddcrosslist', 'report_courserequestor') ?>
</div>

<div class="generalbox categorybox box "  >
    <div class="crqcenterbox">
<?php
        $course_requestor =  $CFG->wwwroot."/admin/report/courserequestor/index.php";
        $addCrosslist = $CFG->wwwroot."/admin/report/courserequestor/addcrosslist.php";

        echo "<a href=\"$course_requestor\">".get_string('buildcourse', 
            'report_courserequestor')."</a> | ";
        echo "<a href=\"$addCrosslist\">".get_string('addcrosslist', 
            'report_courserequestor')."</a> ";
?>
    </div>

    <div >
        <form method="POST" action="<?php echo $CFG->wwwroot."/admin/report/courserequestor/addcrosslist.php"; ?>">
            <fieldset class="crqformeven">
                <legend></legend>
                <label>
<?php 
    echo get_string('crosslistselect', 'report_courserequestor');
    print_term_pulldown_box(true); 
?>
                </label>
            </fieldset>
    </form>
    </div>

    <div >
        <form method="POST" action="<?php echo $CFG->wwwroot."/admin/report/courserequestor/addcrosslist.php"; ?>">
            <fieldset class="crqformodd">
                <legend></legend>
                <label>
<?php 
    $termcleaned = optional_param('term', NULL, PARAM_ALPHANUM); 
    echo get_string('crosslistterm', 'report_courserequestor');
    echo '<strong> ';
    if( empty($termcleaned) ){
        echo $CFG->classrequestor_selected_term;
    } else {
        echo "$termcleaned";
    } 
    echo '</strong><br/><br/>';
    echo get_string('crosslistnotice', 'report_courserequestor');
    echo '<br/><select name="hostsrs" >';
    
    if(isset($termcleaned)){
        $term = ($termcleaned == "") ? $CFG->classrequestor_selected_term : $termcleaned;
    }

    $crs = $DB->get_records_sql("select srs,course from ".$CFG->prefix."ucla_request_classes 
        where term like '$term' and action like '%uild' and (status = 'processing' 
        or status = 'pending') order by course");

    foreach ($crs as $rows)
    {
        $srs=rtrim($rows->srs);
        $course=rtrim($rows->course);
        $existingcourse[$srs]=1;

        echo "<option value='$srs'>$course</option>";
    }
    echo '</select></label>';
    echo get_string('crosslistaddalias', 'report_courserequestor');
    echo '<input type="hidden" name="action" value="addalias">';
    $i=1;
    while($i<=15){
        if($i%2==0){
            echo '<div class="crqfrmtxtboxodd">';
            echo '<input type="text" name="alias'.$i.'" size="20" maxlength="9">';
            echo '</div>';
        } else {
            echo '<div class="crqfrmtxtboxeven">';
            echo '<input type="text" name="alias'.$i.'" size="20" maxlength="9">';
            echo '</div>';
        }
        $i++;
    }
?>
            <input type="hidden" name="action" value="addalias">
            <input type="hidden" name="term" value="<?php echo "$term"; ?>"><br/>
<?php
    echo '<input type="submit" value="'.get_string('insertalias', 'report_courserequestor').'">';
?>
            </fieldset>
    <form>
    </div>

<div align="center">
<?php
$actioncleaned = optional_param('action', NULL, PARAM_ALPHANUM);
if(isset($actioncleaned)) {
  if($actioncleaned=="addalias") {
      $i=1;
      while($i<=15) {
        $alias="alias".$i;
        $value=optional_param($alias, NULL, PARAM_ALPHANUM);
        if ($value) {
            if (preg_match('/^[0-9]{9}$/',$value)) {
                $termcleaned=required_param('term', PARAM_ALPHANUM);
                $hostsrscleaned=required_param('hostsrs', PARAM_ALPHANUM);

                if ($DB->get_records('ucla_request_crosslist', array('aliassrs'=>$value, 
                    'term'=>$termcleaned, 'srs'=>$hostsrscleaned), null, 'aliassrs')){
                    echo "<div class=\"crqerrormsg\">";
                    echo "DUPLICATE ENTRY. Alias already inserted";
                    echo "</div>";
                } else{
                    $crosslistdata->term = $termcleaned;
                    $crosslistdata->srs = $hostsrscleaned;
                    $crosslistdata->aliassrs = $value;
                    $crosslistdata->type = 'joint';
                    $DB->insert_record('ucla_request_crosslist', $crosslistdata);
                    
                    
                    echo "<table><tr ><td ><div class=\"crqgreenmsg\">New aliases 
                        submitted for crosslisting with host: '$hostsrscleaned'</div></td></tr></table>";

                    $update_records = $DB->get_records('ucla_request_classes', array('srs'=>$hostsrscleaned));
                    if ($update_records){
                        foreach ($update_records as $update_record){
                            $updateobject->id=$update_record->id;
                            $updateobject->crosslist = 1;
                            $DB->update_record('ucla_request_classes', $updateobject);
                        }
                    }
                    
                    
                    echo "<table><tr ><td ><div class=\"crqgreenmsg\">Submitted 
                        for crosslisting</div></td></tr></table>";

                    $message = "New aliases submitted to be crosslisted with host: '$hostsrscleaned' ";
                }
            } else {
                    echo "<div class=\"crqerrormsg\">";
                    echo "Please check your srs input. It has to be a 9 digit numeric value";
                    echo "</div>";
            }
        }
     $i++;
     }
  }
}
echo "</div></div>";

echo $OUTPUT->footer();
?>