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

// Settings pages
$string['student_access_week_title'] = 'Student previous term cutoff week';
$string['student_access_week_desc'] = 'The number of week since the beginning of the current quarter in which students are allowed view a previous quarter\'s courses.';

$string['currentterm_title'] = 'Current term';
$string['currentterm_desc'] = 'Determines what value to use for anything that needs to know what the current term is.';

$string['current_week_title'] = 'Current week';
$string['current_week_desc'] = 'Determines what value to use for anything that needs to know what the current week is.';

$string['privileged_roles_title'] = 'Roles that can view old terms';
$string['privileged_roles_desc'] = 'The roles selected here can view old terms.';

$string['currenttermweek_disabled'] = 'The ability to alter current term and current week through this interface has been disabled because those are automatically determined.';

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

// CCLE-2669 - Copyright Modifications
// separate out help icon, because it is used separately in easy upload suite
$string['choosecopyright_helpicon'] = '<a title="Help with Copyright Status" href="'.
        $CFG->wwwroot.'/help.php?component=block_ucla_easyupload&identifier=license&lang=en" target="_blank"><img class="iconhelp" alt="Help with Copyright Status" src="'.
        $CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help"></a>';
$string['choosecopyright'] = 'Copyright status ' . $string['choosecopyright_helpicon'];

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

// START UCLA MOD: CCLE-3028 - Fix nonlogged users redirect on hidden content
// If a user who is not logged in tries to access private course information
$string['notloggedin'] = 'Please login to view this content.';
$string['loginredirect'] = 'Login required';
// END UCLA MOD: CCLE-3028

$string['notice_oldcourse'] = 'You are viewing a course that is no longer in session.';
