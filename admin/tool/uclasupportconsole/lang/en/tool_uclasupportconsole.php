<?php
$string['pluginname'] = 'UCLA support console';

$string['notitledesc'] = '(no description)';

// Titles
$string['logs'] = 'Log tools';
$string['users'] = 'User tools';
$string['srdb'] = 'Registrar tools';
$string['modules'] = 'Module tools';

// System logs
$string['syslogs'] = 'View last 1000 lines of a log';
$string['syslogs_info'] = 'If a selection is disabled, then the corresponding log file was not found.';
$string['syslogs_select'] = 'Select a log file';
$string['syslogs_choose'] = 'Choose log...';
$string['log_apache_error'] = 'Apache error';
$string['log_apache_access'] = 'Apache access';
$string['log_apache_ssl_access'] = 'Apache SSL access';
$string['log_apache_ssl_error'] = 'Apache SSL error';
$string['log_apache_ssl_request'] = 'Apache SSL request';
$string['log_shibboleth_shibd'] = 'Shibboleth daemon';
$string['log_shibboleth_trans'] = 'Shibboleth transaction';
$string['log_moodle_cron'] = 'Moodle cron';
$string['log_course_creator'] = 'Course creator';
$string['log_prepop'] = 'Pre-pop';

// Other logs
$string['prepopfiles'] = 'Show pre-pop files';
$string['prepopview'] = 'Show latest pre-pop output';
$string['prepoprun'] = 'Run prepop for one course';
$string['moodlelog'] = 'Show last 100 log entries';
$string['moodlelog_select'] = 'Select which types of log entries to view';
$string['moodlelog_filter'] = 'Filter log by action types';
$string['moodlelogins'] = 'Show logins during the last 24 hours';
$string['moodlelogbyday'] = 'Count Moodle logs by day';
$string['moodlelogbydaycourse'] = 'Count Moodle logs by day and course (past 7 days, limited to top 100 results)';
$string['moodlelogbydaycourseuser'] = 'Count Moodle logs by day, course and user (past 7 days, limited to top 100 results)';
$string['moodlevideofurnacelist'] = 'Video furnace';
$string['moodlelibraryreserveslist'] = 'Library reserves';
$string['moodlebruincastlist'] = 'Bruincast';
$string['sourcefile'] = 'Data source: {$a}';
$string['recentlysentgrades'] = 'Show 100 most recent MyUCLA grade log entries';

// Users
$string['moodleusernamesearch'] = 'Show users with firstname and/or lastname';
$string['roleassignments'] = 'View role assignments';
$string['userswithrole'] = 'Users with the given role assignment';
$string['viewrole'] = 'View 1 role assignment';
$string['viewroles'] = 'View {$a} role assignments';
$string['countnewusers'] = 'Show most recently created users';
$string['pushgrades'] = 'Manually push grades to MyUCLA';
$string['noenrollments'] = 'There are no enrollments';
$string['usersdescription'] ='Users with role: {$a->role}, Context: {$a->contextlevel} and Component: {$a->component}';

// The SRDB
$string['enrollview'] = 'Get courses for view enrollment (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3318">enroll2</a>)';

// For each stored procedure, the name is dynamically generated.
// The item itself will be there when the SP-object is coded, but there
// will be no explanation unless the code here is changed (or the SRDB
// layer is altered to include descriptions within the object).
$string['ccle_coursegetall'] = 'Get all courses in a subject area for BrowseBy (CCLE <a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3305">ccle_coursegetall</a>)';
$string['ccle_courseinstructorsget'] = 'Get instructors for course (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3306">ccle_courseinstructorsget</a>)';
$string['ccle_getclasses'] = 'Get information about course (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3308">ccle_getclasses</a>)';
$string['ccle_getinstrinfo'] = 'Get all instructors in a subject area (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3309">ccle_getinstrinfo</a>)';
$string['ccle_roster_class'] = 'Get student roster for class (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3310">ccle_roster_class</a>)';
$string['cis_coursegetall'] = 'Get all courses in a subject area  (CIS <a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3311">cis_coursegetall</a>)';
$string['cis_subjectareagetall'] = 'Get all subject area codes and full names (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3313">cis_subjectareagetall</a>)';
$string['ucla_getterms'] = 'Get terms information (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3315">ucla_getterms</a>)';
$string['ucla_get_user_classes'] = 'Get courses for My sites (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=16788">ucla_get_user_classes</a>)';
$string['ccle_class_sections'] = 'Get course sections (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=3304">ccle_class_sections</a>)';
$string['ccle_get_primary_srs'] = 'Get primary course srs for given discussion srs (<a target="_blank" href="https://ccle.ucla.edu/mod/page/view.php?id=37526">ccle_get_primary_srs</a>)';

$string['unknownstoredprocparam'] = 'This stored procedure has a unknown parameter type. This needs to be changed in code.';

$string['courseregistrardifferences'] = 'Show courses with changed descriptions';

// Module
$string['nosyllabuscourses'] = 'Show courses with no syllabus';
$string['assignmentquizzesduesoon'] = 'Show courses with assignments or quizzes due soon';
$string['modulespercourse'] = 'Count module totals and module types per course';
$string['syllabusreoport'] = 'Syllabus report';
$string['syllabus_header_course'] = '{$a->term} Course ({$a->num_courses})';
$string['syllabus_header_instructor'] = 'Instructors';
$string['syllabus_header_public'] = 'Public ({$a})';
$string['syllabus_header_private'] = 'Private ({$a})';
$string['syllabus_header_manual'] = 'Manual ({$a})';
$string['syllabusoverview'] = 'Syllabus overview';
$string['syllabus_browseby'] = 'Browse by';
$string['syllabus_division'] = 'Division';
$string['syllabus_subjarea'] = 'Subject area';
$string['syllabus_count'] = 'Syllabus/Courses<br />{$a}';
$string['syllabus_ugrad_table'] = 'Undergraduate courses';
$string['syllabus_grad_table'] = 'Graduate courses';
$string['public_syllabus_count'] = 'Public<br />{$a}';
$string['loggedin_syllabus_count'] = 'UCLA community<br />{$a}';
$string['preview_syllabus_count'] = 'Preview<br />{$a}';
$string['private_syllabus_count'] = 'Private<br />{$a}';
$string['manual_syllabus_count'] = 'Manual<br />{$a}';
$string['syllabustimerange'] = 'Displaying uploaded syllabi';
$string['nocourses'] = 'No courses found.';
$string['syllabusoverviewnotes'] = 'Does not include cancelled or tutorial courses. ' .
        'The preview syllabus percentage is counted against the total number of "Public" and "UCLA community" syllabi. ' .
        'A manual syllabus is always counted, but only increments the total number of syllabi if no other syllabus type is found.';
$string['syllabusreoportnotes'] = 'Does not include cancelled or tutorial courses. The manual syllabus column counts the number of manual syllabi in a course.';
$string['mediausage'] = 'Media usage';
$string['mediausage_help'] = 'Lists course video content over specified size for a given term.';

// Course
$string['collablist'] = 'Show collaboration sites';

// Capability string
$string['tool/uclasupportconsole:view'] = 'Access UCLA support console';

// Form input strings
$string['choose_term'] = 'Choose term...';
$string['term'] = 'Term';
$string['srs'] = 'SRS';
$string['subject_area'] = 'Subject area';
$string['choose_subject_area'] = 'Choose subject area...';
$string['uid'] = 'UID';

$string['srslookup'] = "SRS number lookup (Registrar)";

$string['goback'] = 'Go back';

// capability strings
$string['uclasupportconsole:view'] = 'Use UCLA support console';
