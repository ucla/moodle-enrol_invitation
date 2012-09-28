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
 * Syllabus form definition.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class syllabus_form extends moodleform {
    private $courseid;
    private $action;
    private $type;
    private $ucla_syllabus_manager;
    
    public function definition(){
        global $CFG, $USER, $DB;
        
        $mform = $this->_form;
        $this->courseid = $this->_customdata['courseid'];
        $this->action = $this->_customdata['action'];     
        if (isset($this->_customdata['type'])) {
            $this->type = $this->_customdata['type'];        
        }        
        $this->ucla_syllabus_manager = $this->_customdata['ucla_syllabus_manager'];
            
        // get course syllabi
        $syllabi = $this->ucla_syllabus_manager->get_syllabi();
                
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT || 
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            if ($this->type == UCLA_SYLLABUS_TYPE_PUBLIC) {
                $this->display_public_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]);                
            } else if ($this->type == UCLA_SYLLABUS_TYPE_PRIVATE) {
                // @todo later
                // $this->display_private_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]);
            }
            
            $mform->addElement('hidden', 'action', $this->action);
            $mform->addElement('hidden', 'type', $this->type);        
            $mform->addElement('hidden', 'id', $this->courseid);            
        } else {
            // if viewing, then display both public/private syllabus forums
            $this->display_public_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]);
            // @todo later
            // $this->display_private_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]);
        }                
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
    public function validation($data, $files) {
        global $DB;
        
        $err = array();
        
        // check if access_type is valid value
        if (!in_array($data['access_types']['access_type'], 
                array(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC, 
                      UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN, 
                      UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE))) {
            $err['access_types'] = get_string('access_invalid', 'local_ucla_syllabus');
        }
        
//        // check if another public syllabus was uploaded  
//        if (ucla_syllabus_manager::has_public_syllabus($data['id'])) {            
//            $err['public_syllabus_file'] = 
//                    get_string('invalid_public_syllabus', 'local_ucla_syllabus');
//        }
        
        return $err;
    }        
    
    // PRIVATE FUNCTIONS
    
    /**
     * Handles display of the public syllabus.
     * 
     *  - If no public syllabus is uploaded, then display form
     *  - If editing public syllabus, then display form and set defaults 
     *  - If public syllabus is uploaded, then don't display form, just filename
     *    and ability to edit or delete
     * 
     * @param ucla_public_syllabus $existing_syllabus
     */
    private function display_public_syllabus($existing_syllabus=null) {
        $mform = $this->_form;        
        
        $mform->addElement('header', 'header_public_syllabus', 
                get_string('public_syllabus', 'local_ucla_syllabus'));
        $mform->addElement('html', html_writer::tag('div', 
                get_string('public_syllabus_help', 'local_ucla_syllabus'),
                array('class' => 'syllabus_help')));        
        
        // show upload form if user is editing
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT || 
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            $this->display_public_syllabus_form($existing_syllabus);
        } else {
            // display edit links for syllabus
            if (!empty($existing_syllabus)) {
                $display_syllabus = html_writer::tag('span', sprintf('%s (%s)', 
                        $existing_syllabus->display_name, 
                        $existing_syllabus->stored_file->get_filename()), 
                        array('class' => 'displayname_filename'));         
                
                $edit_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_EDIT,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('edit'));
                $del_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_DELETE,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('delete'));
                
                $edit_links = html_writer::tag('span', $edit_link . $del_link, array('class' => 'editing_links'));
                $mform->addElement('html', $display_syllabus . $edit_links);
            } else {
                // no syllabus added, so give a "add syllabus now" link
                $text = html_writer::tag('div', get_string('no_syllabus', 
                        'local_ucla_syllabus'), array('class' => 'no_syllabus'));
                $mform->addElement('html', '' . $text);     
                $url = new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_ADD,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC));
                $text = get_string('public_syllabus_add', 'local_ucla_syllabus');
                $link = html_writer::link($url, $text);
                $mform->addElement('html', $link);                
            }
        }
    }
    
    /**
     */
    private function display_public_syllabus_form($existing_syllabus) {
        $mform = $this->_form;        
        
        // single file upload (pdf only)
        $mform->addElement('filemanager', 'public_syllabus_file', 
                sprintf('%s (%s)', get_string('uploadafile', 'moodle'), 
                        get_string('pdf_only', 'local_ucla_syllabus')), null, 
                $this->ucla_syllabus_manager->get_filemanager_config());
        $mform->addRule('public_syllabus_file', 
                get_string('public_syllabus_none_uploaded', 'local_ucla_syllabus'), 
                'required');

        // access type
        $label = get_string('access', 'local_ucla_syllabus');
        $access_types = array();
        $access_types[] = $mform->createElement('radio', 'access_type', '',
                get_string('accesss_public_info', 'local_ucla_syllabus'), 
                UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC);
        $access_types[] = $mform->createElement('radio', 'access_type', '', 
                get_string('accesss_loggedin_info', 'local_ucla_syllabus'), 
                UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN);
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
        
        // set defaults or use existing syllabus
        $defaults = array();
        if (empty($existing_syllabus)) {
            $defaults['access_types[access_type]'] = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $defaults['display_name'] = get_string('display_name_default', 'local_ucla_syllabus');
            $mform->setDefaults($defaults);
        } else {            
            // load existing files
            $draftitemid = file_get_submitted_draft_itemid('public_syllabus_file');   
            file_prepare_draft_area($draftitemid, 
                    context_course::instance($this->courseid)->id, 
                    'local_ucla_syllabus', 'syllabus', $existing_syllabus->id, 
                    $this->ucla_syllabus_manager->get_filemanager_config());        

            // set existing syllabus values
            $data['access_types[access_type]'] = $existing_syllabus->access_type;
            $data['display_name'] = $existing_syllabus->display_name;
            $data['is_preview'] = $existing_syllabus->is_preview;         
            $data['public_syllabus_file'] = $draftitemid;                     
            
            $this->set_data($data);
            
            // indicate that we are editing an existing syllabus
            $mform->addElement('hidden', 'entryid', $existing_syllabus->id);
        }
        
        $this->add_action_buttons();
    }
}