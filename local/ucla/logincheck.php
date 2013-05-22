<?php
/**
 * Checks if user's session is still active
 */

require_once(dirname(__FILE__) . '/../../config.php');

$obj = new stdClass();
$obj->status = false;

if(isloggedin() && !isguestuser()) {
    global $USER;

    $obj->status = true;
    $obj->sesskey = $USER->sesskey;
    $obj->userid = $USER->id;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($obj);