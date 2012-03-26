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
 * Performs the XML generation for Moodle Roles based on input from the Roles Export Form
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include necessary XML libarary files
require_once($CFG->dirroot.'/backup/util/xml/output/xml_output.class.php');
require_once($CFG->dirroot.'/backup/util/xml/output/memory_xml_output.class.php');
require_once($CFG->dirroot.'/backup/util/xml/xml_writer.class.php');

// Site context
$sitecontext = get_context_instance(CONTEXT_SYSTEM);

// Init file storage object
$fs = get_file_storage();

// Init empty array of roles to export
$role_ids = array();

// File information
$fileinfo = array(
    'userid'   => $USER->id,
    'contextid' => $sitecontext->id,
    'component' => 'local_rolesmigration',
    'filearea' => 'backup',
    'itemid' => time(),
    'filepath' => '/local/rolesmigration/temp/',
    'filename' => 'rolesexport.xml'
);

// Delete file if it already exists
if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
    $file->delete();
}

// Init XML oupt and writer objects
$xml_output = new memory_xml_output();
$xml = new xml_writer( $xml_output );

// Start the XML construction process
$xml->start();

// Open the top level XML element
$xml->begin_tag('MOODLE_ROLES_MIGRATION');

    // General site and migration data
    $xml->begin_tag('INFO');
        $xml->full_tag('NAME', 'rolesmigration');
        $xml->full_tag('MOODLE_VERSION', $CFG->version);
        $xml->full_tag('MOODLE_RELEASE', $CFG->release);
        $xml->full_tag('BACKUP_VERSION', $CFG->backup_version);
        $xml->full_tag('BACKUP_RELEASE', $CFG->backup_release);
        $xml->full_tag('DATE', time());
        $xml->full_tag('ORIGINAL_WWWROOT', $CFG->wwwroot);
        $xml->full_tag('ORIGINAL_SITE_IDENTIFIER_HASH', md5(get_site_identifier()));
    $xml->end_tag('INFO');

    // The roles tag contains all data for selected Roles on export screen
    $xml->begin_tag('ROLES');
        // Loop through provided role  IDs
        foreach($data->export as $role) {
            // Grab role from DB
            if ($role = $DB->get_record('role', array('shortname' => $role))) {
                $role_array = (array) $role;
                // Loop through columns and create tag for each one
                $xml->begin_tag('ROLE');
                    foreach ($role_array as $field => $value) {
                        $xml->full_tag(strtoupper($field), $value);
                        // Lets make an array of Role IDs to use later while we're here
                        if ( 'id' == $field ) {
                            $role_ids[] = $value;
                        }
                    }
                    // The ROLE_CAPABILITIES tag contains data from the role_capabilities table associated with selected ROLES
                    $xml->begin_tag('ROLE_CAPABILITIES');
                        // Loop through provided role  IDs
                        if ($rolecaps = $DB->get_records('role_capabilities', array('contextid' => $sitecontext->id, 'roleid' => end($role_ids)))) {
                            foreach( $rolecaps as $key => $cap ) {
                                $cap_array = (array) $cap;
                                // Loop through columns and create tag for each one
                                // Only print the capabilities if they're associated with one of our roles
                                if ( in_array($cap->roleid, $role_ids)) {
                                    $xml->begin_tag('ROLE_CAPABILITY');
                                    foreach ($cap_array as $field => $value) {
                                        $xml->full_tag(strtoupper($field), $value);
                                    }
                                    $xml->end_tag('ROLE_CAPABILITY');
                                }
                            }
                        }
                    $xml->end_tag('ROLE_CAPABILITIES');

                    // The ROLE_CONTEXTLEVELS tag contains data from the role_capabilities table associated with selected ROLES
                    $xml->begin_tag('ROLE_CONTEXTLEVELS');
                        // Loop through provided role  IDs
                        if ($ctxlevs = $DB->get_records('role_context_levels', array('roleid' => end($role_ids)))) {
                            foreach( $ctxlevs as $key => $lev ) {
                                $lev_array = (array) $lev;
                                // Loop through columns and create tag for each one
                                // Only print the context levels if they're associated with one of our roles
                                if ( in_array($lev->roleid, $role_ids)) {
                                    $xml->begin_tag('ROLE_CONTEXTLEVEL');
                                    $xml->full_tag('CONTEXTLEVEL', $lev->contextlevel);
                                    $xml->end_tag('ROLE_CONTEXTLEVEL');
                                }
                            }
                        }
                    $xml->end_tag('ROLE_CONTEXTLEVELS');
                $xml->end_tag('ROLE');
            }
        }
    $xml->end_tag('ROLES');

$xml->end_tag('MOODLE_ROLES_MIGRATION');
$xml->stop();

// Create the XML file from the XML object stored in memory
if ($fs->create_file_from_string($fileinfo, $xml_output->get_allcontents())) {
    if ($file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
        $path = "/".$fileinfo['contextid']."/".$fileinfo['component']."/".$fileinfo['filearea']."/".$fileinfo['itemid'].$fileinfo['filepath'].$fileinfo['filename'];
        $url = moodle_url::make_file_url($CFG->wwwroot."/pluginfile.php", $path);
        redirect($url);
    } else {
        send_file_not_found();
    }
} else {
        send_file_not_found();
}
die();
