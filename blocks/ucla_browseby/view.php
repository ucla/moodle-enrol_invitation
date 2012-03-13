<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/block_ucla_browseby.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');

$type = required_param('type', PARAM_TEXT);
$term = optional_param('term', $CFG->currentterm, PARAM_TEXT);

$argvls = array('term' => $term, 'type' => $type);

$browseby = block_instance('ucla_browseby');

// Iterate through all possible arguments in this display
$args = $browseby->get_possible_arguments();

foreach ($args as $arg) {
    ${$arg} = optional_param($arg, null, PARAM_RAW);
    if (${$arg} !== null) {
        $argvls[$arg] = ${$arg};
    }
}

$PAGE->set_url('/blocks/ucla_browseby/view.php', $argvls);

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('frontpage');

list($title, $innercontents) = $browseby->handle_types($type, $argvls);
if (!$title) {
    print_error('illegaltype', 'block_ucla_browseby', '', $type);
}

$PAGE->set_title($title);

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo html_writer::tag('div', $innercontents, array('id' => 'browsebymain'));

echo $OUTPUT->footer();
