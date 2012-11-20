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

// when pushing grades using this script, disable logging successful grade updates
$CFG->gradebook_log_success = 0;

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
foreach ($courses as $courseid) {

    // first get all grading items
    $gradeitems = $DB->get_records('grade_items', array('courseid' => $courseid));

    if (empty($gradeitems)) {
        echo sprintf("NOTICE: no grade items found for courseid %d; skipping\n", $courseid);
    } else {
        echo sprintf("Processing courseid %d\n", $courseid);
        ++$num_courses;
    }

    foreach ($gradeitems as $gradeitem) {
        // first push grade item
        $ucla_grade_item = new ucla_grade_item($gradeitem);
        $result = $ucla_grade_item->send_to_myucla();
        if ($result == grade_reporter::NOTSENT) {
            // skip sending grades for this item
            echo sprintf("Skipping sending grade item %d\n", $gradeitem->id);
            continue;
        } else if ($ucla_grade_item->send_to_myucla() != grade_reporter::SUCCESS) {
            echo get_string('gradeconnectionfailinfo', 'local_gradebook', $gradeitem->id) . "\n";
        } else {
            echo sprintf("Sent grade item %d\n", $gradeitem->id);
            ++$num_grade_items_sent;
        }

        // next, get grades
        $gradegrades = $DB->get_recordset('grade_grades', array('itemid' => $gradeitem->id));

        if (!$gradegrades->valid()) {
            echo sprintf("No grades for grade item %d; skipping\n", $gradeitem->id);
            continue;
        }

        // now push each grade
        foreach ($gradegrades as $gradegrade) {
            $grade = new ucla_grade_grade($gradegrade);

            $result = $grade->send_to_myucla();
            if ($result == grade_reporter::NOTSENT) {
                // user shouldn't have had their grade sent, skip them
                echo sprintf("Skipping sending grade %d for userid %d\n", $gradegrade->id, $gradegrade->userid);
                continue;
            } else if ($result != grade_reporter::SUCCESS) {
                echo get_string('gradeconnectionfailinfo', 'local_gradebook', $gradegrade->id) . "\n";
            } else {
                echo sprintf("Sent grade %d for userid %d\n", $gradegrade->id, $gradegrade->userid);
                ++$num_grades_sent;
            }
        }
    }
}
    
echo sprintf("Processed %d courses, sent %d grade items and %d grades to MyUCLA\n",
        $num_courses, $num_grade_items_sent, $num_grades_sent);