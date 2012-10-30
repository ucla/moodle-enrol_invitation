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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Singleton class for instantiating connections to MyUCLA
 */
final class grade_reporter {
    private static $_instance = null;
    

    //Private constructor so no one else can use it
    private function __construct() {
    }

    //Cloning is not allowed
    private function __clone() {
    }

    /**
     * Gets the singleton instance of a SOAP connection to MyUCLA
     *
     * @return Object - The connection to MyUCLA
     */
    public static function get_instance() {
        if (self::$_instance === NULL) {
            global $CFG;
            $settings = array('exceptions' => true);
  
            //Careful - can raise exceptions
            self::$_instance = new SoapClient($CFG->gradebook_webservice, $settings);
        }
        return self::$_instance;
    }
}