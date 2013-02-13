<?php
/**
 * Strings
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

$string['pluginname'] = 'UCLA stats console';
$string['uclastats:view'] = 'View UCLA stats console cached queries';
$string['uclastats:query'] = 'Run UCLA stats console queries';
$string['uclastats:manage'] = 'Manage UCLA stats console cached queries (delete or lock results)';

$string['parameters'] = 'Parameters: {$a}';
$string['lastran'] = 'Last ran by {$a->who} on {$a->when}';

// report strings
$string['index_welcome'] = 'Please select a report.';
$string['report_list'] = 'Report list';
$string['run_report'] = 'Run report';

// parameter strings
$string['term'] = 'Term';
$string['subjarea'] = 'Subject area';

$string['course_id'] = 'Course ID';
$string['course_title'] = 'Course';
$string['course_students'] = 'Enrolled students';
$string['course_instructors'] = 'Instructors (role)';
$string['course_forums'] = 'Forum topics'; 
$string['course_posts'] = 'Forum posts';
$string['course_hits'] = 'Total student views';
$string['course_student_percent'] = 'Students visiting site';
$string['course_files'] = 'File count';
$string['course_size'] = 'Course size';
$string['course_syllabus'] = 'Syllabus';

// cached results strings
$string['cached_results_table'] = 'Cached results';
$string['header_param'] = 'Parameters';
$string['header_results'] = 'Results';
$string['header_lastran'] = 'Last ran';
$string['header_actions'] = 'Actions';
$string['view_results'] = 'View results';

// strings for sites_per_term
$string['sites_per_term'] = 'Sites per term';
$string['sites_per_term_help'] = 'Returns number of Registrar course sites built for given term.';
$string['site_count'] = 'Site count';

// strings for course_modules_used
$string['course_modules_used'] = 'Course modules used by courses';
$string['course_modules_used_help'] = 'Returns name and number of course modules used for given term.';
$string['module'] = 'Activity/Resource module';
$string['count'] = 'Count';

// strings for unique_logins_per_term
$string['unique_logins_per_term'] = 'Unique logins per term';
$string['unique_logins_per_term_help'] = 'Counts the average number of unique ' .
        'logins per day and week for a given term. Then gives the total unique ' .
        'logins for the term. Uses the term start and end date to calculate results';
$string['per_day'] = 'Per day';
$string['per_week'] = 'Per week';
$string['per_term'] = 'Per term';
$string['start_end_times'] = 'Start/End';
$string['unique_logins_per_term_cached_results'] = 'Per day: {$a->day} | Per week: {$a->week} | Per term: {$a->term}';

// strings for subject_area_report
$string['subject_area_report'] = 'Subject area report';
$string['subject_area_report_help'] = 'Report that generates a collection of useful statistics that 
    departments can use. Some statistical statistics include, number of enrolled students, 
    class site hits, and forum activity. Was originally requested by Psychology in CCLE-2673.' . 
    '<br /><br />' . 
    'Includes all courses listed at Registrar for given subject area. If course
     does not have a "Course ID" listed, it was not built on the current server.';

//strings for file_size_report
$string['file_size'] = 'File Size';
$string['file_size_help'] = 'Finds number of files over 1 MB';
$string['file_count'] = 'File Count';

// error strings
$string['nocachedresults'] = 'No cached results found';
$string['invalidterm'] = 'Invalid term';
$string['invalidreport'] = 'Invalid report';
$string['resultnotbelongtoreport'] = 'Requested result does not belong to current report';

// strings for unit testing
$string['param1'] = 'Parameter 1';
$string['param2'] = 'Parameter 2';
$string['result1'] = 'Result 1';
$string['result2'] = 'Result 2';
$string['uclastats_base_mock'] = 'UCLA stats base class';
$string['uclastats_base_mock_help'] = 'Text explaining what report does.';