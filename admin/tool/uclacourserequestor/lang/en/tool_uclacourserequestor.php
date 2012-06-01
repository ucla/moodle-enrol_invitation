<?php
$string['pluginname'] = "UCLA course requestor";
$string['uclacourserequestor'] = $string['pluginname'];
$string['courserequestor:view'] = "View " . $string['pluginname'];

$string['srslookup'] = "SRS number lookup (Registrar)";

// Fetch from Registrar
$string['fetch'] = 'Get courses from Registrar';
$string['buildcourse'] = "Get course";
$string['builddept'] = "Get department courses";

$string['views'] = 'View existing requests';
$string['viewcourses'] = "View/Edit existing requests";
$string['buildcoursenow'] = "Build courses now";
$string['alreadybuild'] = "Course build in progress";
$string['queuebuild'] = "Course build queued";

// Status readable 
$string['build'] = "To be built";
$string['failed'] = "Failed";
$string['live'] = "Live";

$string['delete'] = 'Delete';

// This string should be rarely used
$string['noviewcourses'] = "There are no existing requests.";

$string['crosslistnotice'] = "You can add crosslists while these couses are waiting in queue to be built.";

$string['error'] = 'Some of the courses that you have requested have problems with them. Please look over them and if needed, submit your requests again.';
$string['warning'] = 'Some of the courses that you have requested have different values than default. Be sure to look over them if this message is unexpected.';

$string['all_department'] = 'All departments';
$string['all_term'] = 'All terms';
$string['all_action'] = 'All statuses';

$string['noinst'] = 'Not Assigned';

$string['newrequestid'] = 'New entry';
$string['newrequestcourseid'] = 'Not built yet';

$string['checkchanges'] = 'Validate requests without saving changes';
$string['submitfetch'] = 'Submit requests';
$string['submitviews'] = 'Save changes';
$string['savefailed'] = 'Unable to save request';

$string['norequestsfound'] = 'No courses found for given request.';

// Table headers for the requests
$string['id'] = 'Request ID';
$string['courseid'] = 'Associated Course ID';
$string['term'] = 'Term';
$string['srs'] = 'SRS';
$string['course'] = 'Course';
$string['department'] = 'Department';
$string['instructor'] = 'Instructors';
$string['requestoremail'] = 'Requestor email';
$string['crosslist'] = 'Crosslist?';
$string['timerequested'] = 'Time requested';
$string['action'] = 'Status';
$string['status'] = 'Condition';
$string['mailinst'] = 'E-Mail instructor';
$string['hidden'] = 'Course built hidden from students';
$string['nourlupdate'] = 'Do NOT send URL to MyUCLA';
$string['crosslists'] = 'Crosslisted SRSes';

$string['deletefetch'] = 'Ignore';
$string['deleteviews'] = 'Remove request';

$string['addmorecrosslist'] = 'Add another entry';

$string['clchange_removed'] = 'Removed crosslist: ';
$string['clchange_added'] = 'Added crosslist: ';

// Crosslisting errors
$string['illegalcrosslist'] = 'This SRS has already been requested to be built';
$string['hostandchild'] = 'One of the crosslisted SRSes has already been built, and is preventing this request from proceding.';
$string['srserror'] = 'The SRS number must be exactly 9 digits long';
$string['cancelledcourse'] = 'This course is marked as cancelled by the Registrar.';
$string['nosrsfound'] = 'Could not find course with this SRS.';

$string['queuetobebuilt'] = "Courses in queue to be built";
$string['queueempty'] = "The queue is empty. All courses have been built as of now.";

$string['alreadysubmitted'] = "This SRS number has already been submitted as a request. ";
$string['checktermsrs'] = "Cannot find course. Please check the term and SRS again.";
$string['childcourse'] =  " has either been submitted for course creation or is a child course";
$string['duplicatekeys'] = "Duplicate entry. The alias is already inserted.";
$string['checksrsdigits'] = "Please check your SRS input. It has to be a 9 digit numeric value.";
$string['submittedforcrosslist'] = "Submitted for crosslisting";
$string['newaliascrosslist'] = "New aliases submitted for crosslisting with host: ";
$string['crosslistingwith'] = " - submitted for crosslisting with ";
$string['individualorchildcourse'] = " is already submitted individually or as a child course. ";
$string['submittedtobebuilt'] = " submitted to be built ";

$string['deletesuccess'] = "Deleted request entry: {\$a}";
$string['deletecoursesuccess'] = "Delete request entry: {\$a}, along with course.";
$string['savesuccess'] = "Updated request entry: {\$a}";
$string['insertsuccess'] = "Inserted request entry: {\$a}";
$string['deletecoursefailed'] = "Unable to find course entry to delete: ";

$string['changedto'] = ' to ';

$string['uclacourserequestor:edit'] = 'Ability to view/use UCLA course requestor';
