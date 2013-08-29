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
 * @package    local_ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/validateurlsyntax.php');
require_once(dirname(__FILE__).'/webservice/lib.php');

/**
 * Syllabus form class.
 * 
 * Defines the form required for performing some action with
 * a syllabus.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_form extends moodleform {
    /** @var int The ID of the course for the syllabus. */
    private $courseid;

    /** @var int The action the form wishes to take (defined in locallib.php). */
    private $action;

    /** @var int The type of syllabus (defined in locallib.php). */
    private $type;

    /** @var object The syllabus manager. */
    private $syllabusmanager;

    /** @var object The manually uploaded syllabus (if applicable). */
    private $manualsyllabus;

    /**
     * Extracts information from configuration, database, output, and user
     * variables to create the proper form.
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT, $USER;

        $mform = $this->_form;
        $this->courseid = $this->_customdata['courseid'];
        $this->action = $this->_customdata['action'];
        if (isset($this->_customdata['type'])) {
            $this->type = $this->_customdata['type'];
        }
        $this->syllabusmanager = $this->_customdata['ucla_syllabus_manager'];
        $this->manualsyllabus = $this->_customdata['manualsyllabus'];

        // Get course syllabi.
        $syllabi = $this->syllabusmanager->get_syllabi();

        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT ||
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            if ($this->type == UCLA_SYLLABUS_TYPE_PUBLIC) {
                $this->display_public_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]);
            } else if ($this->type == UCLA_SYLLABUS_TYPE_PRIVATE) {
                $this->display_private_syllabus($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]);
            }

            $mform->addElement('hidden', 'action', $this->action);
            $mform->setType('action', PARAM_ALPHA);
            $mform->addElement('hidden', 'type', $this->type);
            $mform->setType('type', PARAM_ALPHA);
            $mform->addElement('hidden', 'id', $this->courseid);
            $mform->setType('id', PARAM_INT);
        } else {
            // If viewing, then display both public/private syllabus forums.
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
     * Validates syllabus form.
     * 
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

        // Check if access_type is valid value.
        if (!in_array($data['access_types']['access_type'],
                array(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC,
                      UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN,
                      UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE))) {
            $err['access_types'] = get_string('access_invalid', 'local_ucla_syllabus');
        }

        // See if working with private syllabus file.
        if ($this->type == UCLA_SYLLABUS_TYPE_PRIVATE) {
            // Make sure that access_type is private.
            $data['access_types']['access_type'] = UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
        }

        // Need to make sure we have URL or file.
        $nourl = empty($data['syllabus_url']);
        $nofile = false;

        // Validate URL syntax.
        if (!$nourl && !validateUrlSyntax($data['syllabus_url'], 's+')) {
            // See if it failed because is missing the http:// at the beginning.
            if (validateUrlSyntax('http://' . $data['syllabus_url'], 's+')) {
                // It was.
                $data['syllabus_url'] = 'http://' . $data['syllabus_url'];
                $this->_form->updateSubmission($data, $files);
            } else {
                $err['syllabus_url'] = get_string('err_invalid_url', 'local_ucla_syllabus');
            }
        }

        // Make sure file was uploaded.
        $draftitemid = file_get_submitted_draft_itemid('syllabus_file');
        if (empty($draftitemid)) {
            $nofile = true;
        }

        // Make sure we have a real file.  We need to test that an actual file
        // was uploaded since $draftitemid can be unreliable.
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id DESC', false)) {
            $nofile = true;
        }

        // If we're missing both file & URL, then send warning.
        if ($nourl && $nofile) {
            $err['syllabus_upload_desc'] = get_string('err_file_url_not_uploaded', 'local_ucla_syllabus');
        }

        return $err;
    }

    // Private functions.

    /**
     * Handles display of the private syllabus.
     * 
     *  - If no private syllabus is uploaded, then display link to upload
     *  - If editing private syllabus, then display form and set defaults 
     *  - If private syllabus is uploaded, then don't display form, just 
     *    filename and ability to edit or delete
     * 
     * @param ucla_private_syllabus $existingsyllabus
     */
    private function display_private_syllabus($existingsyllabus=null) {
        $mform = $this->_form;

        $mform->addElement('header', 'header_private_syllabus',
                get_string('private_syllabus', 'local_ucla_syllabus'));

        // Show upload form if user is editing.
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT ||
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            $this->display_private_syllabus_form($existingsyllabus);
        } else {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('private_syllabus_help', 'local_ucla_syllabus'),
                    array('class' => 'syllabus_help')));

            // Display edit links for syllabus.
            if (!empty($existingsyllabus)) {
                $displaysyllabus = $this->display_syllabus_info($existingsyllabus);

                $editlink = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php',
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_EDIT,
                                  'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                        get_string('edit'));
                $makepubliclink = '';
                if (! ucla_syllabus_manager::has_public_syllabus($this->courseid)) {
                    $makepubliclink = html_writer::link(
                            new moodle_url('/local/ucla_syllabus/index.php',
                                array('id' => $this->courseid,
                                    'action' => UCLA_SYLLABUS_ACTION_CONVERT,
                                    'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                            get_string('make_public', 'local_ucla_syllabus'));
                }

                // Then add link to delete (with very bad javascript confirm prompt)
                // TODO: use YUI or put the javascript prompt in separate js file.
                $deletelink = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php',
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_DELETE,
                                  'type' => UCLA_SYLLABUS_TYPE_PRIVATE)),
                        get_string('delete'),
                        array('onclick' => 'return confirm("'.get_string('confirm_deletion', 'local_ucla_syllabus').'")'));

                $editlinks = html_writer::tag('span', $editlink . $makepubliclink . $deletelink,
                        array('class' => 'editing_links'));
                $mform->addElement('html', $displaysyllabus . $editlinks);
            } else {
                // No syllabus added, so give a "add syllabus now" link.
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
     * @param ucla_public_syllabus $existingsyllabus
     */
    private function display_public_syllabus($existingsyllabus=null) {
        $mform = $this->_form;

        $mform->addElement('header', 'header_public_syllabus',
                get_string('public_syllabus', 'local_ucla_syllabus'));

        // Show upload form if user is editing.
        if ($this->action == UCLA_SYLLABUS_ACTION_EDIT ||
                $this->action == UCLA_SYLLABUS_ACTION_ADD) {
            $this->display_public_syllabus_form($existingsyllabus);
        } else {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('public_syllabus_help', 'local_ucla_syllabus'),
                    array('class' => 'syllabus_help')));

            // Display edit links for syllabus.
            if (!empty($existingsyllabus)) {
                $displaysyllabus = $this->display_syllabus_info($existingsyllabus);

                $editlink = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php',
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_EDIT,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('edit'));
                $makeprivatelink = '';
                if (!ucla_syllabus_manager::has_private_syllabus($this->courseid)) {
                    $makeprivatelink = html_writer::link(
                            new moodle_url('/local/ucla_syllabus/index.php',
                                array('id' => $this->courseid,
                                    'action' => UCLA_SYLLABUS_ACTION_CONVERT,
                                    'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                            get_string('make_private', 'local_ucla_syllabus'));
                }

                // Then add link to delete (with very bad javascript confirm prompt)
                // TODO: use YUI or put the javascript prompt in separate js file.
                $deletelink = html_writer::link(
                        new moodle_url('/local/ucla_syllabus/index.php',
                            array('id' => $this->courseid,
                                  'action' => UCLA_SYLLABUS_ACTION_DELETE,
                                  'type' => UCLA_SYLLABUS_TYPE_PUBLIC)),
                        get_string('delete'),
                        array('onclick' => 'return confirm("'.get_string('confirm_deletion', 'local_ucla_syllabus').'")'));

                $editlinks = html_writer::tag('span', $editlink . $makeprivatelink . $deletelink,
                        array('class' => 'editing_links'));
                $mform->addElement('html', $displaysyllabus . $editlinks);
            } else {
                // No syllabus added, so give a "add syllabus now" link.
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
     * 
     * @param object $existingsyllabus
     */
    private function display_private_syllabus_form($existingsyllabus) {
        $mform = $this->_form;

        // Check if course is subscribed to web service.
        $webserviced = syllabus_ws_manager::is_subscribed($this->courseid);
        $config = $this->syllabusmanager->get_filemanager_config();

        if ($webserviced) {
            // Limit web service to accept PDF files only.
            $config['accepted_types'] = array('.pdf');

            $mform->addElement('hidden', 'syllabus_url', '');
            $mform->setType('syllabus_url', PARAM_URL);
            // Perform single file upload.
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('upload_file', 'local_ucla_syllabus'), null, $config);
            $mform->addRule('syllabus_file',
                    get_string('err_file_not_uploaded', 'local_ucla_syllabus'),
                    'required');
        } else {
            // Add a syllabus description.
            $mform->addElement('static', 'syllabus_upload_desc', get_string('syllabus_url_file', 'local_ucla_syllabus').
                    get_string('syllabus_choice', 'local_ucla_syllabus'));

            // Perform single file upload.
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('file', 'local_ucla_syllabus'), null, $config);
            $mform->addElement('static', 'desc', '', 'OR');

            // Add URL field.
            $mform->addElement('text', 'syllabus_url', get_string('url', 'local_ucla_syllabus'),
                    array('size'=>'50'));
            $mform->setType('syllabus_url', PARAM_URL);
        }

        // Show access type.
        $mform->addElement('hidden', 'access_types[access_type]', UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE);
        $mform->setType('access_types[access_type]', PARAM_TEXT);

        // Show display name.
        $mform->addElement('text', 'display_name', get_string('display_name', 'local_ucla_syllabus'));
        $mform->setType('display_name', PARAM_TEXT);
        $mform->addRule('display_name',
                get_string('display_name_none_entered', 'local_ucla_syllabus'),
                'required');

        // Set defaults or use existing syllabus.
        $defaults = array();
        if (empty($existingsyllabus)) {
            $defaults['display_name'] = get_string('display_name_default', 'local_ucla_syllabus');
            $mform->setDefaults($defaults);
        } else {
            // Load existing files.
            $draftitemid = file_get_submitted_draft_itemid('syllabus_file');
            file_prepare_draft_area($draftitemid,
                    context_course::instance($this->courseid)->id,
                    'local_ucla_syllabus', 'syllabus', $existingsyllabus->id,
                    $config);

            // Set existing syllabus values.
            $data['display_name'] = $existingsyllabus->display_name;
            $data['syllabus_file'] = $draftitemid;
            $data['syllabus_url'] = empty($existingsyllabus->url) ? '' : $existingsyllabus->url;

            $this->set_data($data);

            // Indicate that we are editing an existing syllabus.
            $mform->addElement('hidden', 'entryid', $existingsyllabus->id);
        }

        $this->add_action_buttons();
    }

    /**
     * Displays form fields related to public syllabus.
     * 
     * @param object $existingsyllabus
     */
    private function display_public_syllabus_form($existingsyllabus) {
        $mform = $this->_form;

        $webserviced = syllabus_ws_manager::is_subscribed($this->courseid);
        $config = $this->syllabusmanager->get_filemanager_config();

        if ($webserviced) {
            // Limit web service to accept PDF files only.
            $config['accepted_types'] = array('.pdf');

            $mform->addElement('hidden', 'syllabus_url', '');
            $mform->setType('syllabus_url', PARAM_URL);
            // Perform single file upload.
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('upload_file', 'local_ucla_syllabus'), null, $config);
            $mform->addRule('syllabus_file',
                    get_string('err_file_not_uploaded', 'local_ucla_syllabus'),
                    'required');
        } else {

            // Add syllabus description.
            $mform->addElement('static', 'syllabus_upload_desc', get_string('syllabus_url_file', 'local_ucla_syllabus'),
                    get_string('syllabus_choice', 'local_ucla_syllabus'));

            // Perform single file upload.
            $mform->addElement('filemanager', 'syllabus_file',
                    get_string('file', 'local_ucla_syllabus'), null, $config);
            $mform->addElement('static', 'desc', '', 'OR');

            // Add URL field.
            $mform->addElement('text', 'syllabus_url', get_string('url', 'local_ucla_syllabus'),
                    array('size'=>'50'));
            $mform->setType('syllabus_url', PARAM_URL);
        }

        // Show access type.
        $label = get_string('access', 'local_ucla_syllabus');
        $accesstypes = array();
        $accesstypes[] = $mform->createElement('radio', 'access_type', '',
                get_string('accesss_loggedin_info', 'local_ucla_syllabus'),
                UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN);
        $accesstypes[] = $mform->createElement('radio', 'access_type', '',
                get_string('accesss_public_info', 'local_ucla_syllabus'),
                UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC);
        $mform->addGroup($accesstypes, 'access_types', $label,
                html_writer::empty_tag('br'));
        $mform->addGroupRule('access_types',
                get_string('access_none_selected', 'local_ucla_syllabus'), 'required');

        // Show display name.
        $mform->addElement('text', 'display_name', get_string('display_name', 'local_ucla_syllabus'));
        $mform->setType('display_name', PARAM_TEXT);
        $mform->addRule('display_name',
                get_string('display_name_none_entered', 'local_ucla_syllabus'),
                'required');

        // Preview the syllabus.
        $mform->addElement('checkbox', 'is_preview', '', get_string('preview_info', 'local_ucla_syllabus'));

        // Set defaults or use existing syllabus.
        $defaults = array();
        if (empty($existingsyllabus)) {
            $defaults['access_types[access_type]'] = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $defaults['display_name'] = get_string('display_name_default', 'local_ucla_syllabus');
            $mform->setDefaults($defaults);

            // Check if user is trying to use an manually uploaded syllabus.
            // Only public syllabus can handle a manual syllabus.
            $this->handle_manual_syllabus();
        } else {
            // Load existing files.
            $draftitemid = file_get_submitted_draft_itemid('syllabus_file');
            file_prepare_draft_area($draftitemid,
                    context_course::instance($this->courseid)->id,
                    'local_ucla_syllabus', 'syllabus', $existingsyllabus->id,
                    $config);

            // Set existing syllabus values.
            $data['access_types[access_type]'] = $existingsyllabus->access_type;
            $data['display_name'] = $existingsyllabus->display_name;
            $data['is_preview'] = $existingsyllabus->is_preview;
            $data['syllabus_file'] = $draftitemid;
            $data['syllabus_url'] = empty($existingsyllabus->url) ? '' : $existingsyllabus->url;

            $this->set_data($data);

            // Indicate that we are editing an existing syllabus.
            $mform->addElement('hidden', 'entryid', $existingsyllabus->id);
        }

        $this->add_action_buttons();
    }

    /**
     * Helper function to generate output to display syllabus information for
     * use in a form.
     *
     * @param ucla_syllabus $syllabus
     */
    protected function display_syllabus_info($syllabus) {
        global $CFG;

        $syllabusinfo = array(UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC => 'public_world',
                               UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN => 'public_ucla',
                               UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE => 'private');
        $syllabustype = $syllabusinfo[$syllabus->access_type];
        $imagestring = get_string('icon_'.$syllabustype.'_syllabus', 'local_ucla_syllabus');

        // Display icon for syllabus.
        $displayname = html_writer::tag('img', '',
            array('src' => $CFG->wwwroot.'/local/ucla_syllabus/pix/'.$syllabustype.'.png',
                'alt' => $imagestring,
                'title' => $imagestring
                )
            );

        // Give preference to URL.
        $displayname .= $syllabus->display_name;
        if (empty($syllabus->url)) {
            $filename = $syllabus->stored_file->get_filename();
        } else {
            $filename = $syllabus->url;
        }

        $typetext = '';
        if ($syllabus->is_preview &&
                $syllabus->access_type != UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE) {
            $typetext = sprintf('(%s)',
                    get_string('preview', 'local_ucla_syllabus'));
        }
        $displaysyllabus = html_writer::tag('span', sprintf('%s %s (%s)',
                $displayname, $typetext, $filename),
                array('class' => 'displayname_filename'));

        return $displaysyllabus;
    }


    // Manually uploaded syllabus methods.

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
            // Something is wrong here, just don't try to use this module.
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
                        $this->syllabusmanager->get_filemanager_config());
                $data['syllabus_file'] = $draftitemid;
            }
            $this->set_data($data);
        }
    }
}