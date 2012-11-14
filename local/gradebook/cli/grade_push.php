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

global $DB;

//require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/gradebook/ucla_grade_grade.php');
require_once($CFG->dirroot . '/local/gradebook/locallib.php');

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
$num_courses = 0;
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
        ++$num_courses;
    }

    foreach ($records as $record) {
        $grade = new ucla_grade_grade(array('courseid' => $courseid, 'itemid' => $record->itemid, 'userid' => $record->userid));

        //I wasn't 100% sure exactly what you wanted me to do for errors, so I just printed out a message if it failed.
        //If grade_reporter is not SUCCESS (i.e. DATABASE_ERROR, BAD_REQUEST, or CONNECTION_ERROR)
        if ($grade->send_to_myucla() != grade_reporter::SUCCESS) {
            echo get_string('gradeconnectionfailinfo', 'local_gradebook', $record->id) . "\n";
        } else {
            ++$num_grades_sent;
        }
    }
}
    
echo sprintf("Processed %d courses and sent %d grades to MyUCLA\n", $num_courses, $num_grades_sent);