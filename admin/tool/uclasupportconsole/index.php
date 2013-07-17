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
require_once($CFG->dirroot . '/admin/tool/ucladatasourcesync/lib.php');

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
        echo "Invalid logfile name. $log_file";
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
    echo html_writer::tag('h1', get_string('prepoprun', 'tool_uclasupportconsole'));
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

    // Get module/action pairs from cached log.
    $mapairs = get_config('tool_uclasupportconsole', 'moduleactionpairs');
    $mapairs = json_decode($mapairs);

    $checkboxes = array();
    $lastmodule = '';

    if (!empty($mapairs)) {
        foreach($mapairs as $pair) {
            $module = $pair->module;
            $action = $pair->action;

            // If this is the first module in our list of its kind, we must output
            // it as a heading above the actions
            if ($module != $lastmodule) {
                $lastmodule = $module;
                $checkboxes[] = html_writer::tag('h4', $module);
            }

            // Create the checkbox array for holding action types. The actions
            // are represented as "module_action" (for example: user_login).
            $checkboxes[] = html_writer::tag('li',
                    html_writer::checkbox('actiontypes[]', $module . '_' . $action,
                            false, $action));
        }
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
    
    // Display the filter checkbox form.
    $form_content .= html_writer::start_tag('div', $action_types_container_params);
    $form_content .= html_writer::label(get_string('moodlelog_select', 'tool_uclasupportconsole'), 
                'log-action-types') . 
                html_writer::tag('ul', implode('', $checkboxes), 
                array('id' => 'log-action-types'));
    $form_content .= html_writer::end_tag('div');
    
    $sectionhtml = supportconsole_simple_form($title, $form_content);
} else if ($consolecommand == "$title") { 
    
    // Initialize empty containers for the database query ($logquery), the 
    // query parameters ($params), and the header to be displayed at the top of 
    // the results ($headertext).
    $logquery = '';
    $params = array();
    $headertext = array();
    
    // Get the module, action pairs (represented as module_action).
    $moduleactions = optional_param_array('actiontypes', array(), PARAM_TEXT);
    
    // If there are no actions selected, either the form was hidden, or no
    // checkboxes were selected. In both cases, this results in the log not
    // filtering out any entries (the last 100 entries of any type are shown).
    if (empty ($moduleactions))
    {
        $logquery = "
            SELECT
                a.id, 
                from_unixtime(time) AS time,
                b.firstname,
                b.lastname,
                ip,
                c.shortname,
                c.id AS courseid,
                module,
                action
            FROM {log} a
            LEFT JOIN {user} b ON (a.userid = b.id)
            LEFT JOIN {course} c ON (a.course = c.id)
            ORDER BY a.id DESC LIMIT 100
        ";
    }
    else
    {
        // The $modules and $actions arrays are filled with the pairs of modules
        // and actions found in $module_actions. The $header_text is also made
        // at this point, and used after the query.
        $modules = array();
        $actions = array();
        foreach ($moduleactions as $ma) {
            $moduleactionpair = explode("_", $ma);
            array_push($modules, $moduleactionpair[0]);
            array_push($actions, $moduleactionpair[1]);
            
            $headertextstring = $moduleactionpair[1] . ' ' . $moduleactionpair[0];
            array_push($headertext, $headertextstring);
        }

        // Create the necessary conditional statements and their parameters to
        // be used in the query that follows. This must be done for both actions
        // and modules for the different clauses of the AND in the conditional
        // statement.
        list($actsql, $actparams) = $DB->get_in_or_equal($actions);
        $wheresqlactions = 'action ' . $actsql;
        list($modsql, $modparams) = $DB->get_in_or_equal($modules);
        $wheresqlmodules = 'module ' . $modsql;

        // Merge the parameters into a single array for use by the querying
        // function.
        $params = array_merge($actparams, $modparams);
        $logquery = "
            SELECT
                a.id, 
                from_unixtime(time) AS time,
                b.firstname,
                b.lastname,
                ip,
                c.shortname,
                c.id AS courseid,
                module,
                action
            FROM {log} a
            LEFT JOIN {user} b ON (a.userid = b.id)
            LEFT JOIN {course} c ON (a.course = c.id)
            WHERE $wheresqlactions AND $wheresqlmodules
            ORDER BY a.id DESC LIMIT 100
        ";
    }
    
    $results = $DB->get_records_sql($logquery, $params);

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
        $headertext);
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
            from_unixtime(time) as logintime,
            b.firstname,
            b.lastname,
            ip,
            a.url
        from {log} a 
        left join {user} b on(a.userid=b.id)
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) and action='login'
        order by a.id desc
        ";

    $result = $DB->get_records_sql($log_query);

    foreach($result as $k => $res) {
        $res->user = html_writer::link(new moodle_url("/user/$res->url"), 
            "$res->firstname $res->lastname", array('target'=>'_blank'));
        
        // unset unneeded data for display
        unset($res->id);      
        unset($res->firstname);     
        unset($res->lastname);     
        unset($res->url);     

        $result[$k] = $res;        
    }

    echo supportconsole_render_section_shortcut($title, $result);

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
        WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND
                c.id!=:siteid
        GROUP BY date, course 
        ORDER BY count DESC
        LIMIT 100
    ", array('siteid' => SITEID));

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
        WHERE FROM_UNIXTIME(time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND
            c.id!=:siteid
        GROUP BY day, course, a.userid 
        ORDER BY count DESC
        LIMIT 100
    ", array('siteid' => SITEID));
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result);
} 
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlevideofurnacelist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('video_furnace');
    
    $sourcelocation = get_config('block_ucla_video_furnace', 'source_url');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlelibraryreserveslist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('library_reserves');
    
    $sourcelocation = get_config('block_ucla_library_reserves', 'source_url');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);    
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "moodlebruincastlist";
$sectionhtml = '';

