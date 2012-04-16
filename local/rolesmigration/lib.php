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
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serves rolesexport xml files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function local_rolesmigration_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $USER;
    //require_capability('mod/assignment:view', $this->context);

    $fullpath = "/{$context->id}/local_rolesmigration/$filearea/".implode('/', $args);
    
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    if (($USER->id != $file->get_userid()) && !has_capability('moodle/role:manage', $context)) {
        send_file_not_found();
    }
    
    session_get_instance()->write_close(); // unlock session during fileserving
    if (  !send_stored_file($file, 60*60, 0, true) ) {
        send_file_not_found();
    }

}

/**
* Parses uploaded file (or POST args) for xml roles
*/
function roles_migration_get_incoming_roles($xml=false) {
    global $USER;

    if ($xml) {
        if (isset($xml['MOODLE_ROLES_MIGRATION']['#']['ROLES'][0]['#']['ROLE'])) {
            $roles = $xml['MOODLE_ROLES_MIGRATION']['#']['ROLES'][0]['#']['ROLE'];
            foreach($roles as $key => $value) {
                $role_capabilities  = array();
                $role_contextlevels = array();
                // Add capabilities for role
                if (isset($value['#']['ROLE_CAPABILITIES'])) {
                    foreach($value['#']['ROLE_CAPABILITIES'][0]['#']['ROLE_CAPABILITY'] as $rck => $rcv) {
                        $capability = new stdClass();
                        $capability->contextid      = !empty($rcv['#']['CONTEXTID']) ? $rcv['#']['CONTEXTID'][0]['#'] : '';
                        $capability->capability     = !empty($rcv['#']['CAPABILITY']) ? $rcv['#']['CAPABILITY'][0]['#'] : '';
                        $capability->permission     = !empty($rcv['#']['PERMISSION']) ? $rcv['#']['PERMISSION'][0]['#'] : '';
                        $role_capabilities[]        = $capability;
                    }
                }
                // Add context levels for role
                if (isset($value['#']['ROLE_CONTEXTLEVELS'])) {
                    foreach($value['#']['ROLE_CONTEXTLEVELS'][0]['#']['ROLE_CONTEXTLEVEL'] as $rck => $rcv) {
                        $contextlvl = new stdClass();
                        $contextlvl->contextlevel   = !empty($rcv['#']['CONTEXTLEVEL']) ? $rcv['#']['CONTEXTLEVEL'][0]['#'] : '';
                        $role_contextlevels[]       = $contextlvl;
                    }
                }
                $role = new stdClass(); 
                $role->id            = !empty($value['#']['ID'][0]['#']) ? $value['#']['ID'][0]['#'] : '';
                $role->name          = !empty($value['#']['NAME'][0]['#']) ? $value['#']['NAME'][0]['#'] : '';
                $role->shortname     = !empty($value['#']['SHORTNAME'][0]['#']) ? $value['#']['SHORTNAME'][0]['#'] : '';
                $role->description   = !empty($value['#']['DESCRIPTION'][0]['#']) ? $value['#']['DESCRIPTION'][0]['#'] : '';
                $role->sortorder     = !empty($value['#']['SORTORDER'][0]['#']) ? $value['#']['SORTORDER'][0]['#'] : '';
                $role->archetype     = !empty($value['#']['ARCHETYPE'][0]['#']) ? $value['#']['ARCHETYPE'][0]['#'] : '';
                $role->capabilities  = $role_capabilities;
                $role->contextlevels = $role_contextlevels;
                $to_return[]         = $role;
            }
        }
    } else if (isset($USER->rolesmigrationimport)) {
        return $USER->rolesmigrationimport;
    }
    if (!empty($to_return)) {
        $USER->rolesmigrationimport = $to_return;
        return $to_return;
    }
    return false;
}

