<?php
/**
 *  UCLA Support Console
 **/

require_once(dirname(__FILE__) . "/../../../config.php");
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
$admintooldir = '/' . $CFG->admin . '/tool/';
require_once($CFG->dirroot . $admintooldir . 'uclasupportconsole/lib.php');
require_once($CFG->dirroot . $admintooldir . 'uclacoursecreator/uclacoursecreator.class.php');

// Force debugging errors 
error_reporting(E_ALL); 
ini_set( 'display_errors','1');

$consolecommand = optional_param('console', null, PARAM_ALPHAEXT);
$displayforms = empty($consolecommand);
$alldata = data_submitted();

admin_externalpage_setup('reportsupportconsole');

require_login();
require_capability('tool/uclasupportconsole:view', get_context_instance(CONTEXT_SYSTEM));

// The primary goal is to keep as much as possible in one script
$consoles = new tool_supportconsole_manager();

////////////////////////////////////////////////////////////////////
// CHECKING LOGS 
////////////////////////////////////////////////////////////////////
$title = "syslogs";
$syslogs_types = array('log_apache_error', 
                       'log_apache_access',
                       'log_apache_ssl_access',
                       'log_apache_ssl_error',
                       'log_apache_ssl_request',
                       'log_shibboleth_shibd',
                       'log_shibboleth_trans',
                       'log_moodle_cron',
                       'log_course_creator',
                       'log_prepop');

$sectionhtml = '';
if ($displayforms) {
    $logselects = array();
    
    // build select list for logs
    foreach ($syslogs_types as $syslogs_type) {
        $attarr = array('value' => $syslogs_type);

        // see if logtype is set and accessible
        $log_location = get_config('tool_uclasupportconsole', $syslogs_type);
        if (empty($log_location) || !file_exists($log_location)) {
            $attarr['disabled'] = true;
        }      
        
        $logselects[$syslogs_type] = html_writer::tag('option', 
            get_string($syslogs_type, 'tool_uclasupportconsole'), $attarr);
    } 
    
    // add "Choose log..."
    $logselects = array('none' => html_writer::tag('option', 
            get_string('syslogs_choose', 'tool_uclasupportconsole'))) + $logselects;    

    $logselect = html_writer::label(get_string('syslogs_select', 'tool_uclasupportconsole'), $title) . 
            html_writer::tag('select', implode('', $logselects),
            array('name' => 'log', 'id' => $title));

    $sectionhtml = supportconsole_simple_form($title, $logselect) . 
            html_writer::tag('p', get_string('syslogs_info', 'tool_uclasupportconsole'));
} else if ($consolecommand == $title) {
    ob_start();
    
    $log_file = required_param('log', PARAM_ALPHAEXT);
    $log_file = basename($log_file);

    // invalid log type
    if (!in_array($log_file, $syslogs_types)) {
        echo "Invalid logfile name. $logfile\n";
        exit;        
    }
    
    // else try to display it    
    $log_location = get_config('tool_uclasupportconsole', $log_file);    
    
    // if viewing log_course_creator/log_prepop, then get latest log file
    if ($log_file == 'log_course_creator' || $log_file == 'log_prepop') {
        // get last log file
        $last_pre_pop = exec(sprintf('ls -t1 %s | head -n1', $log_location));
        $log_location = $log_location . $last_pre_pop;
    }
    
    echo $log_location . "\n";
    $tail_command = "/usr/bin/tail -1000 ";
    system($tail_command . ' ' . $log_location);

    $sectionhtml = nl2br(htmlspecialchars(ob_get_clean()));
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "prepoprun";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title, 
        html_writer::label('Moodle course.id', 'prepop-courseid')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'id' => 'prepop-courseid',
                    'name' => 'courseid'
                )));
} else if ($consolecommand == "$title") { 
    $sectionhtml = '';
    $courseid = required_param('courseid', PARAM_INT);
    $dbenrol = enrol_get_plugin('database');
    // Sadly, this cannot be output buffered...so
    echo "<pre>";
    $dbenrol->sync_enrolments(true, null, $courseid);
    echo "</pre>";

    $consoles->no_finish = true;
}

