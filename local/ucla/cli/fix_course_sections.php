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

/**
 * CLI script to fix course sections.
 *
 * To run the script, just enter in a courseid as a parameter.
 *
 * @package    local_ucla
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/classes/local_ucla_course_section_fixer.php");
require_once("$CFG->dirroot/local/ucla/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false), array('h' => 'help'));

if ($options['help']) {
    $help =
"Fix course's sections.

Will attempt to fix a course's sections by:
* Making sure that course cache matches database.
* Extra sections above the numsections for a course are deleted.
* Sections are in sequential order.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/fix_course_sections [COURSEID]
";

    echo $help;
    die;
}

$course = null;
if (!empty($unrecognized)) {
    // Check if someone is passing in a valid courseid.
    $courseid = intval(array_pop($unrecognized));
    if ($courseid <= 0) {
        cli_error("Parameter needs to be an integer\n");
    }
    $course = $DB->get_record('course', array('id' => $courseid));
    if (empty($course)) {
        cli_error("Cannot find courseid $courseid\n");
    }
}

if (empty($course)) {
    cli_error("No parameter passed, need courseid to run\n");
}

$trace = new text_progress_trace();

// Run the checker and fixer methods separately because we want to give verbose
// feedback to the user.
$methods = array('extra_sections', 'section_order');

$changesmade = false;
foreach ($methods as $method) {
    $checkmethod = 'check_'.$method;
    $result = local_ucla_course_section_fixer::$checkmethod($course);
    if (!$result) {
        $trace->output("$checkmethod returned problem, attemping to fix it");

        $handlemethod = 'handle_'.$method;
        $retval = local_ucla_course_section_fixer::$handlemethod($course);
        $trace->output(sprintf("Added: %d, Deleted: %d, Updated: %d",
                $retval['added'], $retval['deleted'], $retval['updated']), 1);

        if ($retval['added'] > 0 || $retval['deleted'] > 0 ||
                $retval['updated'] > 0) {
            $changesmade = true;
        }
    }
}

// If any changes were made, then we need to rebuild the course cache.
if ($changesmade) {
    rebuild_course_cache($course->id);
}