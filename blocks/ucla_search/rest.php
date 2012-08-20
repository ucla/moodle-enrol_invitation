<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

define('SEARCH_MAX_LIMIT', 20);     // Maximum number of results (arbritrary)
define('SEARCH_SUMMARY_LEN', 75);   // Max length of summary text (arbitrary)

$url = $CFG->wwwroot . '/course/view.php?id=';  // Base course URL

$query = required_param('q', PARAM_TEXT);
$collab = optional_param('collab', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$limit = optional_param('limit', SEARCH_MAX_LIMIT, PARAM_INT);

// Ripped right out of the default search...
$search = trim(strip_tags($query)); // trim & clean raw searched string
if ($search) {
    $searchterms = explode(" ", $search);    // Search for words independently
    foreach ($searchterms as $key => $searchterm) {
        if (strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        }
    }
    $search = trim(implode(" ", $searchterms));
}

// Limit the amount of results we can get
$limit = $limit > SEARCH_MAX_LIMIT ? SEARCH_MAX_LIMIT : $limit;

// Create collab site part of query...
$collab_join = 'LEFT JOIN {ucla_siteindicator} AS si ON si.courseid = c.id ';
$collab_and = '';
$collab_select = ', si.courseid as collabcheck';

if(empty($course) && !empty($collab)) {
    // Search only collab sites

    $collab_join = 'JOIN {ucla_siteindicator} si ON c.id = si.courseid';
    $collab_and = "AND si.type NOT LIKE 'test'";
    $collab_select = ', si.courseid as collabcheck';
    
} else if (!empty($course) && empty($collab)) {
    // Search only course sites
    $collab_join = 'LEFT JOIN {ucla_siteindicator} AS si ON si.courseid = c.id ';
    $collab_and = 'AND si.id IS NULL';
    $collab_select = ', si.courseid as collabcheck';
    
}

////////////////////////////////////////////////////////////////////////////////
// Modified copy/paste search code from datalib.php: 
// 
// get_courses_search(...)
// 
// This updates the query to only search for 'visible' coures
// Also adds a conditions to filter by collaboration sites

if ($DB->sql_regex_supported()) {
    $REGEXP    = $DB->sql_regex(true);
    $NOTREGEXP = $DB->sql_regex(false);
}

$searchcond = array();
$params     = array();
$i = 0;

// Thanks Oracle for your non-ansi concat and type limits in coalesce. MDL-29912
if ($DB->get_dbfamily() == 'oracle') {
    $concat = $DB->sql_concat('c.summary', "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
} else {
    $concat = $DB->sql_concat("COALESCE(c.summary, '". $DB->sql_empty() ."')", "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
}

foreach ($searchterms as $searchterm) {
    $i++;

    $NOT = false; /// Initially we aren't going to perform NOT LIKE searches, only MSSQL and Oracle
            /// will use it to simulate the "-" operator with LIKE clause

    /// Under Oracle and MSSQL, trim the + and - operators and perform
    /// simpler LIKE (or NOT LIKE) queries
    if (!$DB->sql_regex_supported()) {
        if (substr($searchterm, 0, 1) == '-') {
            $NOT = true;
        }
        $searchterm = trim($searchterm, '+-');
    }

    // TODO: +- may not work for non latin languages

    if (substr($searchterm,0,1) == '+') {
        $searchterm = trim($searchterm, '+-');
        $searchterm = preg_quote($searchterm, '|');
        $searchcond[] = "$concat $REGEXP :ss$i";
        $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

    } else if (substr($searchterm,0,1) == "-") {
        $searchterm = trim($searchterm, '+-');
        $searchterm = preg_quote($searchterm, '|');
        $searchcond[] = "$concat $NOTREGEXP :ss$i";
        $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

    } else {
        $searchcond[] = $DB->sql_like($concat,":ss$i", false, true, $NOT);
        $params['ss'.$i] = "%$searchterm%";
    }
}
    
$courses = array();

if (!empty($searchcond)) {

    $searchcond = implode(" AND ", $searchcond);

    // Allow for an extra result..
    $limit_extra = $limit + 1;

    // Modified to search visible courses & collab sites
    list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
    $sql = "SELECT c.* $ccselect $collab_select 
            FROM {course} c
        $collab_join 
        $ccjoin
            WHERE $searchcond AND c.id <> ".SITEID." 
            $collab_and 
        ORDER BY fullname ASC 
            LIMIT $limit_extra";

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $course) {
        context_instance_preload($course);
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            $courses[$course->id] = $course;
            
//            print_object($course);
        }
    }
    $rs->close();
}

$totalcount = count($courses);

// Format results
$results = array();

if(!empty($courses)) {

    // Collect courses
    foreach($courses as $course) {
        $item = new stdClass();
        $item->shortname = $course->shortname;
        $item->fullname = $course->fullname;
        
        // Only display summary for collab sites
        if(empty($course->collabcheck)) {
            $item->summary = '';
        } else {
            $item->summary = $course->summary;

            // Clean up summary
            if(!empty($course->summary)) {
                $su = strip_tags($course->summary);
                $su = substr($su, 0, SEARCH_SUMMARY_LEN);
                $item->summary = $su;

                if(strlen($course->summary) > SEARCH_SUMMARY_LEN) {
                    $item->summary .= '...';
                }
            }
        }
        
        // For highlit results
        $item->text = $course->fullname;
        // For url click
        $item->url = $url . $course->id;

        $results[] = $item;
    }

    // Replace the last result with 'show more results...' text
    if($totalcount > $limit) {

        $results[$limit]->shortname = '';
        $results[$limit]->text = get_string('more_results', 'block_ucla_search');
        $results[$limit]->summary = '';
        $results[$limit]->url = $CFG->wwwroot . '/course/search.php?search=' . $search;
    }
    
} else {
    // We have no results.. 
    $item = new stdClass();
    $item->shortname = '';
    $item->text = get_string('no_results', 'block_ucla_search');
    $item->summary = '';
    $item->url = '#';
    
    $results[] = $item;
}

// Adjust total count
$totalcount = ($totalcount > $limit) ? $limit : $totalcount;

// Format output
$out = new stdClass();

$out->query = $search;
$out->results = $results;
$out->numresults = $totalcount;

// Return as JSON text
echo json_encode($out);