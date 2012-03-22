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
 * A namespace contains license specific functions
 *
 * @since      2.0
 * @package    core
 * @subpackage lib
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class license_manager {
    /**
     * Adding a new license type
     * @param object $license {
     *            shortname => string a shortname of license, will be refered by files table[required]
     *            fullname  => string the fullname of the license [required]
     *            source => string the homepage of the license type[required]
     *            enabled => int is it enabled?
     *            version  => int a version number used by moodle [required]
     * }
     */
    static public function add($license) {
        global $DB;
        if ($record = $DB->get_record('license', array('shortname'=>$license->shortname))) {
            // record exists
            if ($record->version < $license->version) {
                // update license record
                $license->enabled = $record->enabled;
                $license->id = $record->id;
                $DB->update_record('license', $license);
            }
        } else {
            $DB->insert_record('license', $license);
        }
        return true;
    }

    /**
     * Get license records
     * @param mixed $param
     * @return array
     */
    static public function get_licenses($param = null) {
        global $DB;
        if (empty($param) || !is_array($param)) {
            $param = array();
        }
        // get licenses by conditions
        if ($records = $DB->get_records('license', $param)) {
            return $records;
        } else {
            return array();
        }
    }

    /**
     * Get license record by shortname
     * @param mixed $param the shortname of license, or an array
     * @return object
     */
    static public function get_license_by_shortname($name) {
        global $DB;
        if ($record = $DB->get_record('license', array('shortname'=>$name))) {
            return $record;
        } else {
            return null;
        }
    }

    /**
     * Enable a license
     * @param string $license the shortname of license
     * @return boolean
     */
    static public function enable($license) {
        global $DB;
        if ($license = self::get_license_by_shortname($license)) {
            $license->enabled = 1;
            $DB->update_record('license', $license);
        }
        self::set_active_licenses();
        return true;
    }

    /**
     * Disable a license
     * @param string $license the shortname of license
     * @return boolean
     */
    static public function disable($license) {
        global $DB, $CFG;
        // Site default license cannot be disabled!
        if ($license == $CFG->sitedefaultlicense) {
            print_error('error');
        }
        if ($license = self::get_license_by_shortname($license)) {
            $license->enabled = 0;
            $DB->update_record('license', $license);
        }
        self::set_active_licenses();
        return true;
    }

    /**
     * Store active licenses in global $CFG
     */
    static private function set_active_licenses() {
        // set to global $CFG
        $licenses = self::get_licenses(array('enabled'=>1));
        $result = array();
        foreach ($licenses as $l) {
            $result[] = $l->shortname;
        }
        set_config('licenses', implode(',', $result));
    }

    /**
     * Install moodle build-in licenses
     */
    static public function install_licenses() {
        $active_licenses = array();

        $license = new stdClass();
        
        $license->shortname = 'iown';
        $license->fullname = 'I own the copyright';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'ucown';
        $license->fullname = 'The UC Regents own the copyright';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'lib';
        $license->fullname = 'Item is licensed by the UCLA Library';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
       
        $license->shortname = 'public';
        $license->fullname = 'Item is in the public domain';
        $license->source = 'http://creativecommons.org/licenses/publicdomain/';
        $license->enabled = 1;
        $license->version = '2010033100';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'cc';
        $license->fullname = 'Item is available for this use via Creative Commons license';
        $license->source = 'http://creativecommons.org/licenses/by/3.0/';
        $license->enabled = 1;
        $license->version = '2010033100';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'obtained';
        $license->fullname = 'I have obtained written permission from the copyright holder';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'fairuse';
        $license->fullname = 'I am using this item under fair use';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'tbd';
        $license->fullname = 'Upload by faculty designate; copyright status to be determined';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2012032200';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        set_config('licenses', implode(',', $active_licenses));
    }
}
