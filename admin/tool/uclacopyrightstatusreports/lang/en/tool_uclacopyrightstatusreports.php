<?php

/**
 * lang strings
 * 
 * @package copyrightstatusreports
 */

// Plugin
$string['description'] = 'Description';
$string['pluginname'] = 'UCLA copyright status reports';
$string['type'] = 'Site type';
$string['roles'] = 'Assignable roles';
$string['site'] = 'site';
$string['del_msg'] = 'Site indicator entry';
$string['change'] = 'Change site type';
$string['sitecreate'] = 'Create copyright status reports';
$string['no_indicator_type'] = 'Please choose a site type below';

// Site role groups
$string['r_project'] = 'Project';
$string['r_instruction'] = 'Instruction';
$string['r_test'] = 'Test';

// Site descriptions
$string['site_instruction'] = 'Instruction';
$string['site_instruction_desc'] = 'An instruction site that is not listed at 
    the registrar.';
$string['site_non_instruction'] = 'Non-Instruction';
$string['site_non_instruction_desc'] = 'A collaboration site.';
$string['site_research'] = 'Research';
$string['site_research_desc'] = 'A research collaboration site';
$string['site_test'] = 'Test';
$string['site_test_desc'] = 'A temporary test site.';
$string['site_registrar'] = 'Instruction (listed at Registrar)';
$string['site_registrar_desc'] = 'An instruction site with an SRS number that is listed at the registrar';
$string['notype'] = 'This site has no type';

// Request
$string['req_desc'] = 'Type of site you are requesting';
$string['req_type'] = 'Site type';
$string['req_type_help'] = 'The site type is used to determine what 
    site roles will be enabled.';
$string['req_type_error'] = 'Please choose a site type';
$string['req_category'] = 'Site category';
$string['req_category_help'] = 'The site category will help determine where your 
    site will be placed.  It also helps to determine the appropriate 
    support contact that will be responsible for creating your site.';
$string['req_category_error'] = 'Please choose a category';

// Pending
$string['sitetype'] = 'Site type';
$string['sitecat'] = 'Requested category';

$string['req_contacts'] = 'Support Contact';
$string['req_selopt_other'] = 'Other (provide reason below)';
$string['req_selopt_choose'] = 'Choose a category...';
$string['req_category_other'] = 'Other category';
$string['req_category_other_help'] = 'If you select "other," you will have to specify the 
    category where your course best belongs.  Use existing categories when possible.';

// Jira
$string['jira_title'] = '{$a->type} collab site request: {$a->fullname}';
$string['jira_msg'] = 'The following collaboration site has been requested by: {$a->user}

Type: {$a->type}
Category: {$a->category}
Fullname: {$a->fullname}
Shortname: {$a->shortname}

Summary:
* {$a->summary}

Reason: 
* {$a->reason}

Approve or reject course: 
{$a->action}';

// Reject
$string['reject_yesno'] = 'Send an email to the user who requested the site?';

// Acess descriptions
$string['uclasiteindicator:edit'] = 'Edit site indicator information.';
$string['uclasiteindicator:view'] = 'View site indicator information.';

// Override default lang string: course -> site
// Pending
$string['coursespending'] = 'Pending collaboration sites requests';
$string['nopendingcourses'] = 'There are no collaboration site requests pending approval';
$string['shortnamecourse'] = 'Site short name';
$string['fullnamecourse'] = 'Site full name';
$string['requestreason'] = 'Reason for the site request';
$string['backtocourselisting'] = 'Back to My home';

// Request
$string['courserequest'] = 'Collaboration site request';
$string['courserequestsuccess'] = 'Your collaboration site request has been submitted. Please 
    expect a response from your local support person regarding approval of 
    your site request within a few days';
$string['courserequestdetails'] = 'Details of the site you are requesting';
$string['courserequestreason'] = 'Reasons for wanting this site';
$string['fullnamecourse'] = 'Site full name';
$string['fullnamecourse_help'] = 'The full name of the site is displayed at the 
    top of each page.';
