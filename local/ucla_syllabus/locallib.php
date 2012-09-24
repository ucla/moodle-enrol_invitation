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
    private $filemanager_config;
    
    public function __construct($course) {
        $this->courseid = $course->id;
        $this->coursecontext = context_course::instance($course->id);
        
        // config for filepicker
        $maxbytes = get_max_upload_file_size(0, $course->maxbytes);        
        $this->filemanager_config = array('subdirs' => 0, 
                'maxbytes' => $maxbytes, 'maxfiles' => 1, 
                'accepted_types' => array('.pdf'));        
    }
    
    public function can_manage() {
        return has_capability('local/ucla_syllabus:managesyllabus', 
                $this->coursecontext);
    }
    
    /**
     * Returns file picker config array.
     * 
     * @return array
     */
    public function get_filemanager_config() {
        return $this->filemanager_config;
    }
    
    /**
     * UCLA Site menu block hook.
     * 
     * Only display node if there is a syllabus uploaded. If no syllabus 
     * uploaded, then display node if logged in user has the ability to add one.
     * 
     * @return navigation_node
     */
    public function get_navigation_nodes() {
        $node_name = null;  
        $ret_val = null;

        // @todo restrict syllabus tool to only SRS and instructional collab sites
        
        // is there a syllabus uploaded?
        if ($this->has_public_syllabus($this->courseid)) {
            // @todo use syllabus displayname
            $node_name = get_string('display_name_default', 
                    'local_ucla_syllabus');
        } else if ($this->can_manage()) {
            $node_name = get_string('syllabus_needs_setup', 
                    'local_ucla_syllabus');
        }

        if (!empty($node_name)) {
            $url = new moodle_url('/local/ucla_syllabus/index.php', 
                    array('id' => $this->courseid));            
            $ret_val = navigation_node::create($node_name, $url, 
                    navigation_node::TYPE_SECTION);
        }
        
        return $ret_val;
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
    
    /**
     * Checks if given course has a public syllabus
     * 
     * @global moodle_database $DB
     * 
     * @param int $courseid
     * 
     * @return boolean
     */
    public static function has_public_syllabus($courseid)
    {
        global $DB;

        $where = 'courseid=:courseid AND (access_type=:public OR access_type=:loggedin)';
        $result = $DB->record_exists_select('ucla_syllabus', $where, 
                array('courseid' => $courseid, 
                      'public' => UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC, 
                      'loggedin' => UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN));
        
        return $result;
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
    
    /**
     * Saves given syllabus data. Can either be an update (id must be set) or
     * insert as a new record.
     * 
     * @param object $data  Data from syllabus form
     * 
     * @return boolean
     */
    function save_syllabus($data) {
        global $DB;
        
        // first create a entry in ucla_syllabus
        $ucla_syllabus_entry = new stdClass();
        $recordid = null;
                
        $ucla_syllabus_entry->courseid      = $data->id;
        $ucla_syllabus_entry->display_name  = $data->display_name;
        $ucla_syllabus_entry->access_type   = $data->access_types['access_type'];
        $ucla_syllabus_entry->is_preview    = isset($data->is_preview) ? 1 : 0;

        if (isset($data->entryid)) {
            // if id passed, then we are updating a current record
            
            // do quick sanity check to make sure that syllabus entry exists
            $result = $DB->record_exists('ucla_syllabus', array('id' => $data->entryid, 
                    'courseid' => $data->id));
            if (empty($result)) {
                print_error(get_string('err_syllabus_mismatch', 'local_ucla_syllabus'));
            }            
            $recordid = $data->entryid;
            $ucla_syllabus_entry->id = $data->entryid;
            
            $DB->update_record('ucla_syllabus', $ucla_syllabus_entry);            
        } else {
            // insert new record
            $recordid = $DB->insert_record('ucla_syllabus', $ucla_syllabus_entry);        
            if (empty($recordid)) {        
                print_error(get_string('cannnot_make_db_entry', 'local_ucla_syllabus'));
            }            
        }
        
        // then save file, with link to ucla_syllabus
        file_save_draft_area_files($data->public_syllabus_file, 
                $this->coursecontext->id, 'local_ucla_syllabus', 'syllabus', 
                $recordid, $this->filemanager_config);        
        
        return true;
    }
}

abstract class ucla_sylabus {
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
        global $DB;
        if (!empty($syllabusid)) {
            $this->properties = $DB->get_record('ucla_syllabus', array('id' => $syllabusid));  
            if (empty($this->properties)) {
                throw moodle_exception('Invalid syllabus id');
            }

            // now get file
            $this->properties->stored_file = 
                    $this->locate_syllabus_file($syllabusid);       
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
            debugging('ucla_sylabus called with invalid $name: ' . $name);  
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
    
    /**
     * Determine if user can view syllabus.
     * 
     * @return boolean
     */
    abstract public function can_view();

    /**
     * Returns link to download syllabus file.
     * 
     * @param object $syllabus  Result from syllabus getter methods
     * @return string   Returns html to generate link to syllabus
     */
    public function get_download_link() {
        $fullurl = $this->get_file_url();
        if (empty($fullurl)) {
            return '';
        }                
        $string = html_writer::link($fullurl, get_string('clicktodownload', 
                'local_ucla_syllabus', $this->properties->display_name));
        
        return $string;
    }            
    
    /**
     * Get url to syllabus file.
     * 
     * @return  Returns full path to syllabus file, otherwise returns empty string
     */
    public function get_file_url() {
        global $CFG;
        
        if (empty($this->properties) || !isset($this->properties->stored_file)) {
            return '';
        }

        $file = $this->properties->stored_file;
        
        $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/local_ucla_syllabus/syllabus";
        $file = $this->properties->stored_file;
        $filename = $file->get_filename();
        $fileurl = $url.$file->get_filepath().$file->get_itemid().'/'.$filename;        
        
        return $fileurl;
    }    
    
    /**
     * Returns mimetype of uploaded syllabus file.
     * 
     * @return string
     */
    public function get_mimetype() {
        if (empty($this->properties) || !isset($this->properties->stored_file)) {
            return '';
        }        
        return $this->properties->stored_file->get_mimetype();        
    }
    
    /**
     * Returns syllabus file for syllabus object. Must have properties->id set
     * 
     * @return stored_file          Returns stored_file object, if file was 
     *                              uploaded, otherwise returns null.
     */
    private function locate_syllabus_file() {
        $ret_val = null;
        
        if (empty($this->properties->id) || empty($this->properties->courseid)) {
            return null;
        }
        
        $coursecontext = context_course::instance($this->properties->courseid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, 'local_ucla_syllabus', 
                'syllabus', $this->properties->id, '', false);
        
        // should really have just one file uploaded, but handle weird cases
        if (count($files) < 1) {
            // no files uploaded!
            debugging('Warning, no file uploaded for given ucla_syllabus entry');
        } else {
            if (count($files) >1) {
                debugging('Warning, more than one syllabus file uploaded for given ucla_syllabus entry');
            }            
            
            $ret_val = reset($files);
            unset($files);
        }        
        
        return $ret_val;
    }    
}

class ucla_private_syllabus extends ucla_sylabus {
    /**
     * Determine if user can view syllabus.
     * 
     * @return boolean
     */    
    public function can_view() { 
        global $USER;
        return is_enrolled($this->stored_file->get_contextid(), $USER->id);
    }    
}

class ucla_public_syllabus extends ucla_sylabus {
    /**
     * Determine if user can view syllabus.
     * 
     * @return boolean
     */        
    public function can_view() {    
        $ret_val = false;
        // check access type
        switch ($this->access_type) {
            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                $ret_val = true;
                break;
            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                require_login($this->courseid, false);
                if (isloggedin() && !isguestuser()) {
                    $ret_val = true;
                }
                break;
            default:
                break;
        }          
        
        return $ret_val;
    }
    
    public function is_preview() {
        if (isset($this->properties)) {
            return $this->properties->is_preview;
        }
        debugging('ucla_public_syllabus called without setting properties');
        return false;
    }
}