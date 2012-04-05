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

$string['pluginname'] = 'UCLA course creator';
$string['pluginname2'] = 'Build Course Now';
$string['uclacoursecreator'] = $string['pluginname'];
$string['cli_helpmsg'] = 
"USAGE: cli_autocreate.php ([TERM] ([TERM] ... ))
This script will build courses in the terms specified in course requestor.

You can specify as many terms as you would like.

Other options:

-c, --category:
    Auto create division and subject area categories, then nest the subject area in the division categories. If this option is disabled, then only subject area categories will be created.

--current-term:
    Run for the term that is specified in the configuration as the current term.

-f, fail:
    Used for testing reverting.

-h, --help:
    Show this message.

-m, --mute:
    Disables sending of mails.

-r, --revert:
    Experimental: This will enable reverting of failed built courses. Whenever the course creator decides that a term built failed, instead of leaving the courses in the Moodle DB, it will attempt to delete them.

-u, --unlock-first
    Attempt to remove a lock that may have been placed by another failed course creator run.

  Written by SSC - CCLE - UCLA\n";

$string['current_term_not_set'] = '$CFG->currentterm is not set!';

$string['cron_quit_out'] = 'This is most likely a moodle cron instance... Quitting...';

$string['checklogs'] = 'Please check the logs for more details';
$string['checkterms'] = 'Select the term to build courses';
