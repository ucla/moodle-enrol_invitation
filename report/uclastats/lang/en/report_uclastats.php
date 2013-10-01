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
$string['warning_high_load'] = 'WARNING: Report may take a long time to run. ' .
        'Please run new reports during off peak hours. Viewing cached results is fine.';
$string['export_options'] = 'Export: ';

// parameter strings
$string['noparams'] = 'No additional parameters needed to run report';
$string['term'] = 'Term';
$string['subjarea'] = 'Subject area';
$string['threshold'] = 'Threshold';

// cached results strings
$string['cached_results_table'] = 'Cached results';
$string['header_param'] = 'Parameters';
$string['header_results'] = 'Results';
$string['header_lastran'] = 'Last ran';
$string['header_actions'] = 'Actions';
$string['view_results'] = 'View results';
$string['lock_results'] = 'Lock';
$string['locked_results'] = 'Locked';
$string['unlock_results'] = 'Unlock';
$string['delete_results'] = 'Delete';
$string['successful_delete'] = 'Successfully deleted result';
$string['successful_unlock'] = 'Successfully unlocked result';
$string['successful_lock'] = 'Successfully locked result';
$string['error_delete_locked'] = 'Cannot delete locked results';
$string['undefined_action'] = 'The requested action , {$a} , is undefined';
$string['confirm_delete'] = 'Are you sure you want to delete the result?';

// strings for sites_per_term
$string['sites_per_term'] = 'Sites per term (course)';
$string['sites_per_term_help'] = 'Returns number of Registrar course sites built for a given term.';
$string['site_count'] = 'Site count';

// strings for course_modules_used
$string['course_modules_used'] = 'Activity/Resource modules (course)';
$string['course_modules_used_help'] = 'Returns name and number of course modules used by courses sites for a given term.';
$string['module'] = 'Activity/Resource module';
$string['count'] = 'Count';

// strings for collab_modules_used
$string['collab_modules_used'] = 'Activity/Resource modules (collab)';
$string['collab_modules_used_help'] = 'Returns name and number of collab modules used by collab sites. Excludes "test" sites.';

// strings for unique_logins_per_term
$string['unique_logins_per_term'] = 'Unique logins per term (system)';
$string['unique_logins_per_term_help'] = 'Counts the average number of unique ' .
        'logins per day and week for a given term. Then gives the total unique ' .
        'logins for the term. Uses the term start and end date to calculate results. ' .
        'Also reports total number of users for the given term.';
$string['per_day'] = 'Per day';
$string['per_week'] = 'Per week';
$string['per_term'] = 'Per term';
$string['start_end_times'] = 'Start/End';
$string['unique_logins_per_term_cached_results'] = 'Per day: {$a->day} | Per week: {$a->week} | Per term: {$a->term} | Total users: {$a->total_users}';
$string['total_users'] = 'Total Users';

// strings for subject_area_report
$string['subject_area_report'] = 'Subject area report (course)';
$string['subject_area_report_help'] = 'Report that generates a collection of useful statistics that 
    departments can use. Some statistical statistics include, number of enrolled students, 
    class site hits, and forum activity. Was originally requested by Psychology in CCLE-2673.';
$string['course_id'] = 'Course ID';
$string['course_title'] = 'Course';
$string['course_students'] = 'Enrolled students';
$string['course_instructors'] = 'Instructors (role)';
$string['course_forums'] = 'Forum topics'; 
$string['course_posts'] = 'Forum posts';
$string['course_hits'] = 'Total student views';
$string['course_student_percent'] = 'Students visiting site';
$string['course_files'] = 'Resource files';
$string['course_size'] = 'Resource file size (MB)';
$string['course_syllabus'] = 'Syllabus';


//strings for system_size_report
$string['system_size'] = 'System size (system)';
$string['system_size_help'] = 'Returns number of files over 1 MB and ' .
                              'size of database';
$string['file_count'] = 'Number of files over 1 MB';
$string['database_size'] = 'Size of database';


//strings for collab_num_sites
$string['collab_num_sites'] = 'Num sites (collab)';
$string['collab_num_sites_help'] = 'Returns count of total, active, and inactive collab sites. ' .
        'Inactivity is based on if a site has not had a single page view for 6 months. ' .
        'Does not count guest user access. Includes test sites.';
$string['total_count'] = 'Total';
$string['active_count'] = 'Active';
$string['inactive_count'] = 'Inactive';
$string['num_sites_cached_results'] = 'Total: {$a->total} | Active: {$a->active} | Inactive: {$a->inactive}';

//strings for course_num_sites
$string['course_num_sites'] = 'Num sites (course)';
$string['course_num_sites_help'] = 'Reports count of total, active, and inactive course sites. ' .
        'Inactivity is based on if a course has not had any log hits 1 week after ' .
        'the start of the term. Handles the different starting times for summer ' .
        'sessions. Does not count guest user access.';
$string['division'] = 'Division';

//strings for role_count
$string['role_count'] = 'Role count (course)';
$string['role_count_help'] = 'Returns the total for each role for all courses for a given term';
$string['role'] = 'Role';

//string for course_block_sites
$string['course_block_sites'] = 'Blocks (course)';
$string['course_block_sites_help'] = 'Returns name and number of blocks used by course sites for a given term.';
$string['blockname'] = 'Block name';

