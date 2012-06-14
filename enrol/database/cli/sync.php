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
 * CLI sync for full external database synchronisation.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * $sudo -u www-data /usr/bin/php /var/www/moodle/enrol/database/cli/sync.php
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    enrol
 * @subpackage database
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 

require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'current-term' => false,
        'course-id' => false,
        'help' => false,
        'verbose' => false
    ),
    array(
        'h' => 'help',
        'v' => 'verbose'
    )
);

if ($options['help']) {
    $help = 
"Execute enrol sync with external database.
The enrol_database plugin must be enabled and properly configured.

If no term is specified it will run for the terms defined in 
get_config('tool_uclacoursecreator', 'terms')

Options:
--current-term        Run for the term specified in \$CFG->currentterm
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php enrol/database/cli/ucla_sync.php ([TERM] ([TERM] ... ))
\$sudo -u www-data /usr/bin/php enrol/database/cli/ucla_sync.php --course-id [COURSEID]
";
    echo $help;
    die;
}

// Figure out non dashed options
$reg_argv = array();

$is_courseid = false;
$singlecourseid = false;

foreach ($argv as $arg) {
    if (strpos($arg, '-') !== false) {
        if ($arg == '--course-id') {
            $is_courseid = true;
        }

        continue;
    }

    if ($is_courseid) {
        $is_courseid = false;
        if (is_numeric($arg)) {
            $singlecourseid = $arg;
        } 

        continue;
    }

    $reg_argv[] = $arg;
}

// If we're doing a course, we don't need to figure out our terms.
if ($singlecourseid !== false) {
    $terms = null;
} else {
    // Figure out terms
    $terms = array();

    // Terms provided in arguments?
    if (count($reg_argv) > 1) {
        $terms = $reg_argv;
    } 

    // Include current term?
    if ($options['current-term']) {
        if (!empty($CFG->currentterm)) {
            $terms = array($CFG->currentterm);
        } else {
            echo "Current term not set.";
        }
    }

    // If use the terms in enrol_database configuration
    if (empty($terms)) {
        $terms = get_active_terms();
    }
}

if (!empty($terms)) {
    foreach ($terms as $key => $term) {
        if (!ucla_validator('term', $term)) {
            unset($terms[$key]);
        }
    }
}

if ($terms !== null && empty($terms)) {
    echo "No terms to run for.\n";
    exit(0);
}

if (!enrol_is_enabled('database')) {
    echo('enrol_database_plugin is disabled, sync is disabled'."\n");
    exit(1);
}

$verbose = !empty($options['verbose']);
$enrol = enrol_get_plugin('database');
$result = 0;

// Note that this function will assume that a $terms parameter === NULL
// means ALL terms, unless singlecourse is NOT NULL
// Not providing an argument will mean all terms.
$result = $result | $enrol->sync_enrolments($verbose, $terms, $singlecourseid);

exit($result);
