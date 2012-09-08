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
 * Class to help handle UCLA syllabus functionality.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_syllabus {
    private $courseid = null;
    private $coursecontext = null;
    private $public_syllabus = null;   // store cached copy
    
    /**
     * Constructor
     * 
     * Sets course for ucla syllabus class to operate.
     * 
     * @param int $courseid
     */
    function __construct($courseid) {
        if (empty($courseid)) {
            throw new moodle_exception('err_missing_courseid', 'local_ucla_syllabus');
        }        
        $this->courseid = $courseid;
        $this->coursecontext = context_course::instance($courseid);
    }   

    /**
     * Returns link to download syllabus file.
     * 
     * @param object $syllabus  Result from syllabus getter methods
     * @return string   Returns html to generate link to syllabus
     */
    public function get_download_link($syllabus) {
        $fullurl = $this->get_file_url($syllabus);
                
        $string = html_writer::link($fullurl, get_string('clicktodownload', 
                'local_ucla_syllabus', $syllabus->display_name));
        
        return $string;
    }        
    
    /**
     * Returns the public syllabus for given course.
     * 
     * Data includes entry from ucla_syllabus and a stored_file object.
     * 
     * @global moodle_database $DB
     * @param int $courseid
     * 
     * @return mixed                Returns false if no file, else 
     */
    public function get_public_syllabus() {
        global $DB;
        
        if (isset($this->public_syllabus)) {
            return $this->public_syllabus;
        }
        
        $where = 'courseid=:courseid AND (access_type=:public OR access_type=:loggedin)';
        $syllabus = $DB->get_record_select('ucla_syllabus', $where, 
                array('courseid' => $this->courseid, 
                      'public' => UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC, 
                      'loggedin' => UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN));              
        if (empty($syllabus)) {
            return false;
        }
                
        // now get file
        $stored_file = $this->locate_syllabus_file($syllabus->id);
        $syllabus->stored_file = $stored_file;

        $this->public_syllabus = $syllabus; // store cache
        
        return $syllabus;
    }
    
    /**
     * Get url to given syllabus file.
     * 
     * @param object $syllabus  Result from syllabus getter methods
     * 
     * @return  Returns full path to syllabus file, otherwise returns empty string
     */
    public function get_file_url($syllabus) {
        global $CFG;
        
        // sanity checks
        if (empty($syllabus) || !isset($syllabus->stored_file)) {
            return '';
        }

        $file = $syllabus->stored_file;
        
        $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/local_ucla_syllabus/syllabus";
        $file = $syllabus->stored_file;
        $filename = $file->get_filename();
        $fileurl = $url.$file->get_filepath().$file->get_itemid().'/'.$filename;        
        
        return $fileurl;
    }

    /**
     * Returns the given syllabus file for given ucla_syllabus id.
     * 
     * Data includes entry from ucla_syllabus and a stored_file object.
     * 
     * @global moodle_database $DB
     * @param int $entryid
     * 
     * @return mixed                Returns false if no file, else 
     */
    public function get_syllabus($entryid) {
        global $DB;
        
        $syllabus = $DB->get_record('ucla_syllabus', array('id' => $entryid));  
        if (empty($syllabus)) {
            debugging('returning false, $entryid = ' . $entryid);
            return false;
        }
                
        // now get file
        $stored_file = $this->locate_syllabus_file($syllabus->id);
        $syllabus->stored_file = $stored_file;

        return $syllabus;
    }    
    
    // PRIVATE METHODS
    
    /**
     * Returns syllabus file for given ucla_syllabus entry id.
     * 
     * @param type $entryid
     * 
     * @return stored_file          Returns stored_file object, if file was 
     *                              uploaded, otherwise returns null.
     */
    private function locate_syllabus_file($entryid) {
        $ret_val = null;
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->coursecontext->id, 
                'local_ucla_syllabus', 'syllabus', $entryid, '', false);
        
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