if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title);
} else if ($consolecommand == "$title") {
    $result = get_reserve_data('bruincast');
    
    $sourcelocation = get_config('block_ucla_bruincast', 'source_url');
    $sourcelink = html_writer::link($sourcelocation, $sourcelocation, array('target' => '_blank'));
    $sourcefile = get_string('sourcefile', 'tool_uclasupportconsole', $sourcelink);    
    
    $sectionhtml = supportconsole_render_section_shortcut($title, $result, array(), $sourcefile);
}
$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
// Date selector for both Syllabus overview and Syllabus report
$syllabusdateselector = html_writer::label('Start date ("MM/DD/YYYY")', 'startdate') .
            html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'startdate',
                    'id' => 'startdate'
                )) . 
            html_writer::label('End date ("MM/DD/YYYY")', 'enddate') .
            html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'name' => 'enddate',
                    'id' => 'enddate'
                ));

$title = "syllabusoverview";
$sectionhtml = '';

if ($displayforms) {
    $overview_options = html_writer::tag('option', get_string('syllabus_division', 'tool_uclasupportconsole'), 
            array('value' => 'division'));
    $overview_options .= html_writer::tag('option', get_string('syllabus_subjarea', 'tool_uclasupportconsole'), 
            array('value' => 'subjarea'));
    
    $syllabus_selectors = get_term_selector($title);
    
    $syllabus_selectors .= html_writer::tag('label', get_string('syllabus_browseby', 'tool_uclasupportconsole')) . 
            html_writer::tag('select', $overview_options, array('name' => 'syllabus'));
    
    $syllabus_selectors .= html_writer::start_tag('br') . $syllabusdateselector;
    
    $sectionhtml = supportconsole_simple_form($title, $syllabus_selectors);
    
} else if ($consolecommand == "$title") {
    $sectionhtml .= $OUTPUT->box(get_string('syllabusoverviewnotes',
            'tool_uclasupportconsole'));

    $selected_term = required_param('term', PARAM_ALPHANUM);
    $selected_type = required_param('syllabus', PARAM_ALPHA);
    
    $timestartstr = optional_param('startdate', '', PARAM_RAW);
    $timeendstr = optional_param('enddate', '', PARAM_RAW);
    
    $timesql = '';
    $timerange = '';
    $uploaddisplaymsg = get_string('syllabustimerange', 'tool_uclasupportconsole');
    if ($timestart = strtotime($timestartstr)) {
        $timesql .= ' AND s.timecreated >= ' . $timestart;
        $timerange .= $uploaddisplaymsg . ' starting from ' . $timestartstr;
    }
    if ($timeend = strtotime($timeendstr)) {
        $timesql .= ' AND s.timecreated <= ' . $timeend;
        $timerange .= ($timestart ? '' : $uploaddisplaymsg) . ' up to '. $timeendstr;
    }
    
    $syllabus_table = new html_table();
    
    $sql = '';

    $table_colum_name = get_string('syllabus_division', 'tool_uclasupportconsole');
    if ($selected_type == 'subjarea') {
        // List courses by subject area
        $sql = 'SELECT      urci.id, urs.subjarea AS code, urs.subj_area_full AS fullname, 
                            urci.crsidx AS catalognum, urc.courseid
                FROM        {ucla_reg_subjectarea} AS urs,
                            {ucla_reg_classinfo} AS urci,
                            {ucla_request_classes} AS urc
                WHERE       urci.term =:term AND
                            urs.subjarea = urci.subj_area AND
                            urci.term = urc.term AND 
                            urci.srs = urc.srs AND
                            urci.enrolstat != \'X\' AND
                            urci.acttype != \'TUT\'
                ORDER BY    urs.subjarea';
        $table_colum_name = get_string('syllabus_subjarea', 'tool_uclasupportconsole');
    } else {
        // List course by division
        $sql = 'SELECT      urci.id, urd.code, urd.fullname, 
                            urci.crsidx AS catalognum, urc.courseid
                FROM        {ucla_reg_division} AS urd,
                            {ucla_reg_classinfo} AS urci,
                            {ucla_request_classes} AS urc
                WHERE       urci.term =:term AND
                            urd.code = urci.division AND
                            urci.term = urc.term AND 
                            urci.srs = urc.srs AND
                            urci.enrolstat != \'X\' AND
                            urci.acttype != \'TUT\'
                ORDER BY    urd.fullname';
    }

    $table_colum_name .= ' (' . $selected_term . ')';

    $params = array();
    $params['term'] = $selected_term;
    if ( $course_list = $DB->get_records_sql($sql, $params) ) {
        // include locallib for syllabus constants, not the manager
        // (too much overhead)
        require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

        // setup bins for ugrad/grad syllabus totals
        /* bins are setup as follows:
         * [div or subject area (fullname)]
         *      [total_courses]
         *      [syllabi_courses]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]
         *      [UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]
         *      [preview]
         */
        $ugrad = array();
        $grad = array();

        // go through each course
        $syllabus_cache = array();  // we might be querying the same courseid
                                    // multiple times
        $working_bin = null;    // pointer to array we are incrementing
        foreach ($course_list as $course) {            
            // is this a grad course?
            if (intval(preg_replace("/[a-zA-Z]+/", '', $course->catalognum)) >= 200) {
                $working_bin = &$grad;
            } else {
                $working_bin = &$ugrad;
            }

            if (!isset($working_bin[$course->fullname])) {
                $working_bin[$course->fullname] = array();
            }

            // increment course count (note: decrementing NULL values has no
            // effect, but incrementing them results in 1, so need to check for
            // null values. however, will need to add @ to supress php notices)
            @++$working_bin[$course->fullname]['total_courses'];

            // now get syllabus information
            if (!isset($syllabus_cache[$course->courseid])) {
                $sql = 'SELECT *
                        FROM {ucla_syllabus} AS s
                        WHERE s.courseid =:courseid' . $timesql;
                $params['courseid'] = $course->courseid;
                $syllabus_cache[$course->courseid] = $DB->get_records_sql($sql, $params);
            }
            $syllabi = $syllabus_cache[$course->courseid];
            if (!empty($syllabi)) {
                // course has a syllabus, let's count it
                @++$working_bin[$course->fullname]['syllabi_courses'];
                $is_preview = false;
                foreach ($syllabi as $syllabus) {
                    if (!empty($syllabus->is_preview)) {
                        $is_preview = true;
                    }
                    switch ($syllabus->access_type) {
                        case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC];
                            break;
                        case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                            break;
                        case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                            @++$working_bin[$course->fullname][UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE];
                            break;
                        default:
                            break;
                    }
                    if (!empty($is_preview)) {
                        @++$working_bin[$course->fullname]['preview'];
                    }
                }
            }

            // Check if there were any manual syllabi.
            $courserecord = $DB->get_record('course', array('id' =>  $course->courseid));
            $ucla_syllabus_manager = new ucla_syllabus_manager($courserecord);
            $manualsyllabi = $ucla_syllabus_manager->get_all_manual_syllabi($timestart, $timeend);
            if (!empty($manualsyllabi)) {
                @++$working_bin[$course->fullname]['manual'];
                // Only increment number of courses that have syllabi if syllabus tool wasn't used.
                if (empty($syllabi)) {
                    @++$working_bin[$course->fullname]['syllabi_courses'];
                }
            }
            unset($ucla_syllabus_manager);
            
        }
        unset($syllabus_cache); // no need to keep this cache anymore

        // now format both data arrays to calculate totals and create array 
        // suitable for passing as a data array for html_table
        $processing['ugrad'] = array('data' => $ugrad, 'table' => new html_table());
        $processing['grad'] = array('data' => $grad, 'table' => new html_table());
        foreach ($processing as $type => &$data) {
            // keep totals for headers
            $header_totals = array();

            $table_data = array();
            $working_data = $data['data'];
            $working_table = $data['table'];
            foreach ($working_data as $fullname => $syllabi_counts) {
                $table_row = array();

                // col1: divison or subject area
                $table_row[] = $fullname;

                // col2: syllabus/courses
                @$header_totals['total_courses'] += $syllabi_counts['total_courses'];
                @$header_totals['syllabi_courses'] += $syllabi_counts['syllabi_courses'];
                if (empty($syllabi_counts['total_courses'])) {
                    $table_row[] = 0;
                } else {
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts['syllabi_courses'], 
                            $syllabi_counts['total_courses'],
                            round(($syllabi_counts['syllabi_courses']/
                             $syllabi_counts['total_courses'])*100));
                }

                // if there are no courses with syllabi, then we can skip
                if (empty($syllabi_counts['syllabi_courses'])) {
                    $table_row[] = 0;   // public
                    $table_row[] = 0;   // loggedin
                    $table_row[] = 0;   // preview
                    $table_row[] = 0;   // private
                    $table_row[] = 0;   // manual
                } else {
                    // col3: public syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col4: loggedin syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col5: preview syllabus
                    @$totalpublic = $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] +
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                    @$header_totals['preview'] += $syllabi_counts['preview'];
                    if (empty($totalpublic)) {
                        $table_row[] = 0;
                    } else {
                        @$table_row[] = sprintf('%d/%d (%d%%)',
                                $syllabi_counts['preview'],
                                $totalpublic,
                                round(($syllabi_counts['preview']/
                                 $totalpublic)*100));
                    }

                    // col6: private syllabus
                    @$header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE] += $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]/
                             $syllabi_counts['syllabi_courses'])*100));

                    // col7: manual syllabus
                    @$header_totals['manual'] += $syllabi_counts['manual'];
                    @$table_row[] = sprintf('%d/%d (%d%%)',
                            $syllabi_counts['manual'],
                            $syllabi_counts['syllabi_courses'],
                            round(($syllabi_counts['manual']/
                             $syllabi_counts['syllabi_courses'])*100));
                }

                $table_data[] = $table_row;
            }
            $working_table->data = $table_data;

            // create header information
            $syllabus_count = 0;
            if (!empty($header_totals['total_courses'])) {
                $syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals['syllabi_courses'],
                        $header_totals['total_courses'],
                        round(($header_totals['syllabi_courses']/
                         $header_totals['total_courses'])*100));
            }

            $public_syllabus_count = 0;
            $loggedin_syllabus_count = 0;
            $preview_syllabus_count = 0;
            $private_syllabus_count = 0;
            $manual_syllabus_count = 0;
            if (!empty($header_totals['syllabi_courses'])) {
                $public_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC]/
                         $header_totals['syllabi_courses'])*100));
                $loggedin_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN]/
                         $header_totals['syllabi_courses'])*100));
                $totalpublic = $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC] +
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN];
                if (!empty($totalpublic)) {
                    $preview_syllabus_count = sprintf('%d/%d (%d%%)',
                            $header_totals['preview'],
                            $totalpublic,
                            round(($header_totals['preview']/
                             $totalpublic)*100));
                }
                $private_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE],
                        $header_totals['syllabi_courses'],
                        round(($header_totals[UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE]/
                         $header_totals['syllabi_courses'])*100));
                $manual_syllabus_count = sprintf('%d/%d (%d%%)',
                        $header_totals['manual'],
                        $header_totals['syllabi_courses'],
                        round(($header_totals['manual']/
                         $header_totals['syllabi_courses'])*100));
            }

            $working_table->head = array($table_colum_name,
                get_string('syllabus_count', 'tool_uclasupportconsole',
                        $syllabus_count),
                get_string('public_syllabus_count', 'tool_uclasupportconsole',
                        $public_syllabus_count),
                get_string('loggedin_syllabus_count', 'tool_uclasupportconsole',
                        $loggedin_syllabus_count),
                get_string('preview_syllabus_count', 'tool_uclasupportconsole',
                        $preview_syllabus_count),
                get_string('private_syllabus_count', 'tool_uclasupportconsole',
                        $private_syllabus_count),
                get_string('manual_syllabus_count', 'tool_uclasupportconsole',
                        $manual_syllabus_count));
        }
    }
        
    $sectionhtml .= $OUTPUT->box_start();
    $sectionhtml .= html_writer::tag('h3',
            get_string('syllabus_ugrad_table', 'tool_uclasupportconsole') . html_writer::start_tag('br') .
            $timerange);
    $sectionhtml .= isset($processing['ugrad']['table']) ? html_writer::table($processing['ugrad']['table']) : 
        get_string('nocourses', 'tool_uclasupportconsole');
    $sectionhtml .= $OUTPUT->box_end();
    
    $sectionhtml .= $OUTPUT->box_start();
    $sectionhtml .= html_writer::tag('h3',
            get_string('syllabus_grad_table', 'tool_uclasupportconsole') . html_writer::start_tag('br') .
            $timerange);
    $sectionhtml .= isset($processing['grad']['table']) ? html_writer::table($processing['grad']['table']) : 
        get_string('nocourses', 'tool_uclasupportconsole');
    $sectionhtml .= $OUTPUT->box_end();
}