$consoles->push_console_html('users', $title, $sectionhtml);
////////////////////////////////////////////////////////////////////
$title = 'coursecreatorlogs';
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == $title) {
    
}
////////////////////////////////////////////////////////////////////
$title = 'moodlelog';
$sectionhtml = '';

if ($displayforms) { 
    $moodlelog_show_filter = optional_param('moodlelog_show_filter', 0, PARAM_BOOL);
    
    $actions = $DB->get_records('log', array(), '', 'DISTINCT action');
    $checkboxes = array();

    $sm = get_string_manager();
    foreach ($actions as $action) {
        $action = $action->action;
        $stringid = $action . '_description';
        if ($sm->string_exists($stringid, 'tool_uclasupportconsole')) {
            $actiondesc = get_string($stringid, 'tool_uclasupportconsole');
        } else {
            $actiondesc = $action;
        }

        // Todo descriptions
        $checkboxes[] = html_writer::tag('li', 
            html_writer::checkbox('actiontypes[]', $action, 
                true, $actiondesc));

    }

    // show/hide action type filter, mainly for users with no js enabled
    $form_content = '';
    $action_types_container_params = array('id' => 'log-action-types-container');
    if (!$moodlelog_show_filter) {
        // display link to show filter
        $form_content .= html_writer::start_tag('div');        
        $form_content .= html_writer::link(
                new moodle_url('/admin/tool/uclasupportconsole/index.php', 
                        array('moodlelog_show_filter' => 1)), 
                get_string('moodlelog_filter', 'tool_uclasupportconsole'), 
                array('id' => 'show-log-types-filter', 
                    // TODO: there has to be a better way to show/hide using YUI...
                    'onclick' => "YAHOO.util.Dom.setStyle('log-action-types-container', 'display', '');YAHOO.util.Dom.setStyle('show-log-types-filter', 'display', 'none');return false;"));        
        $form_content .= html_writer::end_tag('div');                
        
        // hide action types
        $action_types_container_params['style'] = 'display:none';
    }
    
    $form_content .= html_writer::start_tag('div', $action_types_container_params);
    $form_content .= html_writer::label(get_string('moodlelog_select', 'tool_uclasupportconsole'), 
                'log-action-types') . 
                html_writer::tag('ul', implode('', $checkboxes), 
                array('id' => 'log-action-types'));
    $form_content .= html_writer::end_tag('div');
    
    $sectionhtml = supportconsole_simple_form($title, $form_content);
} else if ($consolecommand == "$title") { 
    $actions = required_param_array('actiontypes', PARAM_TEXT);
    list($sql, $params) = $DB->get_in_or_equal($actions);
    $wheresql = 'action ' . $sql;
    $log_query = "
        select
            a.id, 
            from_unixtime(time) as time,
            b.firstname,
            b.lastname,
            ip,
            c.shortname,
            c.id as courseid,
            module,
            action
        from {log} a
        left join {user} b on (a.userid = b.id)
        left join {course} c on (a.course = c.id)
        where $wheresql
        order by a.id desc limit 100
    ";

    $results = $DB->get_records_sql($log_query, $params);

    foreach ($results as $k => $result) {
        if (!empty($result->courseid) && !empty($result->shortname)) {
            $result->shortname = html_writer::link(
                    new moodle_url('/course/view.php', 
                        array('id' => $result->courseid)),
                    $result->shortname
                );
            $results[$k] = $result;
        }
    }

    $sectionhtml = supportconsole_render_section_shortcut($title, $results,
        $params);
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title='moodlelogins';
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") { 
    ob_start();
    $log_query = "
        select 
            a.id, 
            from_unixtime(time) as Time,
            b.Firstname,
            b.Lastname,
            IP,
            a.URL,
            Info
        from {log} a 
        left join {user} b on(a.userid=b.id)
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) and action='login'
        order by a.id desc
        ";

    $result = $DB->get_records_sql($log_query);

    foreach($result as $k => $res) {
        unset($res->id);
        $res->url = html_writer::link(new moodle_url("/user/$res->url"), 
            "$res->firstname $res->lastname", array('target'=>'_blank'));
        $result[$k] = $res;
    }

    echo supportconsole_render_table_shortcut($result, $title);

    $sectionhtml = ob_get_clean();
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO Combine this one with the next one
$title = 'moodlelogbyday';
$sectionhtml = '';
if ($displayforms) { 
    $choiceshtml = html_writer::label('Days', 'days')
        . html_writer::empty_tag('input', array(
                'id' => 'days',
                'type' => 'text',
                'name' => 'days',
                'value' => 7,
                'size' => 3
            ))
        . html_writer::label('Show login entries only', 'radio-login')
        . html_writer::empty_tag('input', array(
                'id' => 'radio-login',
                'type' => 'radio',
                'name' => 'radio',
                'value' => 'login',
                'checked' => true
            ))
        . html_writer::label('Show all entries', 'radio-entries')
        . html_writer::empty_tag('input', array(
                'id' => 'radio-entries',
                'type' => 'radio',
                'name' => 'radio',
                'value' => 'entries'
            ));

    $sectionhtml .= supportconsole_simple_form($title, $choiceshtml);
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} else if ($consolecommand == "$title") {
    $filter = required_param('radio', PARAM_TEXT);
    $days = required_param('days', PARAM_INT);

    if ($days < 1 or $days > 999) {
        print_error("Invalid number of days.");
        exit;
    }    

    if ($filter != "login" and $filter != "entries") {
        echo "Invalid search options.<br>\n";
        exit;
    }    

    if ($filter ==" login") {
        $whereclause = "AND action='login'";
        $what = 'Logins';
    } else {
        $whereclause = "";
        $what = 'Log Entries';
    }    

    $sectionhtml = "Count of Moodle $what from the Last $days Days";
    $days--;  # decrement days by 1 to get query to work
    $result = $DB->get_records_sql("
        SELECT 
            FROM_UNIXTIME(time,'%Y-%m-%d') AS date,
            COUNT(*) AS count 
        FROM {log} a 
        WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
            $whereclause
        GROUP BY date
        ORDER BY a.id DESC
    ");

    $sectionhtml = supportconsole_render_section_shortcut($sectionhtml, $result);
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO combine with the next one
$title = "moodlelogbydaycourse";
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") { 
     $result = $DB->get_records_sql("
        SELECT 
            a.id, 
            FROM_UNIXTIME(time,'%Y-%m-%d') AS date, 
            c.shortname AS course,
            COUNT(*) AS count 
        FROM {log} a 
        LEFT JOIN {course} c ON a.course = c.id
        WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY date, course 
        ORDER BY a.id DESC
    ");

    $sectionhtml .= supportconsole_render_section_shortcut($title, $result);
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlelogbydaycourseuser";
$sectionhtml = '';

if ($displayforms) { 
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") { 
    $result = $DB->get_records_sql("
        SELECT 
            a.id, 
            FROM_UNIXTIME(time,'%Y-%m-%d') AS day,
            c.shortname AS course,
            b.firstname,
            b.lastname,
            COUNT(*) AS count 
        FROM {log} a 
        LEFT JOIN {user} b ON a.userid = b.id
        LEFT JOIN {course} c ON a.course = c.id
        WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY day, course, a.userid 
        ORDER BY a.id DESC
    ");
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result);
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// TODO ghost courses in request classes table

////////////////////////////////////////////////////////////////////
$title = 'moodleusernamesearch';
$sectionhtml = '';
// Note: this report has an additional column at the end, with an SRDB button that points to the enroll2 Registrar class lookup
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title,
        html_writer::label('Full or any part of name', 'name-lookup')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'fullname',
                    'id' => 'name-lookup'
                )));
} else if ($consolecommand == "$title") { 
    $fullname = optional_param('fullname', false, PARAM_TEXT);
    $users = get_users(true, $fullname, false, null, 'lastname, firstname ASC',
        '', '', '', 100, 
        'id AS userid, auth, username, firstname, lastname, idnumber, email, FROM_UNIXTIME(lastaccess) AS last_access, lastip');

    foreach($users as $k => $user) {
        if (!empty($user->idnumber)) {
            $user->srdblink = supportconsole_simple_form('enrollview',
                html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'uid',
                    'value' => $user->idnumber
                )));
        }
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $users); 
} 

