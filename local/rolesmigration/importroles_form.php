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
 * The form for step one of the Import Roles process.
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/xmlize.php');

class import_roles_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $roles = $this->_customdata['roles'];
        $actions = $this->_customdata['actions'];

        $uploadform = new import_roles_upload_form();
        /* Display when file is being uploaded and configured */
        if($uploadform->is_validated()) {
            $xmlfile = $uploadform->get_file_content('importfile');
            // Include xmlize library and place content of file in memory
            $xml = xmlize($xmlfile);

            $table = import_config_table($xml, $roles['create'], $actions);
            $mform->addElement('html', html_writer::table($table));

            $this->add_action_buttons(false, get_string('next'));   // Submit button
        }else if(empty($this->_customdata['actions'])){
            $mform->addElement('html', $uploadform->display());
        }
    }

    function validation($data, $files){
        $errors = array();

        if(empty($this->_customdata['actions'])){
            $errors['roles'] = get_string('error_noaction', 'local_rolesmigration');
        }

        return $errors;
    }
}

class import_roles_upload_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('filepicker', 'importfile', get_string('files'), null, array('accepted_types' => 'xml'));
        $mform->addRule('importfile', get_string('error_nofile', 'local_rolesmigration'), 'required');

        $this->add_action_buttons(false, get_string('next'));   // Submit button
    }

    function validation($data, $files) {
        $errors = array();

        if($file = $this->get_draft_files('importfile')){
            $file = reset($file);
            $content = $file->get_content();
            $xml = xmlize($content);
            if(empty($content)){
                $errors['importfile'] = get_string('error_emptyfile', 'local_rolesmigration');
            }else if(!$xml || !roles_migration_get_incoming_roles($xml)){
                $errors['importfile'] = get_string('error_badxml', 'local_rolesmigration');
            }
        }

        return $errors;
    }
}
