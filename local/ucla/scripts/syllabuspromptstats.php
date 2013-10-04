<?php
/**
 * Script to get usage stats of the syllabus reminder prompt.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');

/*
 * Results will be binned by term.
 */
$results = array();

// Get all entries in mdl_user_preferences for the syllabus prompt.
$sql = "SELECT  up.*
        FROM    {user_preferences} up
        JOIN    {role_assignments} ra ON (
                ra.userid=up.userid
        )
        JOIN    {role} r ON (
                ra.roleid=r.id
        )
        WHERE   up.name LIKE 'ucla_syllabus_noprompt_%' AND
                r.shortname='editinginstructor'";
$rs = $DB->get_recordset_sql($sql);

if ($rs->valid()) {
    foreach ($rs as $record) {
        // Get courseid from record and find what term course belongs to.
        $parts = explode('_', $record->name);
        $courseid = array_pop($parts);
        $term = $DB->get_field('ucla_request_classes', 'term', array('courseid' => $courseid), IGNORE_MULTIPLE);
        if (empty($term)) {
            // Does not belong to Registrar course, so ignore it.
        } else {
            if (!isset($results[$term])) {
                $results[$term] = array('never' => 0, 'later' => 0);
            }
            if ($record->value == 0) {
                ++$results[$term]['never'];
            } else {
                ++$results[$term]['later'];
            }
        }
    }
}

print_r($results);