<?php

// title string
$string['pluginname'] = "UCLA course requestor";

// courserequestor strings
$string['srserror'] = 'The SRS number must be exactly 9 digits long';

$string['srslookup'] = "SRS number lookup";
$string['registrarclasses'] = "Registrar's schedule of classes";

$string['builddept'] = "Fetch courses from department";
$string['buildcourse'] = "Fetch course";
$string['skipinstructors'] = 'Skip instructor lookup';

$string['fetch'] = 'Fetch courses from Registrar';
$string['views'] = 'View existing requests';

// Status readable strings
$string['build'] = "To be built";
// TODO add more status markers for certain things
$string['failed'] = "Failed creator";
$string['live'] = "Live";

$string['viewcourses'] = "View/Edit existing requests";
$string['noviewcourses'] = "If you were expecting a third form, there are no existing requests, so there is no reason to have the option to view them.";

$string['crosslistnotice'] = "You can add crosslists while these couses are waiting in queue to be built.";

$string['existrow'] = 'Some of the courses that you have requested are already in the queue, or have already been built. To rebuild them, please mark them as deleted and fetch the courses again.';

$string['all_department'] = 'All departments';
$string['all_term'] = 'All terms';
$string['all_action'] = 'All statuses';

$string['noinst'] = 'Not Assigned';

$string['newrequest'] = 'New entry';

$string['submitfetch'] = 'Submit requests';
$string['submitviews'] = 'Save changes';

// Table headers for the requests
$string['id'] = 'Request ID';
$string['term'] = 'Term';
$string['srs'] = 'SRS';
$string['course'] = 'Course';
$string['department'] = 'Department';
$string['instructor'] = 'Instructors';
$string['contact'] = 'Requestor email';
// This is whaaa
$string['crosslist'] = 'Crosslist?';
$string['added_at'] = 'Time requested';
$string['action'] = 'Status';
$string['status'] = 'Condition';
$string['mailinst'] = 'E-Mail instructor';
$string['hidden'] = 'Course built hidden from students';
$string['force_urlupdate'] = 'Overwrite URL at MyUCLA';
$string['force_no_urlupdate'] = 'Do NOT send URL to MyUCLA';
$string['crosslists'] = 'Crosslisted SRSes';

$string['addmorecrosslist'] = 'Add another entry';

$string['selectsrscrosslist'] = "Select SRS below to crosslist.";
$string['uncheckedcrosslist'] = "Please uncheck the SRS you do not want crosslisted";

$string['queuetobebuilt'] = "Courses in queue to be built";
$string['queueempty'] = "The queue is empty. All courses have been built as of now.";

$string['alreadysubmitted'] = "This SRS number has been submitted to create a course. ";
$string['checktermsrs'] = "Cannot find course. Please check the term and SRS again.";
$string['childcourse'] =  " has either been submitted for course creation or is a child course";
$string['duplicatekeys'] = "Duplicate entry. The alias is already inserted.";
$string['checksrsdigits'] = "Please check your SRS input. It has to be a 9 digit numeric value.";
$string['submittedforcrosslist'] = "Submitted for crosslisting";
$string['newaliascrosslist'] = "New aliases submitted for crosslisting with host: ";
$string['crosslistingwith'] = " - submitted for crosslisting with ";
$string['individualorchildcourse'] = " is already submitted individually or as a child course. ";
$string['submittedtobebuilt'] = " submitted to be built ";

$string['delete_successful'] = "Deleted course entry: ";
$string['delete_error'] = "Unable to find course entry to delete: ";

$string['courserequestor:view'] = "View " . $string['pluginname'];

