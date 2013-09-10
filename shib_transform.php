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

// NOTE: This file is being included by auth/shibboleth/auth.php: get_userinfo
// so there already exists an $result array.

require_once(dirname(__FILE__) . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Changing to retrieve displayname and if it exists, use it instead of 
// official name.
$displayname = array();
if (isset($_SERVER['HTTP_SHIB_DISPLAYNAME'])) {
    $displayname = $this->get_first_string($_SERVER['HTTP_SHIB_DISPLAYNAME']);
}

if (!empty($displayname)) {
    $formattedname = format_displayname($displayname);
    $result['firstname'] = $formattedname['firstname'];
    $result['lastname'] = $formattedname['lastname'];
} else {
    // No display name, but use any middle or suffix name, if available.
    if (isset($_SERVER['HTTP_UCLA_PERSON_MIDDLENAME'])) {
        $middlename  = $this->get_first_string(
            $_SERVER['HTTP_UCLA_PERSON_MIDDLENAME']
        );
        $middlename = ucla_format_name($middlename);
        $result['firstname'] = $result['firstname'] . ' ' . $middlename;
    }

    if (isset($_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX'])) {
        $suffix = $this->get_first_string(
            $_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX']
        );
        $result['lastname'] .= ' ' . $suffix;
    }

    $result['firstname'] = ucla_format_name($result['firstname']);
    $result['lastname'] = ucla_format_name($result['lastname']);
}

$result['institution'] = str_replace("urn:mace:incommon:","", $result['institution']);
