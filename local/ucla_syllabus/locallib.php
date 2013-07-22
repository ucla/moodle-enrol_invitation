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

define('UCLA_SYLLABUS_TYPE_PUBLIC', 'public');
define('UCLA_SYLLABUS_TYPE_PRIVATE', 'private');

define('UCLA_SYLLABUS_ACTION_ADD', 'add');
define('UCLA_SYLLABUS_ACTION_DELETE', 'delete');
define('UCLA_SYLLABUS_ACTION_EDIT', 'edit');
define('UCLA_SYLLABUS_ACTION_VIEW', 'view');
define('UCLA_SYLLABUS_ACTION_CONVERT', 'convert');

class ucla_syllabus_manager {
    private $courseid;
    private $filemanagerconfig;

    public function __construct($course) {
        $this->courseid = $course->id;

        // Configuration for file picker.
        $maxbytes = get_max_upload_file_size(0, $course->maxbytes);
        $this->filemanagerconfig = array('subdirs' => 0,
                'maxbytes' => $maxbytes, 'maxfiles' => 1,
                'accepted_types' => array('*'));
        // Accept everything '*' - restricting to 'documents' does not seem to work.
    }

    /**
     * Returns if given course can host syllabus files. Currently, only SRS 
     * based courses can have syllabus files.
     * CCLE-3792: Instructional collab sites can host syllabus files 
     * (along with SRS based courses).
     *
     * @return boolean
     */
    public function can_host_syllabi() {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

        $site = siteindicator_site::load($this->courseid);
        return !is_collab_site($this->courseid) || (!is_null($site) &&
            $site->property->type == siteindicator_manager::SITE_TYPE_INSTRUCTION);
    }

    /**
     * Returns if logged in user has the ability to manage syllabi for course.
     * 
     * @return boolean
     */
    public function can_manage() {
        $coursecontext = context_course::instance($this->courseid);
        return has_capability('local/ucla_syllabus:managesyllabus',
                $coursecontext);
    }

    /**
     * Deletes given syllabus.
     * 
     * @param ucla_syllabus $syllabus   Expecting an object that is derived from
     *                                  the ucla_syllabus class
     */
    public function delete_syllabus($syllabus) {
        global $DB;
        // Do some sanity checks.

        // Make sure parameter is valid object.
        if (!is_object($syllabus) || !($syllabus instanceof ucla_sylabus) ||
                empty($syllabus->id)) {
            print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        }

        // Make sure that syllabus belongs to course.
        if ($syllabus->courseid != $this->courseid) {
            print_error('err_syllabus_mismatch', 'local_ucla_syllabus');
        }

        // First, delete files if they exist.  We may have URL-only syllabus.
        if (!empty($syllabus->stored_file)) {
            $syllabus->stored_file->delete();
        }

        // Next, delete entry in syllabus table.
        $DB->delete_records('ucla_syllabus', array('id' => $syllabus->id));

        // Data to handle events.
        $data = new stdClass();
        $data->courseid = $syllabus->courseid;
        $data->access_type = $syllabus->access_type;

        // Trigger necessary events.
        events_trigger('ucla_syllabus_deleted', $data);
    }

