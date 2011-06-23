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

/**
 *  This configuration file are additional settings that want to be
 *  injected into Moodle configurations for use in the UCLA instances
 *  of Moodle.
 *
 *  Currently, the plan is to symbolically link this file as such:
 *  moodle/config.php -> moodle/local/ucla/config/ssc_stage_moodle-config.php
 *
 *  If you still want to use the Moodle-generated configuration file,
 *  then you can rename it config-generated.php, and this script will
 *  attempt to include it first.
 *
 *  The original configuration file should not be used, as it does not have
 *  any capability of saying that another configuration file can be 
 *  included before starting the Moodle session.
 **/

// From the generated config.php
unset($CFG);

global $CFG;

$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = '';
$CFG->dbuser    = '';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbsocket'  => 0 
);

$CFG->wwwroot  = '';
$CFG->dataroot = ''; 

// This determines what the admin folder is called.
$CFG->admin    = 'admin';

$CFG->directorypermissions = 0777;

// This should never change after the first install, or else any special
// logins using the Moodle login will not work.
$CFG->passwordsaltmain = '';

// If you want to have un-revisioned configuration data, place in this file.
$_private_ = dirname(__FILE__) . '/config_private.php';

if (file_exists($_private_)) {
    require_once($_private_);
}

require_once(dirname(__FILE__) . '/lib/setup.php');
