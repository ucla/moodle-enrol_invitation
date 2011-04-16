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

// Parse the arguments into extended and regular arguments
foreach ($argv as $arg) {
    if (substr($arg, 0, 2) == '--') {
        $ext_argv[$arg] = $arg;
    } else if (substr($arg, 0, 1) == '-') {
        $com_argv[$arg] = $arg;
    } else {
        $reg_argv[] = $arg;
    }
}

// Display help message
if (isset($com_argv['-h']) || isset($ext_argv['--help'])) {
    die (
"USAGE: " . __FILE__ . " ([TERM] ([TERM] ... ))
This script will build courses in the terms specified in course requestor.
You can specify as many terms as you would like.

Other options:

--force
    Ignore the locking mechanism and run course creator.

--debug
    Force debug mode, no emails are sent and URLs are not updated.

--current-term
    Run for the current term, as currently specified in the config file.

-h, --help
    Show this message.

Written by SSC - CCLE - UCLA\n");
}

// Skip the regular first argument, which should be the file itself
array_shift($reg_argv);

// Include the Moodle config
$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 
$config_file = $moodleroot . '/config.php';

// Satisfy Moodle's requirement for running CLI scripts
define('CLI_SCRIPT', TRUE);

require($config_file);
require(dirname(__FILE__) . '/uclacoursecreator.class.php');

$bcc = new uclacoursecreator();

// This may take a while...
@set_time_limit(0);

// This may screw up...
ini_set('display_errors', '1');

// Forcing debugging
if (isset($ext_argv['--debug'])) {
    // TODO Fix this to use Moodle's debugging
    // Turn on Moodle's debug mode
    $debugmode = TRUE;
}

// Figure out which terms to run for
$termlist = NULL;
if (!empty($reg_argv)) {
    $termlist = $reg_argv;
} 

if (isset($ext_argv['--current-term'])) {
    $termlist = array($CFG->currentterm);
}

// Set the terms to be this value
$bcc->set_terms($termlist);

echo "Running cron...\n";
$bcc->cron();

/** End of CLI script **/
