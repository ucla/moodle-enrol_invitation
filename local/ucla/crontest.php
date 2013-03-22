<?php
/*
 * Command line script to run UCLA cron for a given term or all terms.
 *
 * The UCLA cron populates the reg-class-info, the subject area and the
 * division tables.
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(__FILE__)));
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
"Command line script to populate the regclassinfo, subject area, and divsion
    tables for a given term or all terms.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/crontest [TERM (optional)]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (!empty($unrecognized) && !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}

if (empty($unrecognized)) {
    // run ucla cron for every term in ucla_request_classes
    $terms = $DB->get_records_menu('ucla_request_classes', null, '', 'id, term');
    $terms = array_unique((array) $terms);
} else {
    $terms = array($unrecognized[0]);
}

local_ucla_cron($terms);
