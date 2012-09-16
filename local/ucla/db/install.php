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

require_once(dirname(__FILE__) . '/../lib.php');

/**
 *  Runs extra commands when installing.
 *  Called by Moodle automatically.
 **/
function xmldb_local_ucla_install() {
    global $CFG;

    // Do stuff eventually
    $result = true;

    require_once($CFG->libdir.'/licenselib.php');
    
   // Disable existing licenses
   license_manager::disable('allrightsreserved');
   license_manager::disable('cc');
   license_manager::disable('cc-nc');
   license_manager::disable('cc-nc-nd');
   license_manager::disable('cc-nc-sa');
   license_manager::disable('cc-nd');
   license_manager::disable('cc-sa');
   license_manager::disable('public');
   license_manager::disable('unknown');
   
    // Add new licenses
    $license = new stdClass();
    
    $license->shortname = 'iown';
    $license->fullname = 'I own the copyright';
    $license->source = null;
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'ucown';
    $license->fullname = 'The UC Regents own the copyright';
    $license->source = null;
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'lib';
    $license->fullname = 'Item is licensed by the UCLA Library';
    $license->source = null;
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'public1';
    $license->fullname = 'Item is in the public domain';
    $license->source = 'http://creativecommons.org/licenses/publicdomain/';
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'cc1';
    $license->fullname = 'Item is available for this use via Creative Commons license';
    $license->source = 'http://creativecommons.org/licenses/by/3.0/';
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'obtained';
    $license->fullname = 'I have obtained written permission from the copyright holder';
    $license->source = NULL;
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'fairuse';
    $license->fullname = 'I am using this item under fair use';
    $license->source = NULL;
    $license->enabled = true;        
    $license->version = '2012032200';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    $license->shortname = 'tbd';
    $license->fullname = 'Copyright status not yet identified';
    $license->source = NULL;
    $license->enabled = true;        
    $license->version = '2012060400';
    license_manager::add($license);        
    license_manager::enable($license->shortname);
    
    // Maybe add some tables we need?
    return $result;
}

/**
 *  Runs commands to recover a halted installation.
 *  Called by Moodle automatically.
 **/
function xmldb_local_ucla_install_recovery() {
    // Do stuff eventually
    $result = true;

    return $result;
}

// EOF
