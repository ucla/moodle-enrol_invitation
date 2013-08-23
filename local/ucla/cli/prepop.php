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
 * CLI sync for prepop synchronization with Registrar data.
 *
 * Modified from the external database CLI script to add in parameters to run
 * for a given term or specific courseid.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * sudo -u www-data /usr/bin/php /var/www/moodle/local/ucla/cli/prepop.php
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    local_ucla
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'currentterm' => false,
        'courseid' => false,
        'help' => false,
        'verbose' => false
    ),
    array(
        'h' => 'help',
        'v' => 'verbose'
    )
);

$parameterterms = array();
if ($unrecognized) {
    // Maybe someone is passing us terms to run.
    foreach ($unrecognized as $index => $param) {
        if (ucla_validator('term', $param)) {
            $parameterterms[] = $param;
            unset($unrecognized[$index]);
        }
    }

    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if ($options['help']) {
    $help =
"Execute enrol sync with external database.
The enrol_database plugin must be enabled and properly configured.

If no term is specified it will run for the terms defined in
get_config('tool_uclacoursecreator', 'terms')

Options:
--currentterm         Run for the term specified in \$CFG->currentterm
--courseid            Run for courseid specified
-v, --verbose         Print verbose progress information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/ucla_sync.php ([TERM] ([TERM] ... ))
\$sudo -u www-data /usr/bin/php local/ucla/cli/ucla_sync.php --course-id [COURSEID]

Sample cron entry:
# 5 minutes past 4am
5 4 * * * sudo -u www-data /usr/bin/php /var/www/moodle/local/ucla/cli/prepop.php
";

    echo $help;
    die;
}

if (!enrol_is_enabled('database')) {
    cli_error('enrol_database plugin is disabled, synchronization stopped', 2);
}

debugging('currentterm = ' . get_config('', 'currentterm'));
debugging('$CFG->currentterm = ' . $CFG->currentterm);


// Figure out how script was called.
$parameters = array();
if (!empty($options['courseid']) && is_int($options['courseid'])) {
    // Just run pre-pop for a single course.
    $parameters = $options['courseid'];
} else if (!empty($options['currentterm']) && !empty($CFG->currentterm)) {
    // Just run pre-pop for the current term.
    $parameters['terms'] = array($CFG->currentterm);
} else if (!empty($parameterterms)) {
    // Run pre-pop for the given set of terms.
    $parameters['terms'] = $parameterterms;
} else {
    // No parameters set, so just run all active terms.
    $terms = get_active_terms();
    if (empty($terms)) {
        cli_error('No terms to run for.');
    }
    $parameters['terms'] = $terms;
}

print_r($parameters);exit;

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

$enrol = enrol_get_plugin('database');

$result = $enrol->sync_enrolments($trace, $parameters);

exit($result);
