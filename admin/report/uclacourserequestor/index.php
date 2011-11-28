<?php
/*
 * Course Requestor form
 * Integrated into moodle as a plugin
 *
 * Now uses mdl_ucla_request_classes & mdl_ucla_request_crosslist tables
 *
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/report/uclacourserequestor/';
require_once($CFG->dirroot . $thisdir . 'lib.php');

global $DB, $ME, $USER;

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
$rucr = 'report_uclacourserequestor';

// Adding 'Support Admin' capability to course requestor
if (!has_capability('report/uclacourserequestor:view', $syscontext)) {
    print_error('adminsonlybanner');
}

$selterm = optional_param('term', false, PARAM_ALPHANUM);
$selected_term = $selterm ? $selterm : get_config(
    'report/uclacourserequestor', 'selected_term');

$thisfile = $thisdir . 'index.php';

// used to determine if course is already been requested
$existingcourse = null;  

// used to determine if cross-listed course is already been requested
$existingaliascourse = null; 

define('UCLA_CR_SUBMIT', 'submitrequests');

// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', $rucr));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacourserequestor');

$subjareas = $DB->get_records('ucla_reg_subjectarea');

$prefields = array('term', 'department', 'action');
$prefieldstr = trim(implode(', ', $prefields));

$rsid = 'CONCAT(' . $prefieldstr . ')';
if (!$prefieldstr) {
    $prefieldstr = $rsid;
} else {
    $prefieldstr = $rsid . ', ' . $prefieldstr;
}

$builtcategories = $DB->get_records('ucla_request_classes', null, 
    'department', 'DISTINCT ' . $prefieldstr);

$prefieldsdata = array();
foreach ($builtcategories as $builts) {
    foreach ($prefields as $prefield) {
        $varname = $prefield;

        if (!isset($prefieldsdata[$varname])) {
            $prefieldsdata[$varname] = array();
        }

        $prefieldsdata[$varname][$builts->$prefield] = $builts->$prefield;
    }
}

$top_forms = array(
    UCLA_REQUESTOR_FETCH => array('srs', 'subjarea'),
    UCLA_REQUESTOR_VIEW => array('view')
);

$termstr = get_config('report/uclacourserequestor', 'terms');

if (!empty($termstr)) {
    $terms = explode(',', $termstr);

    foreach ($terms as $k => $t) {
        unset($terms[$k]);
        $tt = trim($t);
        $terms[$tt] = $tt;
    }
}

if (empty($terms)) {
    $terms[$selected_term] = $selected_term;
}

// This will be passed to each form
$nv_cd = array(
    'subjareas' => $subjareas,
    'selterm' => $selected_term,
    'terms' => $terms,
    'prefields' => $prefieldsdata
);

foreach ($prefieldsdata as $var => $pfd) {
    $nv_cd[$var] = $pfd;
}

// We're going to display the forms, but later
$cached_forms = array();

// This is the courses we want to display.
$requests = null;

// This is to know which form type we came from :(
$groupid = null;

// These are messages that can be displayed wayyy later.
$requestor_messages = array();

foreach ($top_forms as $gk => $group) {
    foreach ($group as $form) {
        $classname = 'requestor_' . $form . '_form';
        $filename = $CFG->dirroot . $thisdir . $classname . '.php';

        // OK, it appears we need all of them
        require_once($filename);

        $fl = new $classname(null, $nv_cd);

        $cached_forms[$gk][$form] = $fl;
       
        if ($requests === null && $recieved = $fl->get_data()) {
            $requests = $fl->respond($recieved);
            $groupid = $gk;
        }
    }
}

// None of the forms took input, so maybe the center form?
// In this situation, we are assuming all information is
// logically correct
$saverequeststates = false;
if ($requests === null) {
    $prevs = data_submitted();

    // TODO Make a function out of this chunk of code
    if (empty($prevs)) {
        $prevs = array();
    }

    if (!empty($prevs->formcontext)) {
        $groupid = $prevs->formcontext;
    }

    // The big moment you've been waiting for
    if (!empty($prevs->{UCLA_CR_SUBMIT})) {
        $saverequeststates = true;
    }

    $requests = array();
    foreach ($prevs as $key => $prev) {
        $att = request_parse_input($key, $prev);

        if ($att) {
            list($term, $srs, $var, $val) = $att;

            $key = "$term-$srs";

            if (empty($requests[$key])) {
                $requests[$key] = array();
            }

            $requests[$key][$var] = $val;
        } else {
            continue;
        }
    }

    // Take the crosslists and make it work
    $k = 'enabled-crosslists';
    $m = array('term', 'srs', 'course');

    foreach ($requests as $rkey => $request) {
        $rterm = $request['term'];
        $request['crosslists'] = array();

        // This may be confusing design
        if (!empty($request['new-crosslists'])) {
            foreach ($request['new-crosslists'] as $nclsrs) {
                $newobj = array(
                    'term' => $rterm,
                    'srs' => $nclsrs
                );

                $k = request_make_key($newobj);
                if ($k) {
                    $request['crosslists'][$k] = $newobj;
                }

                $request = get_request_crosslists_from_registrar($request);
            }

            unset($request['new-crosslists']);
        }

        if (!empty($request[$k])) {
            foreach ($request[$k] as $n => $cl) {
                $clobj = array();
                foreach ($m as $prop) {
                    $clkey = $k . '-' . $prop;
                    if (empty($request[$clkey][$n])) {
                        // This is weird
                        continue 2;
                    }
                    
                    $clobj[$prop] = $request[$clkey][$n];
                    unset($request[$clkey][$n]);
                }

                $request['crosslists'][] = $clobj;
            }

            foreach ($m as $p) {
                unset($request[$k . '-' . $p]);
            }

            unset($request[$k]);
        }

        $requests[$rkey] = $request;
    }
}

// GET A SINGLE COURSE
// GET MULTIPLE COURSES (DEPT)
// VIEW REQUESTED COURSES
// DELETE COURSE FROM REQUESTOR
// ADD COURSES TO REQUESTOR TABLES

if (!empty($requests)) {
    $requeststable = new html_table();

    $possfields = array();

    // Do a WHOLE BUNCH of stuff
    list($rowclasses, $tabledata) = prep_request_entries($requests, $groupid,
        $saverequeststates);

    if (empty($tabledata) && $saverequeststates) {
        redirect($PAGE->url);
    }

    $messages = array_keys(array_flip($rowclasses));

    foreach ($tabledata as $request) {
        // Get the headers organized neatly
        foreach ($request as $f => $v) {
            $possfields[$f] = get_string($f, $rucr);
        }
    }

    $requeststable->head = $possfields;

    // Format the data
    $requeststable->data = $tabledata;

    $requeststable->rowclasses = $rowclasses;
}

$registrar_link = new moodle_url(
    'http://www.registrar.ucla.edu/schedule/');
    

// Start rendering
echo $OUTPUT->header();

echo $OUTPUT->box(
    $OUTPUT->heading(
        get_string('pluginname', $rucr)
    ), 
    'generalbox categorybox box'
);

echo html_writer::link(
    $registrar_link,
    get_string('srslookup', $rucr),
    array('target' => '_blank')
);

foreach ($cached_forms as $gn => $group) {
    echo $OUTPUT->box_start('generalbox');
    echo $OUTPUT->heading(get_string($gn, $rucr));

    foreach ($group as $form) {
         $form->display();
    }

    echo $OUTPUT->box_end();
}

if ($requests === false) {
    echo $OUTPUT->box(get_string('checktermsrs', $rucr));
} if (!empty($requeststable->data)) {
    echo html_writer::start_tag('form', array(
        'method' => 'POST',
        'action' => $PAGE->url
    ));

    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => $groupid,
            'name' => 'formcontext'
        ));

    if (!empty($messages)) {
        foreach ($messages as $message) {
            if (!empty($message)) {
                echo $OUTPUT->box(get_string($message, $rucr));
            }
        }
    }

    echo html_writer::table($requeststable);

    echo html_writer::tag('input', '', array(
            'type' => 'submit',
            'name' => UCLA_CR_SUBMIT,
            'value' => get_string('submit' . $groupid, $rucr),
            'class' => 'right'
        ));

    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();

/*******************************************************************************
 * SCRIPT FUNCTIONS
 ******************************************************************************/

