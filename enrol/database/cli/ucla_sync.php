<?php

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 

require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');


list($ext_argv, $unrecog) = cli_get_params(
    array(
        'current-term' => false,
        'help' => false,
        'verbose' => false
    ),
    array(
        'h' => 'help',
        'v' => 'verbose'
    )
);

if ($ext_argv['help']) {
    echo "Execute enrol sync with external database.
The enrol_database plugin must be enabled and properly configured.

If no term is specified it will run for the terms defined in 
get_config('tool_uclacoursecreator', 'terms')

Options:
--current-term        Run for the term specified in \$CFG->currentterm
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php enrol/database/cli/ucla_sync.php ([TERM] ([TERM] ... ))
";
    exit(0);
}

// Figure out non dashed options
$reg_argv = array();
foreach ($argv as $arg) {
    if (strpos($arg, '-') !== false) {
        continue;
    }

    if (ucla_validator('term', $arg)) {
        // If we have processed up to another TERM argument,
        // and we have no SRS requested within that TERM
        $reg_argv[] = $arg;
    }
}

// Figure out which terms to run for
$terms = array();
if (!empty($reg_argv)) {
    $terms = $reg_argv;
} 

if ($ext_argv['current-term']) {
    if (!empty($CFG->currentterm)) {
        $terms = array($CFG->currentterm);
    } else {
        echo "Current term not set.";
    }
}

// if no other terms given, then use course creator's terms, if any
if (empty($terms)) {
    $terms = get_config('tool_uclacoursecreator', 'terms');
}

if (empty($terms)) {
    $conf_terms = get_config('enrol_database', 'terms');
    if ($conf_terms) {
        if (is_array($conf_terms)) {
            $terms = $conf_terms;
        } else {
            $dirtyterms = explode(',', $conf_terms);
            foreach ($dirtyterms as $dirtyterm) {
                $terms[] = trim($dirtyterm);
            }
        }

        if (!empty($terms)) {
            foreach ($terms as $key => $term) {
                if (!ucla_validator('term', $term)) {
                    unset($terms[$key]);
                }
            }
        }
    }
}

if (empty($terms)) {
    echo "No terms to run for.\n";
    exit(0);
}

$verbose = !empty($ext_argv['verbose']);
$enrol = enrol_get_plugin('database');

// Note that this function will assume that a $terms parameter === NULL
// means ALL terms.
// Not providing an argument will mean all terms.
$result = $enrol->sync_enrolments($verbose, $terms);

exit($result);
