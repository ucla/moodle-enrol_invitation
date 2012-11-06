<?php

/**
 * lang strings
 * 
 * @package copyrightstatusreports
 */

// Plugin
$string['description'] = 'Description';
$string['pluginname'] = 'UCLA copyright status reports';

// admin tool reports
$string['all_by_course_current_term'] = 'All items for current term listed by course';
$string['all_by_instructor_current_term'] = 'All items for current term listed by instructor (across all their courses)';
$string['all_by_course_subj_current_term'] = 'All items for current term listed by subject area';
$string['all_by_course_ccle_current_term'] = 'All items for current term within CCLE';

$string['all_by_course'] = 'All items listed by course';
$string['all_by_instructor'] = 'All items listed by instructor (across all their courses)';
$string['all_by_division'] = 'All items listed by course group by division (D) and subject area (S) expressed in aggregate numbers for each status';
$string['all_by_course_subj'] = 'All items listed by course group by subject area (which can be expressed in aggregate numbers for each status)';
$string['all_ccle'] = 'Details of all items within CCLE (for all terms)';
$string['all_by_quarter_year'] = 'Details of all items by quarter and year';
$string['all_filter'] = 'Customize reports';
$string['no_all_by_course'] = 'No course listed';
$string['no_all_by_instructor'] = 'No instructor listed';
$string['no_file'] = 'No file listed for this course';
$string['detail_copyright'] = 'Detail copyright status for the course files';
$string['list_by_course_term'] = '{$a->term}';
$string['ccle'] = 'CCLE';
$string['allterm'] = 'all terms';
$string['list_course_by_term'] = 'List course by';

// content
$string['class_name'] = "Course";
$string['class_shortdesc'] = "Description";
$string['total_files'] = "Total files";
$string['instructor_name'] = "Instructor";
$string['file_name'] = 'File name';

// button
$string['submit_button'] = 'submit';

// license list
$string['iown'] = 'I own';
$string['ucown']='Regents';
$string['lib']='Library';
$string['public1'] = 'PD';
$string['cc1'] = 'CC';
$string['obtained']='PERM';
$string['fairuse']= 'Fair';
$string['tbd'] = 'TBD';
$string['iown_help'] = '<a title="'.get_string('iown', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['ucown_help'] = '<a title="'.get_string('ucown', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['lib_help'] = '<a title="'.get_string('lib', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['public1_help'] = '<a title="'.get_string('public1', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['cc1_help'] = '<a title="'.get_string('cc1', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['obtained_help'] = '<a title="'.get_string('obtained', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['fairuse_help'] = '<a title="'.get_string('fairuse', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['tbd_help'] = '<a title="'.get_string('tbd', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';

$string['reports_intro'] = 'Please choose a report type to view:';
$string['back'] = 'Back';
$string['mainmenu'] = 'Main menu';
$string['filterpage'] = 'Filter report';
$string['courselist'] = 'Course list';

// capability strings
$string['uclacopyrightstatusreports:view'] = 'View copyright status reports';
$string['uclacopyrightstatusreports:edit'] = 'Edit copyright status reports';