    /**
     * Convert between public or private syllabuses
     * 
     * @param ucla_syllabus $syllabus   Expecting an object that is derived from
     *                                  the ucla_syllabus class
     * @param $converto   UCLA_SYLLABUS_TYPE_PUBLIC | UCLA_SYLLABUS_TYPE_PRIVATE
     */
    public function convert_syllabus($syllabus, $convertto) {
        global $DB;

        if (empty($syllabus)) {
            print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        }

        // Make sure parameter is valid object.
        if (!is_object($syllabus) || !($syllabus instanceof ucla_sylabus) ||
                empty($syllabus->id)) {
            print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        }

        // Make sure that syllabus belongs to course.
        if ($syllabus->courseid != $this->courseid) {
            print_error('err_syllabus_mismatch', 'local_ucla_syllabus');
        }

        // If a public and private syllabus already exists, then we cannot
        // convert the syllabus.
        if (self::has_public_syllabus($this->courseid) &&
                self::has_private_syllabus($this->courseid)
                ) {
            print_error('err_syllabus_convert', 'local_ucla_syllabus');
        }

        $data = new StdClass();
        $data->id = $syllabus->id;
        $data->courseid = $syllabus->courseid;
        $data->display_name = $syllabus->display_name;
        $data->access_type = $convertto;
        $data->is_preview = $syllabus->is_preview;
        $DB->update_record('ucla_syllabus', $data);

        $olddata = $data;
        $olddata->access_type = $syllabus->access_type;

        // Trigger events.
        events_trigger('ucla_syllabus_deleted', $olddata);
        events_trigger('ucla_syllabus_added', $data);
    }

