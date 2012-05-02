<?php

/**
 * lang strings
 * 
 * @package siteindicator 
 */

$string['plugintitle'] = 'UCLA Site Indicator';
$string['description'] = 'Description';
$string['pluginname'] = 'Site Indicator';
$string['type'] = 'Type';
$string['roles'] = 'Roles';
$string['site'] = 'site';

$string['site_instruction'] = 'Instruction (with Intructor roles)';
$string['site_non_instruction'] = 'Non-Instruction (with Project roles)';
$string['site_research'] = 'Research (with Project roles)';
$string['site_test'] = 'Test (experimental)';
$string['site_srs'] = 'Instruction site (listed at Registrar)';

$string['req_desc'] = 'Type of site you are requesting';
$string['req_type'] = 'Site type';
$string['req_type_help'] = 'The site type is used to determine what 
    site roles will be enabled.  ';
$string['req_category'] = 'Site category';
$string['req_category_help'] = 'The site category will help determine where your 
    site will be placed.  It also helps to determine the appropriate 
    support contact that will be responsible for creating your site.';

$string['req_contacts'] = 'Support Contact';
$string['req_selopt_other'] = 'Other (specify and provide reason)';
$string['req_category_other'] = 'Other category';
$string['req_category_other_help'] = 'If you select "other," you will have to specify the 
    categorywhere your course best belongs.  Use existing categories when possible.';

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

$string['reject_header'] = 'Reject Course without a message';
$string['reject_label'] = 'You can reject a course without sending a message.  Doing 
    this will also remove the course from the pending list.';
$string['course_rejected'] = 'Course has been rejected.';

$string['uclasiteindicator:edit'] = 'This is the role description for edit';
$string['uclasiteindicator:view'] = 'This is the role description for view';
