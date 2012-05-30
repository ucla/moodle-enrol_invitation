<?php

define('CLI_SCRIPT', true);

require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->libdir . '/clilib.php');

list($ext_argv, $unrecog) = cli_get_params(
    array(
        'all' => false,
        'future' => false,
        'help' => false,
        'course-id' => false
    ),
    array(
        'h' => 'help',
    )
);

$is_courseid = false;
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

$results = null;
if (isset($singlecourseid)) {
    $results = ucla_group_manager::sync_course($singlecourseid);
} else {

}
