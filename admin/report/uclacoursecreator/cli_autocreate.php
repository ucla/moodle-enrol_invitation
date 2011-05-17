#!/bin/env php
<?php
/**
 *  PHP Version of autocreate.sh
 *  Now fully merged into 1 file, all the functionality of autocreate.
 *
 *  Note: this file will output stuff from IMS enterprise's cron() job,
 *  mainly because the cron job uses mtrace(), which will do a direct fwrite
 *  to STDOUT. So far, I have not seen a way around this. 
 **/

// Display help message

define ('HELP_STRING', 
"USAGE: " . __FILE__ . " ([TERM] ([TERM] ... ))
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
    Show a help message.

-r, --revert:
    This will enable reverting of failed built courses. Whenever the course creator decides that a term built failed, instead of leaving the courses in the Moodle DB, it will attempt to delete them.

-u, --unlock-first
    Attempt to remove a lock that may have been placed by another failed course creator run.

  Written by SSC - CCLE - UCLA
");

// Include the Moodle config
$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 
$config_file = $moodleroot . '/config.php';

// Satisfy Moodle's requirement for running CLI scripts
define('CLI_SCRIPT', true);

require($config_file);
require(dirname(__FILE__) . '/uclacoursecreator.class.php');
global $CFG;

require($CFG->libdir . '/clilib.php');

list($ext_argv, $unrecog) = cli_get_params(
    array(
        'unlock-first' => false,
        'debug' => false,
        'current-term' => false,
        'help' => false,
        'revert' => false,
        'category' => false
    ),
    array(
        'u' => 'unlock-first',
        'd' => 'debug',
        'h' => 'help',
        'r' => 'revert',
        'c' => 'category'
    )
);

if ($ext_argv['help']) {
    die(HELP_STRING);
}

$goals = array();
$cur_term = false;

$reg_argv = array();
foreach ($argv as $arg) {
    if (strpos($arg, '-') !== false) {
        continue;
    }

    if (strlen($arg) == 3) {
        // If we have processed up to another TERM argument,
        // and we have no SRS requested within that TERM
        $reg_argv[] = $arg;
    }
}

$bcc = new uclacoursecreator();

// This may take a while...
@set_time_limit(0);

// This may screw up...
ini_set('display_errors', '1');

if (!empty($goals)) {
    $bcc->set_srs_list($goals);
}

// Forcing debugging
if ($ext_argv['debug']) {
    $bcc->set_debug(true);
}

// Figure out which terms to run for
$termlist = NULL;
if (!empty($reg_argv)) {
    $termlist = $reg_argv;
} 

if ($ext_argv['current-term']) {
    if (!isset($CFG->currentterm)) {
        $termlist = array($CFG->currentterm);
    } else {
        echo '$CFG->currentterm is not set!' . "\n";
    }
}

// Force a run, try unlocking first
if ($ext_argv['unlock-first']) {
    $bcc->handle_locking(false, false);
}

// Force revertings
if ($ext_argv['revert']) {
    // Temporary change
    $CFG->course_creator_revert_failed_cron = true;
}

// Categories
if ($ext_argv['category']) {
    $CFG->course_creator_division_categories = true;
}

// Set the terms to be this value
$bcc->set_terms($termlist);

$bcc->cron();

/** End of CLI script **/
