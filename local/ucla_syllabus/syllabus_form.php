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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class syllabus_form extends moodleform {
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $courseid = $this->_customdata['courseid'];
        $filemanager_config = $this->_customdata['filemanager_config'];
        
        $mform = $this->_form;        
        $mform->addElement('hidden', 'id', $courseid);
        
        $mform->addElement('header', 'header_public_syllabus', 
                get_string('public_syllabus', 'local_ucla_syllabus'));
        
        // single file upload (pdf only)
        $mform->addElement('static', 'public_syllabus_help', '',
                get_string('public_syllabus_help', 'local_ucla_syllabus'));
        $mform->addElement('filemanager', 'public_syllabus_file', 
                sprintf('%s (%s)', get_string('uploadafile', 'moodle'), 
                        get_string('pdf_only', 'local_ucla_syllabus')), null, 
                $filemanager_config);
        $mform->addRule('public_syllabus_file', 
                get_string('public_syllabus_none_uploaded', 'local_ucla_syllabus'), 
                'required');

        // access type
        $label = get_string('access', 'local_ucla_syllabus');
        $access_types = array();
        $access_types[] = $mform->createElement('radio', 'access_type', '',
                get_string('accesss_public_info', 'local_ucla_syllabus'), 
                UCLA_SYLLABUS_PUBLIC);
        $access_types[] = $mform->createElement('radio', 'access_type', '', 
                get_string('accesss_loggedin_info', 'local_ucla_syllabus'), 
                UCLA_SYLLABUS_LOGGEDIN);
        $mform->addGroup($access_types, 'access_types', $label, 
                html_writer::empty_tag('br'));
        $mform->addGroupRule('access_types', 
                get_string('access_none_selected', 'local_ucla_syllabus'), 'required');
        
        // preview syllabus?
        $mform->addElement('checkbox', 'is_preview', '', get_string('preview_info', 'local_ucla_syllabus'));
       
        // display name
        $mform->addElement('text', 'display_name', get_string('display_name', 'local_ucla_syllabus'));
        $mform->addRule('display_name', 
                get_string('display_name_none_entered', 'local_ucla_syllabus'), 
                'required');        
        
        // set defaults
        $mform->setDefaults(
                array('access_types[access_type]' => UCLA_SYLLABUS_LOGGEDIN,
                      'display_name' => get_string('display_name_default', 'local_ucla_syllabus')));
       
        $this->add_action_buttons();
    }
    
    /**
     * Make sure the following is true:
     *  - access_type is valid value
     *  - make sure that only 1 public syllabus is being uploaded
     * 
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     */
    function validation($data, $files) {
        global $DB;
        
        $err = array();
        
        // check if access_type is valid value
        if (!in_array($data['access_types']['access_type'], 
                array(UCLA_SYLLABUS_PUBLIC, 
                      UCLA_SYLLABUS_LOGGEDIN, 
                      UCLA_SYLLABUS_PRIVATE))) {
            $err['access_types'] = get_string('access_invalid', 'local_ucla_syllabus');
        }
        
        // check if another public syllabus was uploaded
        $where = 'courseid=:courseid AND (access_type=:public OR access_type=:loggedin)';
        $result = $DB->record_exists_select('ucla_syllabus', $where, 
                array('courseid' => $data['id'], 
                      'public' => UCLA_SYLLABUS_PUBLIC, 
                      'loggedin' => UCLA_SYLLABUS_LOGGEDIN));
        if (!empty($result)) {
            $err['access_types'] = get_string('invalid_public_syllabus', 'local_ucla_syllabus');
        }        
        
        return $err;
    }        
}