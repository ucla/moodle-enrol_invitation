<?php

/**
 * lang strings
 * 
 * @package siteindicator 
 */

$string['plugintitle'] = 'UCLA Site Indicator';
$string['description'] = 'Description';
$string['pluginname'] = 'Site Indicator';

$string['site_instruction'] = 'Instruction (with Intructor roles)';
$string['site_non_instruction'] = 'Non-Instruction (with Project roles)';
$string['site_research'] = 'Research (with Project roles)';
$string['site_test'] = 'Test (experimental)';

$string['req_desc'] = 'Type of site you are requesting';
$string['req_type'] = 'Site type';
$string['req_type_help'] = 'This is a help string';
$string['req_category'] = 'Site category';
$string['req_category_help'] = 'Site category help string';
$string['req_contacts'] = 'Support Contact';
$string['req_selopt_other'] = 'Other (specify in: Reasons for wanting this course)';
$string['req_category_other'] = 'Other category';
$string['req_category_other_help'] = 'If you select Other, you will have to specify the 
    categorywhere your course best belongs.  Use existing categories when possible.';

$string['jira_title'] = 'Collaboration course request: {$a->fullname}';
$string['jira_msg'] = 'This is a DEV test message 
The following course has been requested by {$a->user}:
Fullname: {$a->fullname}
Shortname: {$a->shortname}

Summary:
{$a->summary}

Reason: 
{$a->reason}

Approve: {$a->approve}
Reject: {$a->reject}
View other pending courses: {$a->pending}';

$string['reject_header'] = 'Reject Course without a message';
$string['reject_label'] = 'You can reject a course without sending a message.  Doing 
    this will also remove the course from the pending list.';
$string['course_rejected'] = 'Course has been rejected.';

$string['uclasiteindicator:edit'] = 'This is the role description for edit';
$string['uclasiteindicator:view'] = 'This is the role description for view';