<?php

// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// Simple syllabus client example


// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// Include the library file
include 'client.php';

// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// Receive expected payload
$payload = syllabus_ws_client::get_payload();


// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// If you're expecting a file..
$path = '.';

if(!empty($payload)) {
    syllabus_ws_client::save_file($payload, $path);
}


// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// For testing purposes only, we'll write the contents of the payload into a
// file called 'temp.txt'... This is to verify that we receieved something.
$obj = serialize($payload);
$handle = fopen('temp.txt', 'w+');
fwrite($handle, $obj);
fclose($handle);

// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// Return a status message, this is what our service expects
if(!empty($payload)) {
    $msg = array(
        'status' => syllabus_ws_client::STATUS_OK
    );
} else {
    $msg = array(
        'status' => syllabus_ws_client::STATUS_FAIL
    );
}

echo json_encode($msg);