/**
 * this function deletes one specific class in the building queue
 * based on the given srs
 */
function delete_course_in_queue()
{
    global $DB;
    global $CFG;    
    $srs = optional_param('srs', NULL, PARAM_ALPHANUM);
    $term = optional_param('term', NULL, PARAM_ALPHANUM);
    $message = array();  // holds what to display to user
    $deleted_entry = false;
    
    // make sure that record exists   
    if (!empty($term) && !empty($srs)) {
        $where = array('srs' => $srs, 'term' => $term);
        if ($DB->record_exists('ucla_request_classes', $where) || 
                $DB->record_exists('ucla_request_crosslist', $where)) {
            $DB->delete_records('ucla_request_classes', $where);
            $DB->delete_records('ucla_request_crosslist', $where);        
            $deleted_entry = true;
        }
    }

    if (!empty($deleted_entry)) {
        $message['style'] = 'crqgreenmsg';
        $message['text'] =  get_string('delete_successful', 
                'report_uclacourserequestor') . "$srs ($term)";        
    } else {
        $message['style'] = 'crqerrormsg';
        $message['text'] =  get_string('delete_error', 
                'report_uclacourserequestor') . "$srs ($term)";       
    }
    
    echo "<div class=\"".$message['style']."\">";
    echo $message['text'];
    echo "</div>";    
    
    get_courses_to_be_built();
}