$consoles->push_console_html('modules', $title , $sectionhtml);

////////////////////////////////////////////////////////////////////
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

$title = "syllabusreoport";
$sectionhtml = '';

if ($displayforms) {
    
    $syllabus_selectors = get_term_selector($title);
    $syllabus_selectors .= get_subject_area_selector($title);
    
    $syllabus_selectors .= html_writer::start_tag('br') . $syllabusdateselector;
    
    $sectionhtml = supportconsole_simple_form($title, $syllabus_selectors);
    
} else if ($consolecommand == "$title") {
    $sectionhtml .= $OUTPUT->box(get_string('syllabusreoportnotes',
            'tool_uclasupportconsole'));

    $selected_term = required_param('term', PARAM_ALPHANUM);
    $selected_subj = required_param('subjarea', PARAM_NOTAGS);
    
    $timestartstr = optional_param('startdate', '', PARAM_RAW);
    $timeendstr = optional_param('enddate', '', PARAM_RAW);
    
    $timesql = '';
    $timerange = '';
    $uploaddisplaymsg = get_string('syllabustimerange', 'tool_uclasupportconsole');
    if ($timestart = strtotime($timestartstr)) {
        $timesql .= ' AND s.timecreated >= ' . $timestart . ' ';
        $timerange .= $uploaddisplaymsg . ' starting from ' . $timestartstr;
    }
    if ($timeend = strtotime($timeendstr)) {
        $timesql .= ' AND s.timecreated <= ' . $timeend . ' ';
        $timerange .= ($timestart ? '' : $uploaddisplaymsg) . ' up to '. $timeendstr;
    }
    
    $sql = "SELECT      CONCAT(COALESCE(s.id, ''), urc.srs) AS idsrs, 
                        urc.department,
                        urc.course,
                        s.access_type,
                        urc.courseid
            FROM        {ucla_request_classes} AS urc
            JOIN        {ucla_reg_classinfo} AS uri ON (
                        urc.term=uri.term AND
                        urc.srs=uri.srs)
            LEFT JOIN   {ucla_syllabus} AS s ON (urc.courseid = s.courseid {$timesql})
            WHERE       urc.term =:term AND
                        urc.department =:department AND
                        uri.enrolstat != 'X' AND
                        uri.acttype != 'TUT'                        
            ORDER BY    uri.term, uri.subj_area, uri.crsidx, uri.secidx";
    
    $params = array();
    $params['term'] = $selected_term; 
    $params['department'] = $selected_subj;
    
    $syllabus_info = array();
    $num_public = 0;
    $num_private = 0;
    $num_courses = 0;
    $num_manual = 0;
    if ($syllabus_report = $DB->get_records_sql($sql, $params)) {        
        foreach ($syllabus_report as $crs_syl) {
            $access_public = $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC
                    || $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $access_private = $crs_syl->access_type == UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
            
            $course_name = $crs_syl->department . ' ' . $crs_syl->course;
            $course_name = html_writer::link($CFG->wwwroot . '/course/view.php?id=' .
                    $crs_syl->courseid, $course_name, array('target' => '_blank'));
            $syllabus_record = array($course_name);
            
            if (empty($crs_syl->access_type)) {
                $syllabus_record[2] = '';
                $syllabus_record[3] = '';
            } else if ($access_public) {
                $syllabus_record[2] = 'x';
                $syllabus_record[3] = '';
                $syllabus_record[4] = '';
                $num_public++;
            } else if ($access_private) {
                $syllabus_record[2] = '';
                $syllabus_record[3] = 'x';
                $syllabus_record[4] = '';
                $num_private++;
            }

            // Check if course has a manual syllabus.
            $courserecord = $DB->get_record('course', array('id' =>  $crs_syl->courseid));
            $ucla_syllabus_manager = new ucla_syllabus_manager($courserecord);
            $manualsyllabi = $ucla_syllabus_manager->get_all_manual_syllabi($timestart, $timeend);
            if (!empty($manualsyllabi)) {
                $syllabus_record[4] = count($manualsyllabi);
                $num_manual++;
            } else {
                $syllabus_record[4] = '';
            }
            unset($ucla_syllabus_manager);

            // If the previous course processed is the same course, then just update
            // that course instead of creating a new row
            if ($num_courses > 0 && $syllabus_info[$num_courses - 1][0] == $course_name) {
                if ($access_public) {
                    $syllabus_info[$num_courses - 1][2] = 'x';
                } else if ($access_private) {
                    $syllabus_info[$num_courses - 1][3] = 'x';
                }
            } else {
                $syllabus_info[$num_courses] = $syllabus_record;
                $num_courses++;
            }
        }
    }
    
    $head_info = new stdClass();
    $head_info->term = $selected_term;
    $head_info->num_courses = $num_courses;
    $syllabus_table = new html_table();
    $syllabus_table->id = 'syllabusreoport';
    $table_headers = array(get_string('syllabus_header_course', 'tool_uclasupportconsole', $head_info) .
        html_writer::start_tag('br') . $timerange,
        get_string('syllabus_header_public', 'tool_uclasupportconsole', $num_public), 
        get_string('syllabus_header_private', 'tool_uclasupportconsole', $num_private),
        get_string('syllabus_header_manual', 'tool_uclasupportconsole', $num_manual)
        );

    $syllabus_table->head = $table_headers;
    $syllabus_table->data = $syllabus_info;
    
    $sectionhtml .= html_writer::table($syllabus_table);
}

