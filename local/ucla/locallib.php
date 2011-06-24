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

defined('MOODLE_INTERNAL') || die();

// We should change this out
//require_once($CFG->libdir . '/uclalib.php');

function ucla_verify_configuration_setup() {
   global $CFG;

    if (!function_exists('curl_init')) {
        throw new moodle_exception('curl_failure', 'local_ucla');
    }

    ini_set('display_errors', '1');
    ini_set('error_reporting', E_ALL);

    $ch = curl_init();

    $self = $CFG->wwwroot . '/local/ucla/version.php';
    $address = $self;

    curl_setopt($ch, CURLOPT_URL, $address);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($ch);

    $returner = false;
    if (!$res) {
        throw new moodle_exception(curl_error($ch));
    } else {
        if (preg_match('/HTTP\/[0-9]*\.[0-9]*\s*403/', $res)) {
            $returner = true;
        }
    }

    curl_close($ch);

    return $returner;
}

// EOF
