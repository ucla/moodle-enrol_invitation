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
 * The form for the export roles process.
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class export_roles_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $contextid = $this->_customdata['contextid'];

        $export = $mform->addElement('hidden', 'export', ''); // Will be overwritten below

        $table = new html_table();
        /* Styling done using HTML table and CSS */
        $table->attributes['class'] = 'export_form_table';
        $table->align = array('left', 'left', 'left', 'center');
        $table->wrap = array('nowrap', '', 'nowrap', 'nowrap');
        $table->data = array();

        $table->head = array(get_string('name'),
                            get_string('description'),
                            get_string('shortname'),
                            get_string('export', 'local_rolesmigration'));

        $roles = get_all_roles();
        foreach ($roles as $role) {
            $row = array();
            $roleurl = new moodle_url('/admin/roles/define.php', array('roleid' => $role->id, 'action' => 'view'));
            $row[0] = '<a href="'.$roleurl.'">'.format_string($role->name).'</a>';
            $row[1] = format_text($role->description, FORMAT_HTML);
            $row[2] = ($role->shortname);
            /* Export values are added from role checkboxes */
            $row[3] = '<input type="checkbox" name="export[]" value="'.$role->shortname.'" />';

            $table->data[] = $row;
        }

        $mform->addElement('html', html_writer::table($table));
        $mform->addElement('hidden', 'contextid', $contextid);
        $this->add_action_buttons(false, get_string('submitexport', 'local_rolesmigration'));
    }
}