$consoles->push_console_html('users', $title, $sectionhtml);
////////////////////////////////////////////////////////////////////
// REGISTRAR DIRECT FEEDS LIONS MEAT
////////////////////////////////////////////////////////////////////
$title = "enrollview";
// Note: this has code which allows post from Name Lookup report 
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title, get_uid_input($title));
} else if ($consolecommand == $title) {
    # tie-in to link from name lookup
    $uid = required_param('uid', PARAM_INT);
    ucla_require_registrar();
    $adodb = registrar_query::open_registrar_connection();

    if (ucla_validator('uid', $uid)) {
        $recset = $adodb->Execute('SELECT * FROM enroll2 WHERE uid = ' . $uid 
            . ' ORDER BY term_int DESC, subj_area, catlg_no, sect_no');

        $usercourses = array();
        if (!$recset->EOF) {
            while($fields = $recset->FetchRow()) {
                $usercourses[] = $fields;
            }
        }

        $sectionhtml .= supportconsole_render_section_shortcut($title, $usercourses, $uid);
    } else {
        $sectionhtml .= $OUTPUT->box($OUTPUT->heading($title, 2));
        $sectionhtml .= 'Invalid UID: [' . $uid . ']';
    }
}

$consoles->push_console_html('srdb', $title, $sectionhtml);

