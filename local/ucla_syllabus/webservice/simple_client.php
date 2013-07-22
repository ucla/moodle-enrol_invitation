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

// Disable for production environment.
if ($CFG->forced_plugin_settings['theme_uclashared']['running_environment'] === 'prod') {
    die();
}

global $DB, $CFG;

// Get server name.
$servername = $_SERVER['SERVER_NAME'];

// Get expected parameters.
$token = optional_param('token', '', PARAM_RAW);
$term = optional_param('term', '', PARAM_ALPHANUM);
$srs = optional_param('srs', 0, PARAM_INT);
$url = optional_param('url', '', PARAM_URL);
$filename = optional_param('file_name_real', '', PARAM_RAW);
$delete = optional_param('deleted', false, PARAM_BOOL);

// Decode file size.
function human_filesize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

// Super secrete token.
$mytoken = '123abc';
$self = parse_url($CFG->wwwroot);

// Make sure we have a token, and make sure that request came from our server.
if (!empty($token) && $servername == $self['host']) {
    $dtoken = hash_hmac('sha256', base64_encode($mytoken), $mytoken);

    if ($dtoken == base64_decode($token)) {
        $filesize = empty($filename) ? '' : filesize($_FILES['file']['tmp_name']);
        $action = empty($filename) ? 'course' : 'syllabus';

        if ($delete) {
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

        if ($DB->insert_record('ucla_syllabus_client', (object)$sdata)) {
            echo "Success";
        }
    }
}
