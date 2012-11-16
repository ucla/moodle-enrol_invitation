<?php
/*
 * CCLE-2362
 *
 * This script manually pushes the grade items for either all pf the courses in
 * a given term or for a specified course given the course-id.
 *
 *
 * Usage: php grade_push.php <term or course-id>
 *
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/gradebook/locallib.php');

require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->libdir . '/grade/grade_category.php');

// Needs one argument, term or courseid
if ($argc != 2) {
    exit('Usage: php grade_push.php <term or course-id>' . "\n");
}

$courses = array();
if (ucla_validator('term', $argv[1])) {
    //Checks if paramater is a term
    $term = $argv[1];
    $results = ucla_get_courses_by_terms($argv[1]);
    $courses = array_keys($results);
} else if ($result = ucla_map_courseid_to_termsrses($argv[1])) {
    // Checks if parameter is a courseid
    $courses[] = $argv[1];
} else {
    exit('ERROR: Invalid term or course-id did not belong to a SRS course' . "\n");    
}

$num_grades_sent = 0;
$num_grade_items_sent = 0;
$num_courses = 0;
$pushed_grade_items = array();
$skipped_grade_items = array();
foreach ($courses as $courseid) {
    $sql = "SELECT gg.id, gg.userid, gg.itemid
            FROM {course} as c
            JOIN {grade_items} AS gi ON gi.courseid = c.id
            JOIN {grade_grades} AS gg ON gg.itemid = gi.id
            WHERE c.id = :courseid";
    $records = $DB->get_records_sql($sql, array('courseid' => $courseid));

    if (empty($records)) {
        echo sprintf("NOTICE: no grades found for courseid %d; skipping\n", $courseid);
    } else {
        echo sprintf("Processing courseid %d\n", $courseid);
        ++$num_courses;
    }

    foreach ($records as $record) {
        $params = array('courseid' => $courseid, 'itemid' => $record->itemid,
            'userid' => $record->userid, 'id' => $record->id);

        $grade = new ucla_grade_grade($params);
        $grade->load_grade_item();
        $grade_item = $grade->grade_item;

        // see if we need to push a grade item
        if (!in_array($grade_item->id, $pushed_grade_items)) {
            // we didn't push it yet, so push it
            unset($params['itemid']);
            unset($params['userid']);
            $params['id'] = $record->itemid;
            $ucla_grade_item = new ucla_grade_item($params);

            $result = $ucla_grade_item->send_to_myucla();
            if ($result == grade_reporter::NOTSENT) {
                // skip sending grades for this item
                $skipped_grade_items[] = $grade_item->id;
                echo sprintf("Skipping sending grade item %d\n", $grade_item->id);
                continue;
            } else if ($ucla_grade_item->send_to_myucla() != grade_reporter::SUCCESS) {
                echo get_string('gradeconnectionfailinfo', 'local_gradebook', $record->itemid) . "\n";
            } else {
                echo sprintf("Sent grade item %d\n", $record->itemid);
                $pushed_grade_items[] = $grade_item->id;
                ++$num_grade_items_sent;
            }
        }

        $result = $grade->send_to_myucla();
        if ($result == grade_reporter::NOTSENT) {
            // user shouldn't have had their grade sent, skip them
            echo sprintf("Skipping sending grade %d for userid %d\n", $record->id, $record->userid);
            continue;
        } else if ($result != grade_reporter::SUCCESS) {
            echo get_string('gradeconnectionfailinfo', 'local_gradebook', $record->id) . "\n";
        } else {
            echo sprintf("Sent grade %d for userid %d\n", $record->id, $record->userid);
            ++$num_grades_sent;
        }
    }
}
    
echo sprintf("Processed %d courses, sent %d grade items and %d grades to MyUCLA\n",
        $num_courses, $num_grade_items_sent, $num_grades_sent);