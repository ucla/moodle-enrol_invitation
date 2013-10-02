<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCLA customizations';

$string['access_failure'] = 'Your access control systems are not properly set up, configuration files in the "local/ucla/" directory may be web visible!';

$string['curl_failure'] = 'cURL is not installed, your configuration files\' web visibility could not be tested!';

$string['term'] = 'Term';
$string['invalidrolemapping'] = 'Could not find role mapping {$a}';

$string['ucla:viewall_courselisting'] = 'Allows user to see all courses another user is associated with on their profile';

$string['external-link'] = 'External website (opens new window)';

/* strings for datetimehelpers */

// for distance_of_time_in_words
$string['less_than_x_seconds'] = 'less than {$a} seconds';
$string['half_minute'] = 'half a minute';
$string['less_minute'] = 'less than a minute';
$string['a_minute'] = '1 minute';
$string['x_minutes'] = '{$a} minutes';
$string['about_hour'] = 'about 1 hour';
$string['about_x_hours'] = 'about {$a} hours';
$string['a_day'] = '1 day';
$string['x_days'] = '{$a} days';

// CCLE-3158 - Use UCLA specific lang string for copyright help icon in filepicker
$string['license_help']='This question requires you to declare the copyright
status of the item you are uploading. Each option is explained in greater detail
below.

<strong>I own the copyright.</strong>
<br />
You are an author of this work and have not transferred the rights to a
publisher or any other person.

<strong>The UC Regents own the copyright.</strong>
<br />
This item’s copyright is owned by the University of California Regents; most
items created by UC staff fall into this category.

<strong>Item is licensed by the UCLA Library.</strong>
<br />
This item is made available in electronic form by the UCLA library. <i> Note:
the UCLA Library would prefer that you provide a link to licensed electronic
resources rather than uploading the file to your CCLE course.</i>

<strong>Item is in the public domain.</strong>
<br />
Generally, an item is in the public domain if one of the following applies:
<ol>
    <li>It was published in the U.S. before 1923.</li>
    <li>It is a product of the federal government.</li>
    <li>The term of copyright, which is generally the life of the author plus
    seventy years, has expired.</li>
</ol>

<strong>Item is available for this use via Creative Commons license.</strong>
<br />
Many items are made available through Creative Commons licenses, which specify
how an item may be reused without asking the copyright holder for permission.
Similar “open source” licenses would also fit under this category. See
<a href="http://creativecommons.org/" target="_blank">creativecommons.org</a>
for more information.

<strong>I have obtained written permission from the copyright holder.</strong>
<br />
This answer applies if you have contacted the copyright holder of the work and
have written permission to use the work in this manner.  Note: You should keep
this written permission on file.

<strong>I am using this item under fair use.</strong><br />
Fair use is a right specifically permitting educational, research, and scholarly
uses of copyrighted works.  However, <u>not every educational use is
automatically a fair use</u>; a
<a href="http://copyright.universityofcalifornia.edu/fairuse.html#2" target="_blank">four-factor analysis</a>
must be applied to each item.

<strong>Copyright status not yet identified.</strong>
<br />
Select <strong>only</strong> if this upload is being performed by <u>someone besides the
instructor of record</u> at the instructor’s behest, but the instructor did not
clarify the copyright status.

Note: if you believe none of these answers apply, you should not upload the item.
For more details  on copyright status and fair use, go to the
<a href="http://copyright.universityofcalifornia.edu/fairuse.html" target="_blank">UC copyright fair use page</a>,
use ARL’s <a href="http://www.knowyourcopyrights.org/bm~doc/kycrbrochurebw.pdf" target="_blank">Know Your Copy Rights</a>
brochure, or read their great <a href="http://www.knowyourcopyrights.org/resourcesfac/faq/online.shtml" target="_blank">FAQ</a>.
If you have questions regarding the above or need assistance in determining
copyright status, please email <a href="mailto:copyright@library.ucla.edu">copyright@library.ucla.edu</a>
for a consultation. <strong>It is the instructor of record’s responsibility to
comply with copyright law in the handling of course materials;</strong> see the
<a href="'.$CFG->wwwroot.'/theme/uclashared/view.php?page=copyright">CCLE copyright information page</a>
    for more details.
';

// SSC-1306 - Let instructors know when if the announcements forum is hidden
$string['announcementshidden'] = 'The Announcements forum is currently hidden: Emails will NOT be sent out to students.';
$string['unhidelink'] = 'Click here to unhide';
$string['askinstructortounhide'] = 'Please ask the instructor to unhide this forum.';
// End SSC Modification