// Dynamic hardcoded (TODO make reqistrar_query return parameter types it expects)
ucla_require_registrar();
$qs = registrar_query::get_all_available_queries();

foreach ($qs as $query) {
    $sectionhtml = '';
    if ($displayforms) {
        // generate input parameters
        $input_html = '';
        switch ($query) {
            // uid, term
            case 'ucla_get_user_classes':
                $input_html .= get_uid_input($query);      
                $input_html .= get_term_selector($query);                
                break;
            // term, subject area
            case 'ccle_coursegetall': 
            case 'ccle_getinstrinfo':
            case 'cis_coursegetall':                                
                $input_html .= get_term_selector($query);
                $input_html .= get_subject_area_selector($query);
                break;            
            // term, srs
            case 'ccle_courseinstructorsget':            
            case 'ccle_getclasses':
            case 'ccle_roster_class':
                $input_html .= get_term_selector($query);
                $input_html .= get_srs_input($query);      
                break;
            // term
            case 'cis_subjectareagetall': 
            case 'ucla_getterms':
                $input_html .= get_term_selector($query);
                break;
            // unknown
            default: 
                break;
        }
        
        if (empty($input_html)) {
            continue;   // skip it
        }     

        $sectionhtml .= supportconsole_simple_form($query, $input_html);
    } else if ($consolecommand == $query) {
        
        /* Possible params:
         * term
         * subjarea
         * srs
         * uid
         */
        $params = array();
        $possible_params = array('term', 'subjarea', 'srs', 'uid');
        foreach ($possible_params as $param_name) {
            if ($param_value = optional_param($param_name, '', PARAM_NOTAGS)) {
                $params[$param_name] = $param_value;
            }
        }
        $sendparams = array($params);
        
        $allresults = registrar_query::run_registrar_query($query, $sendparams);

        $results = array_merge($allresults[registrar_query::query_results], 
            $allresults[registrar_query::failed_outputs]);

        $sectionhtml .= supportconsole_render_section_shortcut($title, 
            $results, $params);
    }

    $consoles->push_console_html('srdb', $query, $sectionhtml);
}

///////////////////////////////////////////////////////////////////////////////
$title = "countmodules";

// Use API
$item_names = array();

