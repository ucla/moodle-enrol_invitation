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

//strings for role_count
$string['role_count'] = 'Role Count';
$string['role_count_help'] = 'Counts the total for each role for all the courses in the specified term';
$string['role'] = 'Role';
$string['count_for_role'] = 'Count';

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