    /**
     * Returns an array of file and url resources that might be a manually
     * uploaded syllabus.
     * 
     * @global moodle_database $DB
     *
     * @param int $timestart        Optional. If given, will restrict the search
     *                              to only course modules added on or after
     *                              given unix timestamp.
     * @param int $timeend          Optional. If given, will restrict the search
     *                              to only course modules added on or before
     *                              given unix timestamp.
     *
     * @return array    Returns an array of objects with fields 'cmid', 'name',
     *                  and 'type' (resource or url). Returns cmid
     *                  (course_module id) instead of resource/url id, because
     *                  the later might not be unique.
     */
    public function get_all_manual_syllabi($timestart=null, $timeend=null) {
        global $DB;

        $searchstring = '%syllabus%';

        $timesql = '';
        if (!empty($timestart)) {
            $timesql .= ' AND cm.added >= ' . intval($timestart);
        }
        if (!empty($timeend)) {
            $timesql .= ' AND cm.added <= ' . intval($timeend);
        }

        // Check file and url resources.
        $sql = "(
                    SELECT  cm.id AS cmid,
                            r.name AS name,
                            m.name AS type
                    FROM    {resource} r
                    JOIN    {course_modules} cm ON (r.id=cm.instance)
                    JOIN    {modules} m ON (cm.module=m.id)
                    WHERE   cm.course=:courseid1 AND
                            m.name='resource' AND " .
                            $DB->sql_like('r.name', ':searchstring1', false, false) .
                            $timesql
                . ") UNION ALL
                (
                    SELECT  cm.id AS cmid,
                            u.name AS name,
                            m.name AS type
                    FROM    {url} u
                    JOIN    {course_modules} cm ON (u.id=cm.instance)
                    JOIN    {modules} m ON (cm.module=m.id)
                    WHERE   cm.course=:courseid2 AND
                            m.name='url' AND " .
                            $DB->sql_like('u.name', ':searchstring2', false, false) .
                            $timesql
                . ")";
        $manualsyllabi = $DB->get_records_sql($sql,
                array('courseid1' => $this->courseid,
                      'courseid2' => $this->courseid,
                      'searchstring1' => $searchstring,
                      'searchstring2' => $searchstring));

        return $manualsyllabi;
    }

    /**
     * Returns file picker config array.
     * 
     * @return array
     */
    public function get_filemanager_config() {
        return $this->filemanagerconfig;
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
        global $USER;
        $nodename = null;
        $retval = null;

        // Restrict syllabus tool to only SRS sites.
        if (!$this->can_host_syllabi()) {
            return $retval;
        }

        // Is there a syllabus uploaded?
        $syllabi = $this->get_syllabi();

        if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]) &&
                $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]->can_view()) {
            // See if logged in user can view private syllabus.
            $nodename = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]->display_name;
        } else if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]) &&
                $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]->can_view()) {
            // Fallback on trying to see if user can view public syllabus.
            $nodename = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]->display_name;
        } else if ($this->can_manage() && !empty($USER->editing)) {
            // If no syllabus, then only show node for instructors to add a
            // syllabus when in editing mode.
            $nodename = get_string('syllabus_needs_setup', 'local_ucla_syllabus');
        }

        if (!empty($nodename)) {
            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('id' => $this->courseid));
            $retval = navigation_node::create($nodename, $url,
                    navigation_node::TYPE_SECTION);
        }

        return $retval;
    }

    /**
     * Returns an array of syllabi for course indexed by type.
     * 
     * @global moodle_database $DB
     * @return array
     */
    public function get_syllabi() {
        global $DB;
        $retval = array(UCLA_SYLLABUS_TYPE_PUBLIC => null,
                         UCLA_SYLLABUS_TYPE_PRIVATE => null);

        // Get all syllabus entries for course.
        $records = $DB->get_records('ucla_syllabus',
                array('courseid' => $this->courseid));

        foreach ($records as $record) {
            switch ($record->access_type) {
                case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                    $retval[UCLA_SYLLABUS_TYPE_PUBLIC] =
                            new ucla_public_syllabus($record->id);
                    break;
                case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                    $retval[UCLA_SYLLABUS_TYPE_PRIVATE] =
                            new ucla_private_syllabus($record->id);
                    break;
            }
        }

        return $retval;
    }

    /**
     * Checks if given course has a private syllabus. If so, then returns 
     * syllabus id, otherwise false.
     * 
     * @global moodle_database $DB
     * 
     * @param int $courseid
     * 
     * @return int              Returns false if no syllabus found
     */
    public static function has_private_syllabus($courseid) {
        global $DB;

        $where = 'courseid=:courseid AND access_type=:private';
        $result = $DB->get_field_select('ucla_syllabus', 'id', $where,
                array('courseid' => $courseid,
                      'private' => UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE));

        return $result;
    }

    /**
     * Checks if given course has a public syllabus. If so, then returns 
     * syllabus id, otherwise false.
     * 
     * @global moodle_database $DB
     * 
     * @param int $courseid
     * 
     * @return int              Returns false if no syllabus found
     */
    public static function has_public_syllabus($courseid) {
        global $DB;

        $where = 'courseid=:courseid AND (access_type=:public OR access_type=:loggedin)';
        $result = $DB->get_field_select('ucla_syllabus', 'id', $where,
                array('courseid' => $courseid,
                      'public' => UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC,
                      'loggedin' => UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN));

        return $result;
    }

    /**
     * Checks if course has any type of syllabus. If so, then returns true,
     * otherwise false.
     *
     * @global moodle_database $DB
     *
     * @return boolean
     */
    public function has_syllabus() {
        global $DB;

        return $DB->record_exists('ucla_syllabus',
                array('courseid' => $this->courseid));
    }

    public static function instance($entryid) {
        global $DB;

        // First find access_type so we know which.
        $accesstype = $DB->get_field('ucla_syllabus', 'access_type',
                array('id' => $entryid));

        // Cast it to the appropiate object type.
        switch ($accesstype) {
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
     * @param object $data  Data from syllabus form. Assumes it is validated
     * 
     * @return int      Returns recordid of added/updated syllabus
     */
    public function save_syllabus($data) {
        global $DB;

        // First create a entry in ucla_syllabus.
        $syllabusentry = new stdClass();
        $recordid = null;
        $eventname = '';

        $syllabusentry->courseid      = $data->id;
        $syllabusentry->display_name  = $data->display_name;
        $syllabusentry->access_type   = $data->access_types['access_type'];
        $syllabusentry->is_preview    = isset($data->is_preview) ? 1 : 0;
        $syllabusentry->url           = $data->syllabus_url;
        $syllabusentry->timemodified  = time();

        if (isset($data->entryid)) {
            // If id passed, then we are updating a current record.

            // Do quick sanity check to make sure that syllabus entry exists.
            $result = $DB->record_exists('ucla_syllabus', array('id' => $data->entryid,
                    'courseid' => $data->id));
            if (empty($result)) {
                print_error(get_string('err_syllabus_mismatch', 'local_ucla_syllabus'));
            }
            $recordid = $data->entryid;
            $syllabusentry->id = $data->entryid;

            $DB->update_record('ucla_syllabus', $syllabusentry);

            $eventname = 'ucla_syllabus_updated';
        } else {
            // Save when this syllabi was created.
            $syllabusentry->timecreated  = time();

            // Insert new record.
            $recordid = $DB->insert_record('ucla_syllabus', $syllabusentry);
            if (empty($recordid)) {
                print_error(get_string('cannnot_make_db_entry', 'local_ucla_syllabus'));
            }

            $eventname = 'ucla_syllabus_added';
        }

        // Then save file, with link to syllabus.
        $coursecontext = context_course::instance($this->courseid);
        file_save_draft_area_files($data->syllabus_file,
                $coursecontext->id, 'local_ucla_syllabus', 'syllabus'.
                $recordid, $this->filemanagerconfig);

        // No errors, so trigger events.
        events_trigger($eventname, $recordid);

        return $recordid;
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
        // Lazy load stored_file, since it is pretty complex.
        if ($name == 'stored_file') {
            if (!isset($this->properties->stored_file)) {
                $this->properties->stored_file =  $this->locate_syllabus_file();
            }
        }

        if (!isset($this->properties) || !isset($this->properties->$name)) {
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
        // Lazy load stored_file, since it is pretty complex.
        if ($name == 'stored_file') {
            $this->stored_file;
        }
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

        if (empty($this->properties) || !isset($this->stored_file)) {
            return '';
        }

        $file = $this->stored_file;

        $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/local_ucla_syllabus/syllabus";
        $file = $this->stored_file;
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
        if (!isset($this->stored_file)) {
            return '';
        }
        return $this->stored_file->get_mimetype();
    }

    /**
     * Returns syllabus file for syllabus object. Must have properties->id set
     * 
     * @return stored_file          Returns stored_file object, if file was 
     *                              uploaded, otherwise returns null.
     */
    private function locate_syllabus_file() {
        $retval = null;

        if (empty($this->properties->id) || empty($this->properties->courseid)) {
            return null;
        }

        $coursecontext = context_course::instance($this->properties->courseid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, 'local_ucla_syllabus',
                'syllabus', $this->properties->id, '', false);

        // Should really have just one file uploaded, but handle weird cases.
        if (count($files) < 1 && empty($this->properties->url)) {
            // No files uploaded and no URL added!
            debugging('Warning, no file uploaded for given ucla_syllabus entry');
        } else {
            if (count($files) >1) {
                debugging('Warning, more than one syllabus file uploaded for given ucla_syllabus entry');
            }

            $retval = reset($files);
            unset($files);
        }

        return $retval;
    }
}

class ucla_private_syllabus extends ucla_sylabus {
    /**
     * Determine if user can view syllabus.
     * 
     * @return boolean
     */
    public function can_view() {
        // Need to check if we have URL.
        if (empty($this->url)) {
            $coursecontext = context::instance_by_id($this->stored_file->get_contextid());
        } else {
            $coursecontext = context_course::instance($this->courseid);
        }
        return is_enrolled($coursecontext) ||
                has_capability('local/ucla_syllabus:managesyllabus', $coursecontext);
    }
}

class ucla_public_syllabus extends ucla_sylabus {
    /**
     * Determine if user can view syllabus.
     * 
     * @return boolean
     */
    public function can_view() {
        $retval = false;
        // Check access type.
        switch ($this->access_type) {
            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                $retval = true;
                break;
            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                if (isloggedin() && !isguestuser()) {
                    $retval = true;
                }
                break;
            default:
                break;
        }

        return $retval;
    }
}