if ($displayforms) {
    // $result = $DB->get_records_sql();
    // Show number of courses per term?

} else if ($consolecommand == "$title") {
	$itemfile = $_POST['itemname'];
	$term     = $_POST['term'];

    echo "<h3>$title $itemfile</h3>\n";
    echo "<i>Term: $term Resource/Activity: $itemfile</i><br>\n";
	
	if($itemfile=='forumposts'){
		$log_query="SELECT c.id as ID, c.shortname as COURSE ,count(*) as Posts, c.fullname as Full_Name
							FROM mdl_course c 
							INNER JOIN  mdl_forum_discussions d ON d.course = c.id 
							INNER JOIN mdl_forum_posts p ON p.discussion = d.id
							WHERE c.idnumber LIKE '$term%'
							GROUP by c.id
							ORDER BY Posts DESC
							";
	} else {
	$log_query="SELECT c.id, COUNT(l.id) as count, c.shortname
        FROM {$CFG->prefix}$itemfile l
        		INNER JOIN {$CFG->prefix}course c on l.course = c.id
        WHERE c.idnumber like '$term%'        
        GROUP BY left(c.idnumber,3), course
        ORDER BY left(c.idnumber,3), count DESC";
    }

    $result=$DB->get_records_sql($log_query);

// Display results with course edit and view links for forum posts
// Display results with just course view links for others...
// Split forum posts out of this
}

// TODO UCLA Datasync library views

////////////////////////////////////////////////////////////////////
// CLASS SITES, CAMP SITES, HIND SIGHTS, MASS HEIGHTS
////////////////////////////////////////////////////////////////////
$title="collablist";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $result=mysql_query("select "
        . "elt(c.visible + 1, 'Hidden', 'Visible') as Hidden,elt(c.guest + 1, 'Private', 'Public') as Guest,c.format,cc.name, concat('<a href=\"{$CFG->wwwroot}/course/view.php?id=', c.id, '\">', c.shortname, '</a>') as 'Link', c.fullname "
        . "from mdl_course c "
        . "left join mdl_ucla_tasites t using(shortname) "
        . "left join mdl_course_categories cc on c.category=cc.id "
        . 'where idnumber="" '
        . 'and t.shortname is NULL '
        . 'and format <>"uclaredir" '
        . 'and cc.name not in ("To Be Deleted", "Demo/Testing") '
        . 'order by cc.name, c.shortname') or die(mysql_error());
    $days = $_POST['days'];

    echo "<h3>$title</h3>\n";

    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows courses.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

////////////////////////////////////////////////////////////////////
$title = "courseregistrardifferences";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title, get_term_selector($title));
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $term = required_param('term', PARAM_ALPHANUM);    
    $sql = "SELECT  c.id AS courseid,
                    c.shortname AS course,
                    regc.crs_desc AS old_description,
                    c.summary AS new_description,
                    regc.coursetitle,
                    regc.sectiontitle
            FROM    {course} AS c,
                    {ucla_reg_classinfo} AS regc,
                    {ucla_request_classes} AS reqc
            WHERE   reqc.term=:term AND
                    reqc.courseid=c.id AND
                    reqc.term=regc.term AND
                    reqc.hostcourse=1 AND
                    reqc.srs=regc.srs AND
                    STRCMP(c.summary, regc.crs_desc)!=0";
    $result = $DB->get_records_sql($sql, array('term' => $term));

    foreach ($result as $k => $course) {
        if (isset($course->courseid)) {
            $course->courselink = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $course->courseid)
                ), $course->course);
            unset($course->course);
            $result[$k] = $course;
        }
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $result,
        $term);
}

$consoles->push_console_html('srdb', $title, $sectionhtml);
////////////////////////////////////////////////////////////////////
// START SSC #775 - Adding missing reports from SSC into CommonCode
/////////////////////////////////////////////////////////////////////////
///// Moodle 2.0 does not have TA Sites yet thus this code was commented out
////////////////////////////////////////////////////////////////////