/**
 * this function displays status on live classes or classes to be built
 * input:
 * $build_or_live - 1 means from classes to be built, and 0 means from live
 * $buildform - an array containing department and term
 */
function display_build_live_classes($build_or_live, $buildform)
{
    global $CFG;
    global $PAGE;
    global $DB;
    $recflag = 0;
    $department = $buildform['group2']['department'];
    $term = $buildform['group2']['term'];
    if ($department == '0') {
        if ($build_or_live == 1) { // from build queue
            if ($rs = $DB->get_records_sql("select * from " . $CFG->prefix . "ucla_request_classes where 
                action like 'build' and (status like 'pending' or status like 'processing') 
                and term like '$term' ")) {
                $recflag = 1;
            }
        } else { // from live queue
            if ($rs = $DB->get_records('ucla_request_classes', array('status' => 'done',
                'term' => $term), 'department,course')) {
                $recflag = 1;
            }
        }
    } else {
        if ($build_or_live == 1) {
            if ($rs = $DB->get_records_sql("select * from `" . $CFG->prefix . "ucla_request_classes` 
                where `department` like '$department'  and  `action` like 'build' and term like '$term' and (status like 
                'pending' or status like 'processing') order by 'department' ")) {
                $recflag = 1;
            }
        } else {
            if ($rs = $DB->get_records('ucla_request_classes', array('department' => $department,
                'status' => 'done', 'term' => $term), 'department')) {
                $recflag = 1;
            }
        }
    }
    if ($recflag = 0) {
        echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">The 
            queue is empty.</div></td></tr></tbody></table>";
    } else {
        echo <<< END

    <table>
        <tbody>
            <tr>
                <td class="crqtableeven" colspan="6" align="center">
END;
        if ($build_or_live == 1) {
            echo get_string('viewtobebuilt', 'report_uclacourserequestor');
        } else {
            echo get_string('viewlivecourses', 'report_uclacourserequestor');
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
        foreach ($rs as $row2) {
            $srs = rtrim($row2->srs);
            echo "<form method=\"POST\" action=\"" . $PAGE->url . "\">";
            if ($build_or_live == 1) {
                echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
            }
            echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
            echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
            echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
            $xlist = "";
            if ($row2->crosslist == 1) {
                $xlist = "<span class=\"crqbedxlist\">crosslisted</span>";
            }
            if ($build_or_live == 1) {
                $coursetype = " <span class=\"crqbedlive\">$row2->status</span>";
            } else {
                $coursetype = " <span class=\"crqbedlive\">Live</span>";
            }

            echo "<tr class=\"crqtableunderline\"><td>" . rtrim($row2->srs) . "</td>
                <td>" . rtrim($row2->course) . "</td>";
            echo "<td>" . rtrim($row2->department) . "</td><td>" . rtrim($row2->instructor) . "</td>";
            if ($build_or_live == 1) {
                echo "<td>" . $coursetype . $xlist . "</td><td><input type=\"submit\" value=\"Delete\">
                    </td></tr></form>";
            } else {
                echo "<td>" . $coursetype . $xlist . "</td></tr></form>";
            }
        }

        echo "</tbody></table>";
    }
}

/**
 * this function displays class info such as whether it has
 * been crosslisted and its aliases. This function is called by
 * get_courses_in_dept
 * Input:
 * $term - the year and quarter that the class is in
 * $srs - the srs of the class
 * $count - its entry number in the display of a department
 * $db_conn - registrar connection
 */
function get_course_details($term, $srs, $count, &$db_conn)
{
    global $CFG;
    global $PAGE;
    $xlistexists = 0;
    $xlist_info = get_crosslisted_courses($term, $srs);

    foreach ($xlist_info as $xlist_element) {
        if (preg_match('/[0-9]{9}/', $xlist_element)) {
            $xlistexists = 1;
            break;
        }
    }

    $qr = odbc_exec($db_conn, "EXECUTE ccle_CourseInstructorsGet '$term', '$srs'");
    $inst_full = "";

    $rows = array();
    while ($row = odbc_fetch_object($qr)) {
        $rows[] = $row;
    }
    odbc_free_result($qr);

    foreach ($rows as $row) {
        if ($row->last_name_person != "" AND $row->first_name_person != "") {
            $inst_full .= " " . trim($row->last_name_person) . ", " . trim($row->first_name_person) . " ";
        }
    }

    if ($inst_full == "") {
        $inst_full = "Not Assigned";
    }

    $result = odbc_exec($db_conn, "EXECUTE ccle_getClasses '$term','$srs'");
    $rows = array();
    while ($row = odbc_fetch_object($result)) {
        $rows[] = $row;
    }
    odbc_free_result($result);

    foreach ($rows as $row) {
        $subj = trim($row->subj_area);
        $num = trim($row->coursenum);
        if ($num > 495) {
            continue;
        }
        if ($subj == "PHYSICS" && $num > 295) {
            continue;
        }
        $sect = trim($row->sectnum);
        $course = $subj . $num . '-' . $sect;

        echo "<input type=\"hidden\" name=\"srs$count\" value=$srs>";
        echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
        echo "<input type=\"hidden\" name=\"inst$count\" value=\"$inst_full\">";
        echo "<input type=\"hidden\" name=\"department\" value=\"$subj\">";
        echo "<input type=\"hidden\" name=\"course$count\" value=\"$course\">";
        echo "<tr class=\"crqtableunderline\" ><td>$inst_full";      // INSTRUCTOR COLUMN

        if ($course == "") {
            $course = "NULL";
        }

        echo "</td><td>$course ($row->srs)</td>";

        if ($xlistexists) {
            echo "<td>";
            $aliascount = 0;

            foreach ($xlist_info as $xlist_element) {
                if (preg_match('/[0-9]{9}/', $xlist_element)) {
                    $aliascount++;
                    $srs = trim($xlist_element);
                    $srs = substr($srs, 5, 9);

                    $result1 = odbc_exec($db_conn, "EXECUTE ccle_getClasses '$term','$srs' ");

                    $rows = array();
                    while ($row = odbc_fetch_object($result1)) {
                        $rows[] = $row;
                    }
                    odbc_free_result($result1);

                    foreach ($rows as $row1) {
                        $subj1 = rtrim($row1->subj_area);
                        $num1 = rtrim($row1->coursenum);
                        $sect1 = rtrim($row1->sectnum);

                        $course1 = $subj1 . $num1 . '-' . $sect1;
                    }

                    echo "<input type=\"checkbox\" name=\"alias$aliascount$count\" value=\"$srs\" checked> $course1 ($srs)<br />";
                }
            }
            echo "<input type=\"hidden\" name=\"aliascount$count\" value = \"$aliascount\" >";
        } else {
            echo "<td><input type=\"text\" name=\"alias1$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"text\" name=\"alias2$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"text\" name=\"alias3$count\" size=\"10\" maxlength=\"9\">";
            echo "<input type=\"hidden\" name=\"aliascount$count\" value = \"3\" >";
        }
        echo "</td><td><input type=\"checkbox\" name=\"addcourse$count\" value=$srs checked>";
        echo "</td></tr>";
    }
}

/**
 * this function displays class infomation in the given department
 * input:
 * $term - the year and quarter that the classes are in
 * $subjarea - the department/subject area
 * $db_conn - registrar connection
 */
function get_courses_in_dept($term, $subjarea, &$db_conn)
{
    global $CFG;
    global $PAGE;
    $term = rtrim($term);
    $subjarea = rtrim($subjarea);

    $qr = odbc_exec($db_conn, "EXECUTE CIS_courseGetAll '$term','$subjarea'") 
            or die('CIS_courseGetAll query failed');

    $rows = array();
    while ($row = odbc_fetch_object($qr)) {
        $rows[] = $row;
    }
    odbc_free_result($qr);

    $mailinst_default = $CFG->classrequestor_mailinst_default;
    $forceurl_default = $CFG->classrequestor_forceurl_default;
    $nourlupd_default = $CFG->classrequestor_nourlupd_default;
    $hidden_default = get_config('moodlecourse')->visible;

    echo "<form method=\"POST\" action=\"" . $PAGE->url . "\">";
    echo <<< END
<table>
<thead>
    <tr>
            
    <td class="crqtableodd" colspan="4">DEPARTMENT: <strong> $subjarea ($term)</strong></td>
    </tr>
    <tr>
        <td class="crqtableeven">
END;
    echo "<label><input type=checkbox name=mailinst value=1 " . ($mailinst_default ? "checked" : '') . ">
&nbsp;Send Email to Instructor(s)</label>
    </td>
    <td class=\"crqtableeven\">
        <label><input type=checkbox name=hidden value=1 " . (!$hidden_default ? "checked" : '') . ">
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

    echo "<label><input type=checkbox name=forceurl value=1 " . ($forceurl_default ? "checked" : '') . ">
    &nbsp;Force URL Update</label>
    </td>";
    echo <<< END
    <td class="crqtableeven" colspan="1">
END;

    echo "<label><input type=checkbox name=nourlupd value=1 " . ($nourlupd_default ? "checked" : '') . ">
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

    $totalrows = count($rows);
    $count = 1;

    foreach ($rows as $row) {
        get_course_details($term, $row->srs, $count, $db_conn);
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

/**
 * this function display classes in the to be built queue
 */
function get_courses_to_be_built()
{
    global $DB;
    global $CFG;
    global $PAGE;
        
    $recflag = 0;
    $department = optional_param('department', NULL, PARAM_ALPHANUM);
    $term = optional_param('term', NULL, PARAM_ALPHANUM);
    if ($department == '0') {
        if ($rs = $DB->get_records_sql("select * from " . $CFG->prefix . "ucla_request_classes 
            where action like 'build' and (status like 'pending' or status like 'processing') 
            and term like '$term' ")) {
            $recflag = 1;
        }
    } else {
        if ($rs = $DB->get_records_sql("SELECT * FROM `" . $CFG->prefix . "ucla_request_classes` 
            WHERE `department` LIKE '$department'  AND  `action` LIKE 'build'  
            AND (status like 'pending' or status like 'processing') order by 'department' ")) {
            $recflag = 1;
        }
    }
    if ($recflag = 0) {
        echo "<table><tbody><tr><td class=\"crqtableodd\"><div class=\"crqerrormsg\">";
        echo get_string('queueempty', 'report_uclacourserequestor');
        echo "</div></td></tr></tbody></table>";
    } else {
        echo <<< END
    <table>
        <tbody>
            <tr>
                <td class="crqtableeven" colspan="6" align="center">
END;
        echo get_string('viewtobebuilt', 'report_uclacourserequestor');
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
        foreach ($rs as $row2) {
            $srs = rtrim($row2->srs);
            echo "<form method=\"POST\" action=\"" . $PAGE->url . "\">";
            echo "<input type=\"hidden\" name=\"action\" value=\"deletecourse\">";
            echo "<input type=\"hidden\" name=\"srs\" value=\"$row2->srs\">";
            echo "<input type=\"hidden\" name=\"department\" value=\"$department\">";
            echo "<input type=\"hidden\" name=\"term\" value=\"$term\">";
            $xlist = "";
            if ($row2->crosslist == 1) {
                $xlist = "<span class=\"crqbedxlist\">crosslisted</span>";
            }
            $coursetype = " <span class=\"crqbedlive\">$row2->status</span>";

            echo "<tr class=\"crqtableunderline\"><td>" . rtrim($row2->srs) . "</td>
                <td>" . rtrim($row2->course) . "</td><td>" . rtrim($row2->department) . "</td>
                <td>" . rtrim($row2->instructor) . "</td><td>" . $coursetype . $xlist . "</td>
                <td><input type=\"submit\" value=\"Delete\"></td></tr></form>";
        }

        echo "</tbody></table>";
    }
}

