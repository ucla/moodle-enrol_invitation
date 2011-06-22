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

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB;

admin_externalpage_setup('uclacoursecreator');

// Start the run
$pagination = optional_param('startat', 0, PARAM_INT);
$viewnation = optional_param('perpage', 25, PARAM_INT);

// Figure out everything that we need
if (!class_exists('uclacoursecreator')) {
    require(dirname(__FILE__) . '/uclacoursecreator.class.php');
}

$coursecreator = new uclacoursecreator();

// Course requestor stuff wrapper needed
$requests = array();

// Stuff?
$error = '';

try {
    $requests = $DB->get_records_select('ucla_request_classes', 
        "status <> 'done' ", null, '', '*', $pagination, 
        $pagination + $viewnation);
} catch (dml_exception $e) {
    $error = $e->debuginfo;
}

$sql_wheres = array();
$params = array();

$terms_list = array();
foreach ($requests as $request) {
    $terms_list[$request->term] = false;

    if ($request->crosslist != '1') {
        continue;
    }

    $termterm = $request->term;
    $termsrs = $request->srs;

    $where = "term = '$termterm' AND srs = '$termsrs'";

    $sql_wheres[] = $where;

    $params[] = $termterm;
    $params[] = $termsrs;
}

foreach ($terms_list as $term => $bool) {
    $term_check = optional_param('build-' . $term, 'false', PARAM_RAW);

    if ($term_check != 'false') {
        $terms_list[$term] = true;
    }
}


if (!empty($params)) {
    $sql_where = "(" . implode(') OR (', $sql_wheres) . ")";

    $crosslists = $DB->get_records_select('ucla_request_crosslist',
        $sql_where, $params);
}

$reindexed = array();

if (!empty($crosslists)) {
    foreach ($crosslists as $crosslist) {
        $reindexed[$crosslist->srs][$crosslist->term][] = $crosslist;
    }
}

// Requests table
$cc_requests = array();
$headers = array(
    'term', 'srs', 'aliassrs', 'course', 'action', 'status', 
    'mailinst', 'Build?'
);

$cc_requests[] = $headers;

// The terms table
$all_terms = array();
$at_headers = array('term', 'Build?');
$all_terms[] = $at_headers;

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

$term_counter = array();
if (!empty($requests)) {
    // Build initial table
    foreach ($requests as $request) {
        if (is_object($request)) {
            $request = get_object_vars($request);
        }

        $row = $bastard($headers, $request);

        $term = $request['term'];
        if (!isset($term_counter[$term])) {
            $term_counter[$term] = 0;
        }

        $term_counter[$term]++;

        $ckey = $term . '-' . $request['srs'];

        $options = array(
            'type' => 'checkbox',
            'name' => $ckey
        );

        if ($terms_list[$request['term']]) {
            $options['checked'] = '1';
            $options['disabled'] = '1';
        } else {
            $opt = optional_param($ckey, 'false', PARAM_RAW);
            if ($opt != 'false') {
                $options['checked'] = '1';
            }
        }

        $row['Build?'] = html_writer::empty_tag('input', $options);
        
        $cc_requests[] = $row;

        if (isset($reindexed[$request['srs']][$term])) {
            $cl = $reindexed[$request['srs']][$term];

            foreach ($cl as $clist) {
                if (is_object($clist)) {
                    $clist = get_object_vars($clist);
                }

                $row = $bastard($headers, $clist);

                $cc_requests[] = $row;

                $term_counter[$term]++;
            }
        }
    }
}

$tum = new html_table();
$tum->data = array(array('Term', 'Build?', 'Count'));

$opts = array('type' => 'checkbox');
foreach ($terms_list as $term => $bool) {
    $opts['name'] = 'build-' . $term;

    if ($bool) {
        $opts['checked'] = '1';
    }

    $tum->data[$term] = array($term, 
        html_writer::empty_tag('input', $opts));

}

foreach ($term_counter as $term => $counter) {
    $tum->data[$term]['count'] = $counter . ' courses';
}

$rum = new html_table();
$rum->data = $cc_requests;

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo $OUTPUT->heading(
    get_string('uclacoursecreator', 'report_uclacoursecreator')
);

// @todo Enable this whenever you are ready
$error = 'Course Creator GUI Disabled';
if (strlen($error) != 0) {
    echo html_writer::tag('p', $error);
} else {
    // Stolen from admin/report/courseoverview/index.php
    echo html_writer::start_tag('form', array('action' => '.',
            'method' => 'post',
            'id' => 'settingsform'));

    if (!empty($terms_list)) {
        echo html_writer::tag('h3', 'Build Terms');
        echo html_writer::table($tum);
    }

    if (isset($rum)) {
        echo html_writer::tag('h3', 'Build Specific Courses');
        echo html_writer::tag('p', 'If you selected a term above, choices '
            . 'made in this section for that term will have no effect.');
        echo html_writer::table($rum);
        // Make a whole array of other options
    } else {
        echo "No courses in the requstor queue.";
    }

    echo html_writer::start_tag('p');

    $value_str = get_string('create') . ' ' . get_string('courses');
    $submit_btn = array(
        'type' => 'submit',
        'value' => $value_str
    );

    if (!isset($rum)) {
        $submit_btn['disabled'] = '1';
    }

    echo html_writer::empty_tag('input', $submit_btn);

    echo html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'value' => '1',
            'name' => 'run'
        ));

    echo html_writer::end_tag('p');

    echo html_writer::end_tag('form');
}

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
