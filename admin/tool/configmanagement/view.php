<?php
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/moodlelib.php');

if (!is_siteadmin($USER->id)) {
    error(get_string('adminsonlybanner'));
}

$name = $_GET['name'];
$name = required_param('name', PARAM_RAW);

$dir = $CFG->dataroot.'/configmanagement/';
header("Content-disposition: attachment; filename=$name");
header('Content-type: text/plain');
readfile("$dir$name");
?>