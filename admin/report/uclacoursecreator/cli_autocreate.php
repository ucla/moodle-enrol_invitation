#!/bin/env php
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
 *  PHP Version of autocreate.sh
 *  Now fully merged into 1 file, all the functionality of autocreate.
 *
 *  Note: this file will output stuff from IMS enterprise's cron() job,
 *  mainly because the cron job uses mtrace(), which will do a direct fwrite
 *  to STDOUT. So far, I have not seen a way around this. 
 **/

// Display help message

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
    die(get_string('cli_helpmsg', 'report_uclacoursecreator'));
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
        echo get_string('current_term_not_set', 'report_uclacoursemenu') . "\n";
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