//// Finding courses with no syllabus (temporary until we get a real syllabus tool working) 
//$title = "nosyllabuscourses";
//$sectionhtml = '';
//if ($displayforms) {
//    $sectionhtml .= supportconsole_simple_form($title, get_term_selector($title));
//} else if ($consolecommand == $title) {  # tie-in to link from name lookup
//    $term = required_param('term', PARAM_ALPHANUM);
//    if (!ucla_validator('term', $term)) {
//        print_error('invalidterm');
//    }
//        
//    $sql = "SELECT      c.id AS course_id,
//                        c.shortname AS course_shortname
//            FROM        {course} c                        
//            JOIN        {ucla_request_classes} urc ON (urc.courseid=c.id)
//            LEFT JOIN   {resource} r ON (r.course=c.id)            
//            WHERE       urc.term=:term AND
//                        r.name NOT LIKE '%course description%' AND 
//                        r.name NOT LIKE '%course outline%' AND 
//                        r.name NOT LIKE '%syllabus%'
//            ORDER BY c.shortname";    
//    $result = $DB->get_records_sql($sql, array('term' => $term));
//
//    $sectionhtml .= supportconsole_render_section_shortcut($title, $result);
//}
//
//$consoles->push_console_html('modules', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "assignmentquizzesduesoon";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml .= supportconsole_simple_form($title,
        html_writer::label('Start date ("MM/DD/YYYY")', 'startdate')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'startdate',
                    'id' => 'startdate',
                    'value' => date('m/d/Y')
                ))
            . html_writer::label('Days from start', 'datedays')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'datedays',
                    'id' => 'datedays',
                    'value' => 7
                )));
} else if ($consolecommand == "$title") {  # tie-in to link from name lookup
    $timefromstr = required_param('startdate', PARAM_RAW);
    $timefrom = strtotime($timefromstr);
    
    $days = required_param('datedays', PARAM_NUMBER);
    $daysec = $days * 86400;
    $timeto = $timefrom + $daysec;
    
    $results = $DB->get_records_sql("
        SELECT 
            c.id AS courseid,
            m.Due_date, 
            c.shortname AS Class, 
            c.Fullname,
            m.modtype, 
            m.Name
        FROM ((
            SELECT 
                'quiz' AS modtype, 
                course, 
                name, 
                FROM_UNIXTIME(timeclose) AS Due_Date
                FROM {quiz}
                WHERE timeclose
                BETWEEN  {$timefrom} AND {$timeto}
        ) UNION (
            SELECT 
                'assignment' AS modtype, 
                course, 
                name, 
                FROM_UNIXTIME(timedue, '%m-%d-%y %H:%i %a') AS Due_Date
            FROM {assignment}
            WHERE timedue
            BETWEEN {$timefrom} AND {$timeto}
            )) AS m
        INNER JOIN {course} c ON c.id = m.course
        ORDER BY `m`.`Due_Date` ASC
    ");    

    foreach ($results as $k => $result) {
        if (isset($result->courseid)) {
            $result->courseid = html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $result->courseid)
                ), $result->courseid);
            $results[$k] = $result;
        }
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results,
        array("From $timefromstr to " . date('m/d/Y', $timeto), "$days days"));
}

$consoles->push_console_html('modules', $title, $sectionhtml);

