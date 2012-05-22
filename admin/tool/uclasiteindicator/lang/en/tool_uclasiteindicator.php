<?php

/**
 * lang strings
 * 
 * @package siteindicator 
 */

// Plugin
$string['plugintitle'] = 'UCLA site indicator';
$string['description'] = 'Description';
$string['pluginname'] = 'Site indicator';
$string['type'] = 'Type';
$string['roles'] = 'Roles';
$string['site'] = 'site';
$string['del_msg'] = 'Site indicator entry';
$string['change'] = 'Change type';

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
$string['notype'] = 'This site has no association';

// Request
$string['req_desc'] = 'Type of site you are requesting';
$string['req_type'] = 'Site type';
$string['req_type_help'] = 'The site type is used to determine what 
    site roles will be enabled.  ';
$string['req_category'] = 'Site category';
$string['req_category_help'] = 'The site category will help determine where your 
    site will be placed.  It also helps to determine the appropriate 
    support contact that will be responsible for creating your site.';

$string['req_contacts'] = 'Support Contact';
$string['req_selopt_other'] = 'Other (provide reason below)';
$string['req_category_other'] = 'Other category';
$string['req_category_other_help'] = 'If you select "other," you will have to specify the 
    categorywhere your course best belongs.  Use existing categories when possible.';

// Jira
$string['jira_title'] = '{$a->type} site request: {$a->fullname}';
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
$string['reject_header'] = 'Reject course without a message';
$string['reject_label'] = 'You can reject a course without sending a message.  Doing 
    this will also remove the course from the pending list.';
$string['course_rejected'] = 'Course has been rejected.';

// Acess descriptions
$string['uclasiteindicator:edit'] = 'This permission allows you to view and edit 
    site indicator information.';
$string['uclasiteindicator:view'] = 'This permission allows you to view site indicator 
    information.';