//string for collab_block_sites
$string['collab_block_sites'] = 'Blocks (collab)';
$string['collab_block_sites_help'] = 'Returns the name and number of blocks used by collab sites.';

//strings for custom_theme_report
$string['custom_theme'] = 'Custom theme report (system)';
$string['custom_theme_help'] = 'Displays sites that are using a custom theme.';
$string['theme_count'] = 'Number of sites using custom theme: ';
$string['course_shortname'] = 'Course';
$string['course_title'] = 'Course title';
$string['theme'] = 'Theme';

//strings for repository usage report
$string['repository_usage'] = 'Repository usage (system)';
$string['repository_usage_help'] = 'Find repository usage for: Dropbox, Google, Box, Server files, and My CCLE files.';
$string['repo_name'] = 'Repository';
$string['repo_count'] = 'File count';

//strings for large courses report
$string['large_courses'] = 'Large sites (course)';
$string['large_courses_help'] = 'For a given term, list all the courses over {$a}.';
$string['other'] = 'Other';
$string['video'] = 'Video';
$string['audio'] = 'Audio';
$string['image'] = 'Image';
$string['web_file'] = 'Web file';
$string['spreadsheet'] = 'Spreadsheet';
$string['document'] = 'Document';
$string['archive'] = 'Archive';
$string['presentation'] = 'Presentation';

//strings for large collab sites report
$string['large_collab_sites'] = 'Large sites (collab)';
$string['large_collab_sites_help'] = 'List all the collaboration sites over {$a}.';

//strings for final quiz report
$string['final_quiz_report'] = 'Final quiz report (course)';
$string['final_quiz_report_help'] = 'Displays the number of quizzes taken during 10th week and Finals week by division.<br/> ' .
                                    '<strong>Regular:</strong> 10th week: Sat-Friday before Finals, Finals: Sat-Friday of end of term.<br/>' .
                                    '<strong>Summer:</strong> 10th: Sat-Thursday of end of term, Finals: Friday of end of term';
$string['last_week_count'] = 'Last Week';
$string['final_count'] = 'Finals';

//strings for most active course site report
$string['most_active_course_sites'] = 'Most active (course)';
$string['most_active_course_sites_help'] = 'Most active course site is one that has the most views.';
$string['viewcount'] = 'Number of Views';

//strings for most active collab site report
$string['most_active_collab_sites'] = 'Most active (collab)';
$string['most_active_collab_sites_help'] =  'Most active collab site is one that has the most views.';

//strings for total downloads
$string['total_downloads'] = 'Total downloads (course)';
$string['total_downloads_help'] = 'For a given term, get a count of all total downloads.';

//strings for forum usage
$string['collab_forum_usage'] = 'Forum usage (collab)';
$string['collab_forum_usage_help'] = 'Forum usage by average number of posters, average number of threads';
$string['course_forum_usage'] = 'Forum usage (course)';
$string['course_forum_usage_help'] = $string['collab_forum_usage_help'];
$string['avg_num_threads'] = 'Threads per Forum';
$string['avg_num_posters'] = 'Posters per Forum';
$string['forum_usage_cached_results'] = 'Avg Posters: {$a->avg_num_posters} | '.
                                        'Avg Threads: {$a->avg_num_threads}';

//string for users by division
$string['users_by_division'] = 'Users by division (course)';
$string['users_by_division_help'] = 'Counts the number of hits, users, ' .
        'ratio of (hits/users) for a division by term, ' .
        'and number of users of the entire system by term';
$string['hits'] = 'Hits';
$string['ratio_hits_users'] = 'Hits to Users';

// Strings for gradebook usage.
$string['gradebook_usage'] = 'Gradebook usage';
$string['gradebook_usage_help'] = 'Counts the number of courses that have used
    the gradebook in the following ways:
    <ol>
        <li>Has graded grade items.</li>
        <li>Has overridden a grade item or grade category.</li>
        <li>Exported grades to one of the export formats.</li>
    </ol>';
$string['gradeditems'] = 'Has graded items';
$string['overriddengrades'] = 'Has overridden grades';
$string['exportedgrades'] = 'Exported grades';
$string['usedgradebook'] = 'Total courses using gradebook';
$string['totalcourses'] = 'Total courses';
                                   
// Strings for Course activity (Instructor focused)
$string['active_instructor_focused'] = 'Course activity (Instructor focused)';
$string['active_instructor_focused_help'] = 'A course is active ' . 
        'if it has visible content added to it beyond the normal course shell. ' .
        'This includes adding a block, module, or posting in the ' .
        'default forums. This does not include uploading a syllabus.';
$string['numactive'] = 'Active';
$string['numinactive'] = 'Inactive';
$string['totalcourses'] = 'Total courses';

// Strings for Course activity (Student focused)
$string['active_student_focused'] = 'Course activity (Student focused)';
//$string['active_student_focused_help'] = 'A course is active ' .
//        'if it has at least 80% of its enrolled students ' .
//        'viewing the course at least once during the term.';
$string['active_student_focused_help'] = 'A course is active ' .
        'if it has student log entries/number of enrolled students > 0.80';

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
