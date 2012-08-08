<?php
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
require_once($CFG->dirroot . $thisdir . 'lib.php');

// Get param
$query = required_param('q', PARAM_TEXT);

echo siteindicator_manager::get_query_result_json($query);