function import_config_table($xml, $roles_to_create, $actions){
    global $DB;

    // Existing roles in this installation
    $existing_roles = $DB->get_records('role');
    $incoming_roles = roles_migration_get_incoming_roles($xml);

    $table = new html_table();
    $table->attributes['class'] = 'import_form_table';
    $table->align = array('right', 'left', 'left', 'left');
    $table->wrap = array('nowrap', '', 'nowrap', 'nowrap');
    $table->data = array();
    $table->head = array(get_string('name'), get_string('shortname'),
    get_string('action'));
    if (! is_array($incoming_roles)) {
        echo get_string('no_roles_in_import', 'local_rolesmigration');
        return;
    }
    foreach ($incoming_roles as $role) {
        $row = array();
        $row[0] = $role->name;
        $row[1] = $role->shortname;

        $create_checked = (isset($actions[$role->shortname]) && 'create' == $actions[$role->shortname]) ? 'checked="checked"' : '';
        $replace_checked = (isset($actions[$role->shortname]) && 'replace' == $actions[$role->shortname]) ? 'checked="checked"' : '';
        $skip_checked = (empty($create_checked) && empty($replace_checked) ) ? 'checked="checked"' : ''; 

        $shortname_new_value = isset($roles_to_create[$role->shortname]['shortname']) ? $roles_to_create[$role->shortname]['shortname'] : $role->shortname;
        $name_new_value = isset($roles_to_create[$role->shortname]['name']) ? $roles_to_create[$role->shortname]['name'] : $role->name;

        $options = '';
        $replace_options = '';
        foreach ($existing_roles as $er) {
            if (isset($incoming_roles[$role->shortname])) {
                if ($incoming_roles[$role->shortname] == $er->shortname) {
                    $selected = ' selected="selected" ';
                }
            } elseif ($role->shortname == $er->shortname) {
                $selected = ' selected="selected" ';
            } else {
                $selected = '';
            }
            $options .= "<option {$selected} value=\"{$er->shortname}\"> {$er->name} ({$er->shortname})</option>";
        }
        $row[2] = '<ul style="list-style-type: none;">';
        $row[2] .= '<li style="list-style-type: none;">';
        $row[2] .= '<input type="radio" ' . $skip_checked . ' id="skip' . $role->id . '" name="actions[' . $role->shortname . ']" value="skip" />&nbsp;';
        $row[2] .= '<label for="skip' . $role->id . '">' . get_string('do_not_import', 'local_rolesmigration') . '</label>';
        $row[2] .= '</li>';
        $row[2] .= '<li style="list-style-type: none;">';
        $row[2] .= '<input type="radio" ' . $create_checked . ' id="create' . $role->id . '" name="actions[' . $role->shortname . ']" value="create" />&nbsp;';
        $row[2] .= '<label for="create' . $role->id . '">' . get_string('import_new', 'local_rolesmigration') . '</label>';
        $row[2] .= '<ul style="list-style-type: none;margin:0 0 0 35px;padding:0;"><li>' . get_string('shortname', 'local_rolesmigration') . ': <input type="text" name="to_create[' . $role->shortname . '][shortname]" value="' . $shortname_new_value . '" /></li>';
        $row[2] .= '<li>' . get_string('name', 'local_rolesmigration') . ': <input type="text" name="to_create[' . $role->shortname . '][name]" value="' . $name_new_value . '" /></li></ul>';
        $row[2] .= '</li>';
        $row[2] .= '<li style="list-style-type: none;">';
        $row[2] .= '<input type="radio" ' . $replace_checked . ' id="replace' . $role->id . '" name="actions[' . $role->shortname . ']" value="replace" />&nbsp;';
        $row[2] .= '<label for="replace' . $role->id . '">' . get_string('import_replacing', 'local_rolesmigration') . '</label>';
        $row[2] .= '<select name="to_replace[' . $role->shortname . ']" >' . $options . '</select>';
        $row[2] .= '</li>';
        $row[2] .= '</ul>';
        $table->data[] = $row;
    }

    return $table;
}
?>
