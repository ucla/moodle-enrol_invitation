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
 *  This configuration file has been preconfigured to set certain variables
 *  such that launching and upgrades will run as smoothly as possible.
 *
 *  Currently, the plan is to symbolically link this file as such:
 *  moodle/config.php -> moodle/local/ucla/config/<this file>
 *
 *  The original configuration file should not be used, as it does not have
 *  any capability of saying that another configuration file can be 
 *  included before starting the Moodle session.
 *
 *  If you want configurations to be not within revisioning, then place
 *  your secrets @ moodle/config_private.php.
 *
 **/

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
    'dbsocket'  => 1 
);

$CFG->wwwroot  = '';
$CFG->dataroot = ''; 

// This determines what the admin folder is called.
$CFG->admin    = 'admin';

$CFG->directorypermissions = 0777;

// Registrar
$CFG->registrar_dbtype = 'odbc';
$CFG->registrar_dbhost = 'REGISTRAR';
$CFG->registrar_dbuser = '';
$CFG->registrar_dbpass = '';
$CFG->registrar_dbencoding = 'ISO-8859-1';

// This should never change after the first install, or else any special
// logins using the Moodle login will not work.
$CFG->passwordsaltmain = '';

// If you want to have un-revisioned configuration data, place in this file.
// $CFG->dirroot is overwritten later
$_dirroot_ = dirname(realpath(__FILE__)) . '/../../..';

// Try an alternative directory setup.
if (!file_exists($_dirroot_ . '/config.php')) {
    $_dirroot_ = dirname(realpath(__FILE__));

    if (!file_exists($_dirroot_ . '/config.php')) {
        die ('Improper setup of configuration file.');
    }
}

/** 
 *  Automatic Shibboleth configurations.
 *  Disabling in favor for GUI configurations.
 *  Keeping in code for sake of quick re-enabling and reference.
 *  To re-enable, add a '/' at the end of the following line.
 **/
$CFG->auth = 'shibboleth';
$CFG->alternateloginurl = $CFG->wwwroot . '/login/ucla_login.php?shibboleth';

$CFG->forced_plugin_settings['auth/shibboleth'] = array(
    'user_attribute'    => 'HTTP_SHIB_EDUPERSON_PRINCIPALNAME',
    'convert_data'      => $_dirroot_ . '/shib_transform.php',
    'logout_handler'    => $CFG->wwwroot . '/Shibboleth.sso/Logout',
    'logout_return_url' => 'https://shb.ais.ucla.edu/shibboleth-idp/Logout',
    'login_name'        => 'Shibboleth Login',

    'field_map_firstname'         => 'HTTP_SHIB_GIVENNAME',
    'field_updatelocal_firstname' => 'onlogin',
    'field_lock_firstname'        => 'locked',

    'field_map_lastname'         => 'HTTP_SHIB_PERSON_SURNAME',
    'field_updatelocal_lastname' => 'onlogin',
    'field_lock_lastname'        => 'locked',

    'field_map_email'        => 'HTTP_SHIB_MAIL',
    'field_updatelocal_mail' => 'onlogin',
    'field_lock_email'       => 'unlockedifempty',

    'field_map_idnumber'         => 'HTTP_SHIB_UID',
    'field_updatelocal_idnumber' => 'onlogin',
    'field_lock_idnumber'        => 'locked',

    'field_map_institution'         => 'HTTP_SHIB_IDENTITY_PROVIDER',
    'field_updatelocal_institution' => 'onlogin',
    'field_lock_institution'        => 'locked'
);
/**
 *  End shibboleth configurations.
 **/

// Load a custom private data
$_private_ = $_dirroot_ . '/config_private.php';
if (file_exists($_private_)) {
    require_once($_private_);
}

// This will bootstrap the moodle functions.
require_once($_dirroot_ . '/lib/setup.php');

// EOF
