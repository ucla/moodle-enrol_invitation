<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB;

admin_externalpage_setup('uclacoursecreator');

// Start the run
$run = optional_param('run', 0, PARAM_INT);

// Figure out everything that we need
if (!class_exists('uclacoursecreator')) {
    require(dirname(__FILE__) . '/uclacoursecreator.class.php');
}

$coursecreator = new uclacoursecreator();

// Course requestor stuff wrapper needed
$terms = $DB->get_records_select('ucla_request_classes', "status <> 'done' ",
    null, '', '*', 0, 50);

$sql_wheres = array();
$params = array();
foreach ($terms as $term) {
    $termterm = $term->term;
    $termsrs = $term->srs;

    $where = "term = '$termterm' AND srs = '$termsrs'";

    $sql_wheres[] = $where;

    $params[] = $termterm;
    $params[] = $termsrs;
}

// What a boob, moob, noob (babe-boob, man-boob, nun-boob).
$sql_where = "(" . implode(') OR (', $sql_wheres) . ")";

$crosslists = $DB->get_records_select('ucla_request_crosslist',
    $sql_where, $params);

$reindexed = array();
foreach ($crosslists as $crosslist) {
    $reindexed[$crosslist->srs][$crosslist->term][] = $crosslist;
}

$master = array();

$headers = array(
    'term', 'srs', 'aliassrs', 'course', 'action', 'status', 'mailinst'
);

$master[] = $headers;

// Avoiding renaming things
$bastard = function($headers, $term) {
    $row = array();

    foreach ($headers as $field) {
        $row[$field] = '';
        if (isset($term[$field])) {
            $row[$field] = $term[$field];
        }
    }

    return $row;
};

if (!empty($terms)) {
    foreach ($terms as $term) {
        if (is_object($term)) {
            $term = get_object_vars($term);
        }

        $row = $bastard($headers, $term);
        $master[] = $row;

        if (isset($reindexed[$term['srs']][$term['term']])) {
            $cl = $reindexed[$term['srs']][$term['term']];

            foreach ($cl as $clist) {
                if (is_object($clist)) {
                    $clist = get_object_vars($clist);
                }

                $row = $bastard($headers, $clist);

                $master[] = $row;
            }
        }
    }
}

$rum = new html_table();
$rum->data = $master;

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo $OUTPUT->heading(
    get_string('uclacoursecreator', 'report_uclacoursecreator')
);

// @todo Add a button to launch course creator
// Stolen from admin/report/courseoverview/index.php
echo '<form action="." method="post" id="settingsform">' . "\n";

echo html_writer::table($rum);
echo '<p>';
echo '<input type="submit" value="' . get_string('create') . ' ' 
     . get_string('courses') . '" />' . "\n";

echo '<input type="hidden" value="1" name="run">' . "\n";

echo '</p>';

echo '</form>' . "\n";


echo $OUTPUT->box_end();

echo $OUTPUT->footer();
