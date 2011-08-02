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

// Program: shib_transform.php
// Purpose: Set the firstname to equal SHIB_GIVENNAME + SHIB_MIDDLENAME, and 
// lastname to add Suffix
// Usage: In admin/shibboleth/Data modification API:  
// use: /usr/local/moodle/shib_transform.php
// Updated: 2-22-09 Mike Franks - using displayname from campus directory, 
//  if available
// Updated: 8-22-08 Mike Franks - fix for SSC's Shibboleth config different 
//  attribute names, and shrunk institution
// Updated: 1-10-08 Jovca - fix for Moodle 1.8, change "get_first_string" 
//  to "$this->get_first_string"
// Updated: 4-10-07 Mike Franks - previous switch failed, apparently can't 
//  edit username here, switched to eduPersonPPN which comes in as 
//  uclalogin@ucla.edu
// Updated: 4-6-07 Mike Franks - switching to uclaLogonID which comes in as 
//  mfranks, need to add @ucla.edu
// Updated: 3-5-07 Mike Franks - got it working, with Keith's help. 
//  Copied from auth/shibboleth/README.txt example

$ln = 'lastname';
$fn = 'firstname';
$it = 'institution';

// Changing to retrieve displayname and if it exists, use it instead of 
// official name.
$displayname = array();
if (isset($_SERVER['HTTP_SHIB_DISPLAYNAME'])) {
    $displayname = $this->get_first_string($_SERVER['HTTP_SHIB_DISPLAYNAME']);
}

$suffix = '';

if (!empty($displayname)) {
    list($lastname, $firstname, $suffix) = split(',', $displayname);
    $result[$fn] = strtoupper($firstname);
    $result[$ln]  = strtoupper($lastname);
} else {
    if (isset($_SERVER['HTTP_UCLA_PERSON_MIDDLENAME'])) {
        $middlename  = $this->get_first_string(
            $_SERVER['HTTP_UCLA_PERSON_MIDDLENAME']
        );
    }

    if (!empty($middlename)) {
        $result[$fn] = "{$result[$fn]} $middlename";
    }

    if (isset($_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX'])) {
        $suffix = $this->get_first_string(
            $_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX']
        );
    }
}

$suffix = strtoupper($suffix);
if ($suffix == 'JR') {
    $result[$ln] .= ", $suffix";  // SMITH, JR
} else if (!empty($suffix)) {
    $result[$ln] .= $suffix;      // SMITH II or SMITH III
} 

$result[$it] = str_replace("urn:mace:incommon:","", $result[$it]);

// EOF
