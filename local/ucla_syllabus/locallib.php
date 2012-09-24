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
 * Internal library of functions for UCLA syllabus
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the newmodule specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Constants */
define('UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC', 1);
define('UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN', 2);
define('UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE', 3);

class ucla_syllabus_manager {
    private $courseid;
    private $coursecontext;
    
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->coursecontext = context_course::instance($courseid);
    }
    
    public function can_manage() {
        return has_capability('local/ucla_syllabus:managesyllabus', 
                $this->coursecontext);
    }
    
    public function get_syllabi() {
        global $DB;
        $ret_val = array('public' => null, 'private' => null);        
        
        // get all syllabus entries for course
        $records = $DB->get_records('ucla_syllabus', 
                array('courseid' => $this->courseid));
        
        foreach ($records as $record) {
            switch ($record->access_type) {
                case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                    $ret_val['public'] = new ucla_public_syllabus($record->id);
                case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                    $ret_val['private'] = new ucla_private_syllabus($record->id);
            }            
        }
        
        return $ret_val;
    }    
    
    static function instance($entryid) {
        global $DB;

        // first find access_type so we know which         
        $access_type = $DB->get_field('ucla_syllabus', 'access_type', 
                array('id' => $entryid));
        
        // cast it to the appropiate object type
        switch ($access_type) {
            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                return new ucla_public_syllabus($entryid);
            case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                return new ucla_private_syllabus($entryid);
        }
        
        return null;
    }
}

class ucla_sylabus {
    /* In following format:
     * [columns from ucla_syllabus table]
     * ['stored_file'] => stored_file object
     */
    private $properties = null;
    
    /**
     * Constructor.
     * 
     * If syllabus id is passed, then will fill properties for object. Else,
     * can be used as a shell to save data to create a new syllabus file.
     * 
     * @param int $syllabusid
     */
    public function __construct($syllabusid=null) {        
        if (!empty($syllabusid)) {
            $this->properties = $DB->get_record('ucla_syllabus', array('id' => $entryid));  
            if (empty($this->properties)) {
                throw moodle_exception('Invalid syllabus id');
            }

            // now get file
            $this->properties = $this->locate_syllabus_file($this->properties);       
        } else {
            $this->properties = new stdClass();
        }
    }
    
    /**
     * Magic getting method
     * 
     * @return mixed
     */
    public function __get($name) {
        if (!isset($this->$name)) {
            debugging('ucla_sylabus_base called with invalid $name: ' . $name);  
            return null;
        }
        return $this->properties->$name;
    }

    /**
     * Magic isset method
     * 
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->properties->$name);
    }

    /**
     * Magic setter method
     * 
     * @return mixed
     */
    public function __set($name, $value) {
        return $this->properties->$name = $value;
    }

    /**
     * Magic unset method
     * 
     * @return boolean
     */
    public function __unset($name) {
        unset($this->properties->$name);
    }    
    
    public function can_view($userid=null) {
        $ret_val = false;
        
        // check access type
        switch ($this->access_type) {
            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                $ret_val = true;
                break;
            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                require_login($this->courseid, false);
                if (isloggedin()) {
                    $ret_val = true;
                }
                break;
            case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                $context = context_course::instance($this->courseid);
                $ret_val = is_enrolled($context, $userid);
                break;
            default:
                break;
        }      
        
        return $ret_val;
    }
}

class ucla_private_syllabus extends ucla_sylabus {
}

class ucla_public_syllabus extends ucla_sylabus {
    public function is_preview() {
        if (isset($this->properties)) {
            return $this->properties->is_preview;
        }
        debugging('ucla_public_syllabus called without setting properties');
        return false;
    }
}