$string['shortnamecourse'] = 'Site short name';
$string['shortnamecourse_help'] = 'The short name of the site is displayed in 
    the navigation and is used in the subject line of course email messages.  
    This shortname is also used for your site\'s url.  Example:  
    www.ccle.ucla.edu/course/short-name';

$string['coursesummary_help'] = 'The site summary is displayed in the list of courses. A site 
    search searches the summary text in addition to the site names.';
$string['coursesummary'] = 'Site summary';

$string['coursespending'] = 'Sites pending approval (for managers only)';
$string['requestcourse'] = 'Request a collaboration site';

// Reject
$string['coursereasonforrejecting'] = 'Reject collaboration site request';
$string['courserejected'] = 'Site has been rejected';

// admin tool reports
$string['all_by_course'] = 'All items for current term list by course';
$string['all_by_instructor'] = 'All items for current term list by instructor (across all their courses)';
$string['all_by_division'] = 'All items for current term list by division(which can be expressed in aggregate numbers for each status)';
$string['all_by_course_subj'] = 'All items for current term list by course group by subject area (which can be expressed in aggregate numbers for each status)';
$string['all_by_course_div'] = 'All items for current term list by course group by division and subject area (which can be expressed in aggregate numbers for each status)';
$string['all_ccle'] = 'Details of all items within CCLE';
$string['all_by_quarter'] = 'Details of all items by quarter';
$string['all_by_academic_yr'] = 'Details of all items by academic year';
$string['all_filter'] = 'Customize reports';
$string['no_all_by_course'] = 'No course listed';
$string['no_all_by_instructor'] = 'No instructor listed';
$string['no_file'] = 'No file listed for this course';
$string['detail_copyright'] = 'Detail copyright status for the course files';

// content
$string['class_name'] = "Course";
$string['class_shortdesc'] = "Description";
$string['total_files'] = "Total files";
$string['instructor_name'] = "Instructor";
$string['file_name'] = 'File name';
$string['subj_text'] = 'SUBTOTAL FOR subject area ';
$string['div_text'] = 'SUBTOTAL FOR division ';



// button
$string['instr_button'] = 'submit';
$string['course_button'] = 'submit';


// license list
$string['iown'] = 'I own';
$string['ucown']='The UC Regents own';
$string['lib']='Licensed by the UCLA Library';
$string['public1'] = 'Public domain';
$string['cc1'] = 'Creative Commons license';
$string['obtained']='Written permission';
$string['fairuse']= 'Fair use';
$string['tbd'] = 'TBD';
$string['iown_help'] = '<a title="'.get_string('iown', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['ucown_help'] = '<a title="'.get_string('ucown', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['lib_help'] = '<a title="'.get_string('lib', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['public1_help'] = '<a title="'.get_string('public1', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['cc1_help'] = '<a title="'.get_string('cc1', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['obtained_help'] = '<a title="'.get_string('obtained', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['fairuse_help'] = '<a title="'.get_string('fairuse', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['tbd_help'] = '<a title="'.get_string('tbd', 'license').'"><img class="iconhelp" alt="Help with Copyright Status" src="'. $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';

$string['reports_heading'] = 'Reports';
$string['reports_intro'] = 'Please choose a report type to view:';
$string['sitetypes'] = 'Site types';
$string['requesthistory'] = 'Request history';
$string['norequesthistory'] = 'No requests found';
$string['site_requester'] = 'Site requester';
$string['site_status'] = 'Site status';
$string['orphans'] = 'Orphan sites (non-SRS sites with no site type)';
$string['noorphans'] = 'No orphan sites found';
$string['sitelisting'] = 'Site listings';
$string['nositelisting'] = 'No sites found';
$string['back'] = 'Back';
$string['mainmenu'] = 'Main menu';
$string['filterpage'] = 'Filter report';
$string['courselist'] = 'Course list';

$string['search_placeholder'] = 'Search for a collaboration site';