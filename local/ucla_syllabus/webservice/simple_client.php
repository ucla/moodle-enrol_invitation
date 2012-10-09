<?php

require_once(dirname(__FILE__) . '/../../../config.php');

global $DB, $CFG;

// Get server name
$servername = $_SERVER['SERVER_NAME'];

// Expected params
$token = optional_param('token', '', PARAM_RAW);
$term = optional_param('term', '', PARAM_ALPHANUM);
$srs = optional_param('srs', 0, PARAM_INT);
$url = optional_param('url', '', PARAM_URL);
$filename = optional_param('file_name_real', '', PARAM_RAW);
$delete = optional_param('deleted', 0, PARAM_BOOL);

// Decode filsize
function human_filesize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

// Super secrete token
$mytoken = '123abc';
$self = parse_url($CFG->wwwroot);

// Make sure we have a token, and make sure that request came from our server
if(!empty($token) && $servername == $self['host']) {
    $dtoken = hash_hmac('sha256', base64_encode($mytoken), $mytoken);
    
    if($dtoken == base64_decode($token)) {
        $filesize = empty($filename) ? '' : filesize($_FILES['file']['tmp_name']);
        $action = empty($filename) ? 'course' : 'syllabus';
        
        if($delete) {
            $action = 'syllabus delete';
        }
        
        $data = array(
            'action' => $action,
            'token' => $mytoken,
            'term' => $term,
            'srs' => $srs,
            'url' => $url,
            'filename' => $filename,
            'filesize' => human_filesize($filesize),
            'timestamp' => time(),
        );

        $sdata = array('data' => serialize($data));

        $DB->insert_record('ucla_syllabus_client', (object)$sdata);
    }
}