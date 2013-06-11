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
require_once($CFG->libdir . '/validateurlsyntax.php');
require_once(dirname(__FILE__).'/webservice/lib.php');

class syllabus_form extends moodleform {
    private $courseid;
    private $action;
    private $type;
    private $ucla_syllabus_manager;
    private $manualsyllabus;
    
    public function definition(){
        global $CFG, $DB, $OUTPUT, $USER;
        
        $mform = $this->_form;
        $this->courseid = $this->_customdata['courseid'];
        $this->action = $this->_customdata['action'];     
        if (isset($this->_customdata['type'])) {
            $this->type = $this->_customdata['type'];        
        }
        $this->ucla_syllabus_manager = $this->_customdata['ucla_syllabus_manager'];
        $this->manualsyllabus = $this->_customdata['manualsyllabus'];
        
        // get course syllabi
        $syllabi = $this->ucla_syllabus_manager->get_syllabi();
                
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT || 
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            if ($this->type == UCLA_SYLLABUS_TYPE_PUBLIC) {
                $this->display_public_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]);                
            } else if ($this->type == UCLA_SYLLABUS_TYPE_PRIVATE) {
                $this->display_private_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]);
            }
            
            $mform->addElement('hidden', 'action', $this->action);
            $mform->addElement('hidden', 'type', $this->type);        
            $mform->addElement('hidden', 'id', $this->courseid);            
        } else {
            // if viewing, then display both public/private syllabus forums
            if (empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]) &&
                    empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
                $mform->addElement('html', $OUTPUT->notification(
                        get_string('no_syllabus', 'local_ucla_syllabus')));
            }
            $this->display_public_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]);
            $this->display_private_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]);
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
        global $DB, $USER;
        
        $err = array();
        
        // check if access_type is valid value
        if (!in_array($data['access_types']['access_type'], 
                array(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC, 
                      UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN, 
                      UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE))) {
            $err['access_types'] = get_string('access_invalid', 'local_ucla_syllabus');
        }
        
        // see if working with private syllabus file
        if ($this->type == UCLA_SYLLABUS_TYPE_PRIVATE) {
            // make sure that access_type is private
            $data['access_types']['access_type'] = UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
        }
        
        // Need to make sure we have URL or file
        $nourl = empty($data['syllabus_url']);
        $nofile = false;
        
        // Validate URL syntax
        if(!$nourl && !validateUrlSyntax($data['syllabus_url'], 's+')) {
            // maybe it failed because the url is missing http://?
            if (validateUrlSyntax('http://' . $data['syllabus_url'], 's+')) {
                // works!
                $data['syllabus_url'] = 'http://' . $data['syllabus_url'];
                $this->_form->updateSubmission($data, $files);
            } else {
                $err['syllabus_url'] = get_string('err_invalid_url', 'local_ucla_syllabus');
            }
        }

        // make sure file was uploaded
        $draftitemid = file_get_submitted_draft_itemid('syllabus_file');    
        if (empty($draftitemid)) {
            $nofile = true;
        } 
        
        // Make sure we have a real file.  We need to test that an actual file 
        // was uploaded since $draftitemid can be unreliable 
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id DESC', false)) {
            $nofile = true;
        }
        
        // If we're missing both file & URL, then send warning
        if($nourl && $nofile) {
            $err['syllabus_upload_desc'] = get_string('err_file_url_not_uploaded', 'local_ucla_syllabus');
        }
        
        return $err;
    }        
    
    // PRIVATE FUNCTIONS

    /**
     * Handles display of the private syllabus.
     * 
     *  - If no private syllabus is uploaded, then display link to upload
     *  - If editing private syllabus, then display form and set defaults 
     *  - If private syllabus is uploaded, then don't display form, just 
     *    filename and ability to edit or delete
     * 
     * @param ucla_private_syllabus $existing_syllabus
     */
    private function display_private_syllabus($existing_syllabus=null) {
        $mform = $this->_form;        
        
        $mform->addElement('header', 'header_private_syllabus', 
                get_string('private_syllabus', 'local_ucla_syllabus'));
        
        // show upload form if user is editing
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT || 
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            $this->display_private_syllabus_form($existing_syllabus);
        } else {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('private_syllabus_help', 'local_ucla_syllabus'),
                    array('class' => 'syllabus_help')));

            // display edit links for syllabus
            if (!empty($existing_syllabus)) {
                $display_syllabus = $this->display_syllabus_info($existing_syllabus);

                $edit_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_EDIT,
                                  'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                        get_string('edit'));
                $make_public_link = '';
                if (! ucla_syllabus_manager::has_public_syllabus($this->courseid)) {
                    $make_public_link = html_writer::link(
                            new moodle_url('/local/ucla_syllabus/index.php', 
                                array('id' => $this->courseid,
                                    'action' => UCLA_SYLLABUS_ACTION_CONVERT,
                                    'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                            get_string('make_public', 'local_ucla_syllabus'));
                }
                
                // then add link to delete (with very bad javascript confirm prompt)
                // TODO: use YUI or put the javascript prompt in separate js file
                $del_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php',
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_DELETE,
                                  'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                        get_string('delete'),
                        array('onclick' => 'return confirm("'.get_string('confirm_deletion', 'local_ucla_syllabus').'")'));
                
                $edit_links = html_writer::tag('span', $edit_link . $make_public_link . $del_link, 
                        array('class' => 'editing_links'));
                $mform->addElement('html', $display_syllabus . $edit_links);
            } else {
                // no syllabus added, so give a "add syllabus now" link                
                $url = new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_ADD,
                                  'type' => UCLA_SYLLABUS_TYPE_PRIVATE));
                $text = get_string('private_syllabus_add', 'local_ucla_syllabus');
                $link = html_writer::link($url, $text);
                $mform->addElement('html', $link);                
            }
        }
    }    
    
    /**
     * Handles display of the public syllabus.
     * 
     *  - If no public syllabus is uploaded, then display link to upload
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
        
        // show upload form if user is editing
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT || 
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            $this->display_public_syllabus_form($existing_syllabus);
        } else {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('public_syllabus_help', 'local_ucla_syllabus'),
                    array('class' => 'syllabus_help')));                        

            // display edit links for syllabus
            if (!empty($existing_syllabus)) {                
                $display_syllabus = $this->display_syllabus_info($existing_syllabus);

                $edit_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_EDIT,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('edit'));
                $make_private_link = '';
                if (!ucla_syllabus_manager::has_private_syllabus($this->courseid)) {
                    $make_private_link = html_writer::link(
                            new moodle_url('/local/ucla_syllabus/index.php', 
                                array('id' => $this->courseid,
                                    'action' => UCLA_SYLLABUS_ACTION_CONVERT,
                                    'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                            get_string('make_private', 'local_ucla_syllabus'));
                }

                // then add link to delete (with very bad javascript confirm prompt)
                // TODO: use YUI or put the javascript prompt in separate js file
                $del_link = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php', 
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_DELETE,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('delete'),
                        array('onclick' => 'return confirm("'.get_string('confirm_deletion', 'local_ucla_syllabus').'")'));
                
                $edit_links = html_writer::tag('span', $edit_link . $make_private_link . $del_link, 
                        array('class' => 'editing_links'));
                $mform->addElement('html', $display_syllabus . $edit_links);
            } else {
                // no syllabus added, so give a "add syllabus now" link                
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
     * Displays form fields related to private syllabus.
     */
    private function display_private_syllabus_form($existing_syllabus) {
        $mform = $this->_form;        
        
        // Check if course is subscribed to web service
        $webserviced = syllabus_ws_manager::is_subscribed($this->courseid);
        $config = $this->ucla_syllabus_manager->get_filemanager_config();
        
        if($webserviced) {
            // Limit web service to accept PDF files only
            $config['accepted_types'] = array('.pdf');
            
            $mform->addElement('hidden', 'syllabus_url', '');
            // single file upload 
            $mform->addElement('filemanager', 'syllabus_file', 
                    get_string('upload_file', 'local_ucla_syllabus'), null, $config);
            $mform->addRule('syllabus_file', 
                    get_string('err_file_not_uploaded', 'local_ucla_syllabus'), 
                    'required');
        } else {
            // Add description
            $mform->addElement('static', 'syllabus_upload_desc', get_string('syllabus_url_file', 'local_ucla_syllabus'), 
                    get_string('syllabus_choice', 'local_ucla_syllabus'));

            // single file upload
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('file', 'local_ucla_syllabus'), null, $config);
            $mform->addElement('static', 'desc', '', 'OR');

            // Add URL field
            $mform->addElement('text', 'syllabus_url', get_string('url', 'local_ucla_syllabus'),
                    array('size'=>'50'));
        }

        // access type
        $mform->addElement('hidden', 'access_types[access_type]', UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE);
        
        // display name
        $mform->addElement('text', 'display_name', get_string('display_name', 'local_ucla_syllabus'));
        $mform->addRule('display_name', 
                get_string('display_name_none_entered', 'local_ucla_syllabus'), 
                'required');          
        
        // set defaults or use existing syllabus
        $defaults = array();
        if (empty($existing_syllabus)) {
            $defaults['display_name'] = get_string('display_name_default', 'local_ucla_syllabus');
            $mform->setDefaults($defaults);
        } else {            
            // load existing files
            $draftitemid = file_get_submitted_draft_itemid('syllabus_file');   
            file_prepare_draft_area($draftitemid, 
                    context_course::instance($this->courseid)->id, 
                    'local_ucla_syllabus', 'syllabus', $existing_syllabus->id, 
                    $config);

            // set existing syllabus values
            $data['display_name'] = $existing_syllabus->display_name;
            $data['syllabus_file'] = $draftitemid;                     
            $data['syllabus_url'] = empty($existing_syllabus->url) ? '' : $existing_syllabus->url;
            
            $this->set_data($data);
            
            // indicate that we are editing an existing syllabus
            $mform->addElement('hidden', 'entryid', $existing_syllabus->id);
        }
        
        $this->add_action_buttons();
    }    
    
    /**
     * Displays form fields related to public syllabus.
     */
    private function display_public_syllabus_form($existing_syllabus) {
        $mform = $this->_form;        

        $webserviced = syllabus_ws_manager::is_subscribed($this->courseid);
        $config = $this->ucla_syllabus_manager->get_filemanager_config();
        
        if($webserviced) {
            // Limit web service to accept PDF files only
            $config['accepted_types'] = array('.pdf');
            
            $mform->addElement('hidden', 'syllabus_url', '');
            // single file upload 
            $mform->addElement('filemanager', 'syllabus_file', 
                    get_string('upload_file', 'local_ucla_syllabus'), null, $config);
            $mform->addRule('syllabus_file', 
                    get_string('err_file_not_uploaded', 'local_ucla_syllabus'), 
                    'required');
        } else {
            
            // Add description
            $mform->addElement('static', 'syllabus_upload_desc', get_string('syllabus_url_file', 'local_ucla_syllabus'), 
                    get_string('syllabus_choice', 'local_ucla_syllabus'));

            // single file upload
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('file', 'local_ucla_syllabus'), null, $config);
            $mform->addElement('static', 'desc', '', 'OR');

            // Add URL field
            $mform->addElement('text', 'syllabus_url', get_string('url', 'local_ucla_syllabus'),
                    array('size'=>'50'));
        }

        // access type
        $label = get_string('access', 'local_ucla_syllabus');
        $access_types = array();
        $access_types[] = $mform->createElement('radio', 'access_type', '', 
                get_string('accesss_loggedin_info', 'local_ucla_syllabus'), 
                UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN);
        $access_types[] = $mform->createElement('radio', 'access_type', '',
                get_string('accesss_public_info', 'local_ucla_syllabus'),
                UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC);
        $mform->addGroup($access_types, 'access_types', $label, 
                html_writer::empty_tag('br'));
        $mform->addGroupRule('access_types', 
                get_string('access_none_selected', 'local_ucla_syllabus'), 'required');
               
        // display name
        $mform->addElement('text', 'display_name', get_string('display_name', 'local_ucla_syllabus'));
        $mform->addRule('display_name', 
                get_string('display_name_none_entered', 'local_ucla_syllabus'), 
                'required');          

        // preview syllabus?
        $mform->addElement('checkbox', 'is_preview', '', get_string('preview_info', 'local_ucla_syllabus'));

        // set defaults or use existing syllabus
        $defaults = array();
        if (empty($existing_syllabus)) {
            $defaults['access_types[access_type]'] = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $defaults['display_name'] = get_string('display_name_default', 'local_ucla_syllabus');
            $mform->setDefaults($defaults);

            // Check if user is trying to use an manually uploaded syllabus.
            // Only public syllabus can handle a manual syllabus.
            $this->handle_manual_syllabus();
        } else {
            // load existing files
            $draftitemid = file_get_submitted_draft_itemid('syllabus_file');   
            file_prepare_draft_area($draftitemid, 
                    context_course::instance($this->courseid)->id, 
                    'local_ucla_syllabus', 'syllabus', $existing_syllabus->id, 
                    $config);

            // set existing syllabus values
            $data['access_types[access_type]'] = $existing_syllabus->access_type;
            $data['display_name'] = $existing_syllabus->display_name;
            $data['is_preview'] = $existing_syllabus->is_preview;         
            $data['syllabus_file'] = $draftitemid;
            $data['syllabus_url'] = empty($existing_syllabus->url) ? '' : $existing_syllabus->url;
            
            $this->set_data($data);
            
            // indicate that we are editing an existing syllabus
            $mform->addElement('hidden', 'entryid', $existing_syllabus->id);
        }
        
        $this->add_action_buttons();
    }

    /**
     * Helper function to generate output to display syllabus information for
     * use in a form.
     *
     * @param ucla_sylabus $syllabus
     */
    protected function display_syllabus_info($syllabus) {
        global $CFG;
        
        $syllabus_info = array(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC => 'public_world',
                               UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN => 'public_ucla',
                               UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE => 'private');
        $syllabus_type = $syllabus_info[$syllabus->access_type];
        $image_string = get_string('icon_'.$syllabus_type.'_syllabus', 'local_ucla_syllabus');

        // Display icon for syllabus.
        $display_name = html_writer::tag('img', '',
            array('src' => $CFG->wwwroot.'/local/ucla_syllabus/pix/'.$syllabus_type.'.png',
                'alt' => $image_string,
                'title' => $image_string
                )
            );

        // Give preference to URL
        $display_name .= $syllabus->display_name;
        if(empty($syllabus->url)) {
            $filename = $syllabus->stored_file->get_filename();
        } else {
            $filename = $syllabus->url;
        }

        $type_text = '';
        if ($syllabus->is_preview &&
                $syllabus->access_type != UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE) {
            $type_text = sprintf('(%s)',
                    get_string('preview', 'local_ucla_syllabus'));
        }
        $display_syllabus = html_writer::tag('span', sprintf('%s %s (%s)',
                $display_name, $type_text, $filename),
                array('class' => 'displayname_filename'));

        return $display_syllabus;
    }

    /////// MANUALLY UPLOADED SYLLABUS METHODS

    /**
     * Returns the necessary information to make a manually uploaded syllabus.
     *
     * @return object   Returns null if there is no manual syllabus. Else
     *                  returns an object with coursemodule record plus url or
     *                  file information.
     */
    private function get_manual_syllabus_info() {
        global $DB;

        if (empty($this->manualsyllabus)) {
            return null;
        }

        $cm = get_coursemodule_from_id(null, $this->manualsyllabus, $this->courseid);
        if (empty($cm)) {
            return null;
        }

        $module = $DB->get_record($cm->modname, array('id' => $cm->instance,
            'course' => $this->courseid));
        if (empty($module)) {
            // something is wrong here, just don't try to use this module.
            return null;
        }

        $cm->module = $module;
        return $cm;
    }

    /**
     * Sets the form's initial values if a manually uploaded syllabus is found.
     *
     * For url resources, it will set the url. For file resources, it will copy
     * the file into the file manager.
     *
     * For both resources it will set the display name to be the module name.
     */
    private function handle_manual_syllabus() {
        $manualsyllabus = $this->get_manual_syllabus_info();
        if (!empty($manualsyllabus)) {
            $data['display_name'] = $manualsyllabus->module->name;
            if ($manualsyllabus->modname == 'url') {
                // Set value for URL.
                $data['syllabus_url'] = $manualsyllabus->module->externalurl;
            } else if ($manualsyllabus->modname == 'resource') {
                // Copy existing resouce file (assume we are getting first file).
                $draftitemid = file_get_submitted_draft_itemid('syllabus_file');
                file_prepare_draft_area($draftitemid,
                        context_module::instance($manualsyllabus->id)->id,
                        'mod_resource', 'content', 0,
                        $this->ucla_syllabus_manager->get_filemanager_config());
                $data['syllabus_file'] = $draftitemid;
            }
            $this->set_data($data);
        }
    }
}