//////////////////////////////////////////////////////////////////////////////////////////
$title = "modulespercourse";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {  
    // Mapping of [course shortname, module name] => count of 
    // instances of this module in this course
    // count($course_indiv_module_counts[<course shortname>]) has 
    // the number kinds of modules used in this course
    $course_indiv_module_counts = array();
    
    // Mapping of course shortname => count of instances of 
    // all modules in this course
    $course_total_module_counts = array();
    
    $results = $DB->get_records_sql("
        SELECT 
            cm.id,
            c.id AS courseid,
            c.shortname AS shortname,
            m.name AS modulename, 
            count(*) AS cnt
        FROM {course} c
        JOIN {course_modules} cm ON c.id = cm.course
        JOIN {modules} m ON cm.module = m.id
        GROUP BY c.id, m.id
    ");

    $courseshortnames = array();

    foreach ($results as $result) {
        $sn = $result->courseid;
        $mn = $result->modulename;
        $courseshortnames[$sn] = $result->shortname;

        if (!isset($course_indiv_module_counts[$sn][$mn])) {
            $course_indiv_module_counts[$sn][$mn] = 0;
        }

        $course_indiv_module_counts[$sn][$mn] += $result->cnt;
    }

    $tabledata = array();
    foreach ($course_indiv_module_counts as $courseid => $modulecounts) {
        $rowdata = array(
            'course' => html_writer::link(new moodle_url(
                    '/course/view.php', array('id' => $courseid)
                ), $courseshortnames[$courseid]),
            'total' => array_sum($modulecounts)
        );


        foreach ($modulecounts as $modulename => $moduleinst) {
            $rowdata[$modulename] = $moduleinst;
        }

        $tabledata[] = $rowdata;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title,
        $tabledata);


}

$consoles->push_console_html('modules', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// USER RELATED REPORTS 
////////////////////////////////////////////////////////////////////
$title = "roleassignments";
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title);
} else if ($consolecommand == "$title") { 
    $results = $DB->get_records_sql("
        SELECT 
            a.id,
            b.name,
            b.shortname,
            component,
            COUNT(*) AS cnt 
        FROM {role_assignments} a 
        LEFT JOIN {role} b ON (a.roleid = b.id) 
        GROUP BY component, roleid
    ");

    $admin_result = get_config(null, 'siteadmins');
    if (empty($admin_result) && empty($result)) {
       $sectionhtml .= html_writer::error_text("There are no enrollments");
    }

    foreach ($results as $key => $result) {
        if ($result->component == '') {
            $result->component = 'manual';
        }
    }

    $admin_cnt = count(explode(',', $admin_result));
    $adminrow = new object();
    $adminrow->name = 'Administrators';
    $adminrow->shortname = 'admin';
    $adminrow->component = 'admin';
    $adminrow->cnt = $admin_cnt;
    $results[] = $adminrow;

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "countnewusers";
$sectionhtml = '';
if ($displayforms) { 
    $sectionhtml .= supportconsole_simple_form($title, 
        html_writer::label('Number of users to show', 'count')
            . html_writer::empty_tag('input', array(
                'name' => 'count', 
                'id' => 'count',
                'value' => 20,
                'size' => 3
            )));
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} else if ($consolecommand == "$title") { 
    $count = required_param('count', PARAM_INT);
    $distinct = ""; 
    $results = $DB->get_records_sql("
        SELECT 
            id,
            idnumber,
            lastname, 
            firstname,
            IF (timemodified = 0,
                    'Never',
                    FROM_UNIXTIME(timemodified, '%Y-%m-%d')
                ) AS Time_Modified,
            IF (firstaccess = 0,
                    'Never',
                    FROM_UNIXTIME(firstaccess, '%Y-%m-%d')
                ) AS First_Access,
            IF (lastaccess = 0,
                    'Never',
                    FROM_UNIXTIME(lastaccess, '%Y-%m-%d')
                ) AS Last_Access,
            IF (lastlogin = 0,
                    'Never',
                    FROM_UNIXTIME(lastlogin, '%Y-%m-%d')
                ) AS Last_Login
        FROM {user} 
        ORDER BY id DESC 
        LIMIT $count
    ");

    foreach ($results as $k => $result) {
        $result->delete = html_writer::link(new moodle_url('/admin/user.php',
            array('delete' => $result->id)), 'Delete');

        $result->view = html_writer::link(new moodle_url('/user/view.php',
            array('id' => $result->id)), 'View');

        $results[$k] = $result;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}

$consoles->push_console_html('users', $title, $sectionhtml);

if (isset($consoles->no_finish)) {
    echo html_writer::link(new moodle_url($PAGE->url), 'Back');
    die();
}

echo $OUTPUT->header();
if (!$displayforms) {
    echo html_writer::link(new moodle_url($PAGE->url), 'Back');
    echo $consoles->render_results();
} else {
    // Put srdb stuff first
    $consoles->sort(
        array(
            'srdb' => array(
                'ccle_getclasses' => ''
            )
        )
    );

    echo $consoles->render_forms();
}
echo $OUTPUT->footer();
