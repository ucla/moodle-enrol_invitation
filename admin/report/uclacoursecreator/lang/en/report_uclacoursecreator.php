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

$string['uclacoursecreator'] = 'Course creator';
$string['cli_helpmsg'] = 
"USAGE: cli_autocreate.php ([TERM] ([TERM] ... ))
This script will build courses in the terms specified in course requestor.

You can specify as many terms as you would like.

Other options:

-c, --category:
    Auto create division and subject area categories. If this option is disabled, the behavior will follow whatever has been specified in the IMS Enterprise configuration.

-d, --debug:
    Force debug mode. Emails are not send, URLs are not updated, and at the end of each term, an exception is thrown, forcing each term to fail. See reverting cron job.

--current-term:
    Run for the term that is specified in the configuration as the current term.

-h, --help:
    Show this message.

-r, --revert:
    This will enable reverting of failed built courses. Whenever the course creator decides that a term built failed, instead of leaving the courses in the Moodle DB, it will attempt to delete them.

-u, --unlock-first
    Attempt to remove a lock that may have been placed by another failed course creator run.

  Written by SSC - CCLE - UCLA\n";

$string['current_term_not_set'] = '$CFG->currentterm is not set!';

$string['cron_quit_out'] = 'This is most likely a moodle cron instance... Quitting...';
