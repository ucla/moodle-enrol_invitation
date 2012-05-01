<?php
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/moodlelib.php');

$name = $_GET['name'];
$name = required_param('name', PARAM_RAW);

$dir = $CFG->dataroot.'/config_management/';
header("Content-disposition: attachment; filename=$name");
header('Content-type: text/plain');
readfile("$dir$name");
?>