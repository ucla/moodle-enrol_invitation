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

$string['pluginname'] = 'UCLA configurations';

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

$string['choosecopyright'] = 'Copyright status <a title="Help with Copyright Status" href="'.$CFG->wwwroot.'/help.php?component=block_ucla_easyupload&identifier=license&lang=en" target=_blank><img class="iconhelp" alt="Help with Copyright Status" src="'.$CFG->wwwroot.'/theme/image.php?theme=uclashared&image=help">';
