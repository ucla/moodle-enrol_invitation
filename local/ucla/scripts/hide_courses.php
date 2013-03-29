<?php
/*
 * Command line script to manually hide courses and related TA sites for a given
 * term.
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__))));
require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($options['help']) {
    $help =
"Command line script to manually hide courses and related TA sites for a given
 term.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php enrol/database/cli/ucla_sync.php [TERM]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}
$term = $unrecognized[0];

echo "Hiding courses for term: " . $term . "\n";

list($num_hidden_courses, $num_hidden_tasites, $num_problem_courses,
        $error_messages) = hide_courses($term);

echo sprintf("Hid %d courses.\n", $num_hidden_courses);
echo sprintf("Hid %d TA sites.\n", $num_hidden_tasites);
echo sprintf("Had %d problem courses.\n", $num_problem_courses);
echo $error_messages;
die("\nDONE!\n");