// capability strings
$string['ucla:assign_all'] = 'CCLE-2530: Can see the entire user database when assigning roles';
$string['ucla:editadvancedcoursesettings'] = 'CCLE-3278: Can edit the course settings for category, format, maximum upload size, or language defaults';
$string['ucla:deletecoursecontentsandrestore'] = 'CCLE-3446: Can delete course contents when restoring a course';
$string['ucla:editcoursetheme'] = 'CCLE-2315: Can edit the theme a course uses';
$string['ucla:viewotherusers'] = 'CCLE-3584: Can view other users when viewing a course';
$string['ucla:bulk_users'] = 'CCLE-2970: Can perform bulk user actions';
$string['ucla:browsecourses'] = 'CCLE-3773: Gives users link to "Add/edit courses"';

// START UCLA MOD: CCLE-3028 - Fix nonlogged users redirect on hidden content
// If a user who is not logged in tries to access private course information
$string['notloggedin'] = 'Please login to view this content.';
$string['loginredirect'] = 'Login required';
// END UCLA MOD: CCLE-3028

// Strings for notice_course_status.
$string['notice_course_status_paststudent'] = 'You are viewing a site for a course that is no longer in session. Your access will expire at the end of Week 2 of the subsequent term.';
$string['notice_course_status_pastinstructor'] = 'You are viewing a site for a course that is no longer in session. Student access will expire at the end of Week 2 of the subsequent term.';
$string['notice_course_status_hidden'] = 'This site is unavailable.';
$string['notice_course_status_temp'] = 'You have temporary access to this site. Your access will expire after {$a}.';
$string['notice_course_status_pasthidden_tempparticipant'] = 'You are viewing a site for a course that is no longer in session. Student access has expired. Use the <a href="{$a}">Site invitation tool</a>/Temporary Participant role to grant temporary access to this site.';
$string['notice_course_status_pasthidden'] = 'You are viewing a course that is no longer in session. ';
$string['notice_course_status_pasttemp'] = 'You have temporary access to a site for a course that is no longer in session. Your access will expire after {$a}.';
$string['notice_course_status_hiddentemp'] = 'You have temporary access to a site that is currently unavailable. Your access will expire after {$a}.';
$string['notice_course_status_pasthiddentemp'] = 'You have temporary access to a site for a course that is no longer in session. Your access will expire after {$a}.';

$string['lti_warning'] = 'There are risks using external tools. Please read ' . 
        'this help document for more information: ' .
        '<a target="_blank" href="https://docs.ccle.ucla.edu/w/LTI">https://docs.ccle.ucla.edu/w/LTI</a>';

// Settings.
$string['student_access_ends_week'] = 'Prevent student access on week';
$string['student_access_ends_week_description'] = 'When the specified week starts, the system will automatically ' . 
        'hide all courses for previous term. For example, if "3" is given, then when "Week 3" starts for Spring ' .
        'Quarter, then all courses for Winter will be hidden automatically. Also, if set, will prevent "My sites" ' .
        'from listing the previous term\'s courses for students. If set to "0" no courses will be hidden ' .
        'automatically and "My sites" is not restricted.';
$string['coursehidden'] = '<p>This course is unavailable for students. Students ' .
        'can access their course sites from a prior term during the first ' .
        'two weeks of the subsequent term.</p><p>If additional access is ' .
        'needed, students should contact the course instructor. </p>';
$string['overrideenroldatabase'] = 'Override database enrollment plugin';
$string['overrideenroldatabasedesc'] = 'Override the database enrollment plugin to use UCLA specific customizations.';
$string['minuserupdatewaitdays'] = 'User information update delay';
$string['minuserupdatewaitdays_desc'] = 'Number of days since a user last used the site before updating their first name, last name and/or email from the external database.';

// Form submit login check
$string['longincheck_login'] = 'Your session has timed out. In order to save 
         your work login again and save your changes.';
$string['logincheck_idfail'] = 'Your user ID does not match!  This form has been ' .
        'disabled in order to prevent an erroneous submission. ' .
        'Save your work and reload this page.';
$string['logincheck_networkfail'] = 'There was no response from the server. ' .
        'You might be disconnected from your network. Please reconnect and try again.';
$string['logincheck_success'] = 'You\'re logged in.  You can now submit this form.';

// CCLE-3652 - Students unable to see "Submission Grading" link on Assignment module
$string['submissionsgrading'] = 'Grading criteria';

// CCLE-3970 - Install and evaluate LSU's Gradebook Improvements
$string['overridecat'] = 'Allow Grade Override';
$string['overridecat_help'] = 'This option allows users to override the final grade in for category totals. Unchecking this option will make category totals uneditable.';
$string['repeatheaders'] = 'Repeat Headers';
$string['repeatheaders_help'] = 'Repeat gradebook headers after this many rows.';