$consoles->push_console_html('modules', $title , $sectionhtml);

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
    $uid = required_param('uid', PARAM_RAW);
    ucla_require_registrar();
    $adodb = registrar_query::open_registrar_connection();

    if (ucla_validator('uid', $uid)) {
        $recset = $adodb->Execute('SELECT * FROM enroll2_test WHERE uid = ' . $uid 
            . ' ORDER BY term_int DESC, subj_area, catlg_no, sect_no');

        $usercourses = array();
        if (!$recset->EOF) {
            while($fields = $recset->FetchRow()) {
                $usercourses[] = $fields;
            }
        }

        $sectionhtml .= supportconsole_render_section_shortcut($title, $usercourses, $uid);
    } else {
        $sectionhtml .= $OUTPUT->box($OUTPUT->heading($title, 3));
        $sectionhtml .= 'Invalid UID: [' . $uid . ']';
    }
}

$consoles->push_console_html('srdb', $title, $sectionhtml);

// Dynamic hardcoded (TODO make reqistrar_query return parameter types it expects)
ucla_require_registrar();
$qs = get_all_available_registrar_queries();

foreach ($qs as $query) {
    $sectionhtml = '';
    $input_html = '';
    if ($displayforms) {
        // generate input parameters
        $storedproc = registrar_query::get_registrar_query($query);

        if (!$storedproc) {
            continue;
        }

        $params = $storedproc->get_query_params();

        foreach ($params as $param) {
            switch($param) {
                case 'term':
                    $input_html .= get_term_selector($query);
                    break;
                case 'subjarea':
                    $input_html .= get_subject_area_selector($query);
                    break;
                case 'uid':
                    $input_html .= get_uid_input($query);
                    break;
                case 'srs':
                    $input_html .= get_srs_input($query);
                    break;
                default:
                    $input_html .= get_string('unknownstoredprocparam',
                        'tool_uclasupportconsole');
                    break;
            }
        }

        if (empty($input_html)) {
            continue;   // skip it
        }     

        $sectionhtml .= supportconsole_simple_form($query, $input_html);
    } else if ($consolecommand == $query) {
        // generate input parameters (optimized by putting inside 
        // conditionals)
        $storedproc = registrar_query::get_registrar_query($query);
        $spparams = $storedproc->get_query_params();

        foreach ($spparams as $param_name) {
            if ($param_value = optional_param($param_name, '', PARAM_NOTAGS)) {
                $params[$param_name] = $param_value;
            }
        }
        
                
        // get all data, even bad, and uncached
        $results = registrar_query::run_registrar_query($query, $params, false);
        
        if (!$good_data = $results[registrar_query::query_results]) {
            $good_data = array();
        }        
        $results = array_merge($good_data, $results[registrar_query::failed_outputs]);
        
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
            m.id AS module_id,
            m.Due_date, 
            c.shortname, 
            c.fullname,
            m.modtype, 
            m.Name
        FROM ((
            SELECT 
                id,
                'quiz' AS modtype, 
                course, 
                name, 
                FROM_UNIXTIME(timeclose) AS Due_Date
                FROM {quiz}
                WHERE timeclose
                BETWEEN  {$timefrom} AND {$timeto}
        ) UNION (
            SELECT 
                id,
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
    // add filter for term/subject area, because this table can get very big
    // and the query get return a ton of data
    $input_html = get_term_selector($title);
    $input_html .= get_subject_area_selector($title);        
    
    $sectionhtml = supportconsole_simple_form($title, $input_html);
} else if ($consolecommand == "$title") {  
    
    // get optional filters
    $term = optional_param('term', null, PARAM_ALPHANUM);
    if (!ucla_validator('term', $term)) {
        $term = null;
    }    
    $subjarea = optional_param('subjarea', null, PARAM_NOTAGS);
    
    // Mapping of [course shortname, module name] => count of 
    // instances of this module in this course
    // count($course_indiv_module_counts[<course shortname>]) has 
    // the number kinds of modules used in this course
    $course_indiv_module_counts = array();
    
    // Mapping of course shortname => count of instances of 
    // all modules in this course
    $course_total_module_counts = array();
    
    $params = array();
    $sql = "SELECT  cm.id,
                    c.id AS courseid,
                    c.shortname AS shortname,
                    m.name AS modulename, 
                    count(*) AS cnt
            FROM    {course} c
            JOIN    {course_modules} cm ON c.id = cm.course
            JOIN    {modules} m ON cm.module = m.id";
    
    // handle term/subject area filter
    if (!empty($term) || !empty($subjarea)) {
        $sql .= " JOIN  {ucla_request_classes} urc ON (urc.courseid=c.id)";
    }        
    if (!empty($term) && !empty($subjarea)) {
        $sql .= " WHERE urc.term=:term AND
                        urc.department=:subjarea";
        $params['term'] = $term;
        $params['subjarea'] = $subjarea;        
    } else if (!empty($term)) {
        $sql .= " WHERE urc.term=:term";
        $params['term'] = $term;    
    } else if (!empty($subjarea)) {
        $sql .= " WHERE urc.department=:subjarea";
        $params['subjarea'] = $subjarea;    
    }    
    
    $sql .= " GROUP BY c.id, m.id
             ORDER BY c.shortname";
    
    $results = $DB->get_records_sql($sql, $params);
    
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
    
    // Create an array with only the module names: $field
    $field = array();
    $tempfield = array();
    
    foreach ($tabledata as $tabledatum) {
        foreach ($tabledatum as $tablef => $tablev) {
            if ($tablef == 'id') {
                continue;
            }
            
            if ($tablef == 'course' || $tablef == 'total') {
                $field[$tablef] = $tablef;
                continue;
            }
            
            $tempfield[$tablef] = $tablef;
        }
    }
    
    asort($tempfield);
    $field = array_merge($field, $tempfield);
    
    foreach ($tabledata as & $courses) {
        $tempfield = $field;
        // Merge the courses array into the tempfield array.
        $courses = array_merge($tempfield, $courses);
        foreach ($courses as $module => & $count) {
            // If course does not have the module, make its count = 0.
            if ($module != 'course' && !is_numeric($count)) {
                $count = NULL;
            }   
        }    
    }
    $sectionhtml .= supportconsole_render_section_shortcut($title,
             $tabledata, $params);


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

    $sql = "SELECT  ra.id,
                    r.name,                    
                    c.contextlevel,
                    ra.component,
                    si.type,
                    COUNT(*) AS count
            FROM    {role_assignments} ra 
            JOIN    {context} c ON c.id = ra.contextid
            JOIN    {role} r ON (ra.roleid = r.id) 
            LEFT JOIN   {ucla_siteindicator} si ON 
                        (c.instanceid=si.courseid AND
                         c.contextlevel=50)
            GROUP BY contextlevel, ra.component, r.id
            ORDER BY c.contextlevel ASC, r.sortorder ASC";
    $results = $DB->get_records_sql($sql);

    $admin_result = get_config(null, 'siteadmins');
    if (empty($admin_result) && empty($result)) {
       $sectionhtml .= html_writer::error_text("There are no enrollments");
    } else {
        // get siteadmins, they are a different breed
        $admin_cnt = count(explode(',', $admin_result));
        $adminrow = new object();
        $adminrow->name = 'Site administrators';
        $adminrow->contextlevel = CONTEXT_SYSTEM;
        $adminrow->component = 'admin';
        $adminrow->count = $admin_cnt;
        $results[] = $adminrow;

        foreach ($results as $key => $result) {
            if ($result->component == '') {
                $result->component = 'manual';
            }
            $result->contextlevel = get_contextlevel_name($result->contextlevel);
        }

        $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
        }
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
        //$result->delete = html_writer::link(new moodle_url('/admin/user.php',
        //    array('delete' => $result->id)), 'Delete');

        $result->view = html_writer::link(new moodle_url('/user/view.php',
            array('id' => $result->id)), 'View');

        $results[$k] = $result;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}

$consoles->push_console_html('users', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "recentlysentgrades";
$sectionhtml = '';

if ($displayforms) {

    $sectionhtml = get_term_selector($title);
    $sectionhtml .= get_subject_area_selector($title);

    $sectionhtml = supportconsole_simple_form($title, $input_html);
} else if ($consolecommand == "$title") {

    // get optional filters
    $term = optional_param('term', null, PARAM_ALPHANUM);
    if (!ucla_validator('term', $term)) {
        $term = null;
    }
    $subjarea = optional_param('subjarea', null, PARAM_NOTAGS);

    //List of gradebook related actions for the log table.
    $actions = array(get_string('gradesuccess', 'local_gradebook'), get_string('gradefail', 'local_gradebook'),
                    get_string('itemsuccess', 'local_gradebook'), get_string('itemfail', 'local_gradebook'),
                    get_string('connectionfail', 'local_gradebook'));

    list($in, $params) = $DB->get_in_or_equal($actions);
    $wheresql = 'l.action ' . $in;
    
    $sql = "SELECT DISTINCT l.id AS logid, from_unixtime(l.time) as time, c.shortname AS course, c.id AS courseid, l.userid, l.module, l.action, l.info
            FROM {log} l
            JOIN {course} c ON l.course = c.id
            JOIN {ucla_request_classes} urc ON c.id = urc.courseid
            WHERE $wheresql";

    // handle term/subject area filter
    if (!empty($term) && !empty($subjarea)) {
        $sql .= " AND
                  urc.term='$term' AND
                  urc.department='$subjarea'";
    } else if (!empty($term)) {
        $sql .= " AND
                  urc.term='$term'";
    } else if (!empty($subjarea)) {
        $sql .= " AND
                  urc.department='$subjarea'";
    }
    $sql .= " ORDER BY time DESC
              LIMIT 100";   //Prints from newest to oldest and limits to 100 results.

    $results = $DB->get_records_sql($sql, $params);
    foreach ($results as $k => $result) {
        $result->course = html_writer::link(new moodle_url('/course/view.php',
            array('id' => $result->courseid)), $result->course,
                array('target' => '_blank'));
        unset($result->courseid);
        $results[$k] = $result;
    }

    $sectionhtml .= supportconsole_render_section_shortcut($title, $results);
}

$consoles->push_console_html('logs', $title, $sectionhtml);

////////////////////////////////////////////////////////////////////
$title = "pushgrades";
$sectionhtml = '';
if ($displayforms) {
    $sectionhtml = supportconsole_simple_form($title, 
        html_writer::label('Moodle course.id', 'gradepush-courseid')
            . html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'length' => 10,
                    'id' => 'gradepush-courseid',
                    'name' => 'courseid'
                )));
} else if ($consolecommand == "$title") { 
    $sectionhtml = '';

    $courseid =  required_param('courseid', PARAM_INT);
    $output = null;
    
    exec("php $CFG->dirroot/local/gradebook/cli/grade_push.php $courseid", $output);

    echo html_writer::tag('h1', get_string('pushgrades', 'tool_uclasupportconsole'));
    echo "<pre>";
    echo implode("\n", $output);
    echo "</pre>";
    
    $consoles->no_finish = true;
}

$consoles->push_console_html('users', $title, $sectionhtml);

// see if user came from a specific page, if so, then direct them back there
$gobackurl = $PAGE->url;
if (!empty($_SERVER['HTTP_REFERER'])) {
    // make sure link came from same server
     if (strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot) !== false) {
         $gobackurl = $_SERVER['HTTP_REFERER'];
     }
}

if (isset($consoles->no_finish)) {
    echo html_writer::link(new moodle_url($gobackurl),
            get_string('goback', 'tool_uclasupportconsole'));
    die();
}

echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclasupportconsole'), 2, 'headingblock');

if (!$displayforms) {
    echo html_writer::link(new moodle_url($gobackurl), 
            get_string('goback', 'tool_uclasupportconsole'));
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
