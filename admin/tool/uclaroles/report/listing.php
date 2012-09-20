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
 * Report to list roles on current system by either role type or site type.
 *
 * @package    tool
 * @subpackage uclaroles
 * @copyright  2012 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclaroles/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclaroles:view', $syscontext);

// get script variables
$selected_role_type = optional_param('role_type', null, PARAM_ALPHANUMEXT);
$selected_site_type = optional_param('site_type', null, PARAM_ALPHANUMEXT);

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclaroles', '', null, 
        $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclaroles/report/listing.php');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclaroles') . ': ' . 
        get_string('listing', 'tool_uclaroles'), 2, 'headingblock');
echo html_writer::link($CFG->wwwroot . '/' . $CFG->admin . 
        '/tool/uclaroles/index.php', get_string('back', 'tool_uclaroles'));

// output selectors for role  and site type 
$role_dropdown = output_role_type_dropdown($selected_role_type);
$site_type_dropdown = output_site_type_dropdown($selected_site_type);
echo print_dropdown($role_dropdown, $site_type_dropdown);

$roles_table = uclaroles_manager::display_roles($selected_role_type, $selected_site_type);
//print_object($roles_table);

echo html_writer::table($roles_table);

echo $OUTPUT->footer();

// SCRIPT FUNCTIONS

/**
 * Returns html to produce the role type dropdown
 * 
 * @param string $selected_role_type    Optional.
 * 
 * @return string
 */
function output_role_type_dropdown($selected_role_type = null) { 
    $ret_val = html_writer::label(get_string('role_type_dropdown', 
            'tool_uclaroles'), 'menurole_type');
    
    $role_types = uclaroles_manager::get_role_types();
    
    // make sure that selected type is valid
    if (!empty($selected_role_type) && 
            !in_array($selected_role_type, array_keys($role_types))) {
        print_error('invalid_role_type', 'tool_uclaroles');
    }    
    
    $ret_val .= html_writer::select($role_types, 
            'role_type', $selected_role_type, 
            array('' => get_string('showall', 'core', 'types')));
    return $ret_val;
}

/**
 * Returns html to produce the site type dropdown
 * 
 * @param string $selected_site_type    Optional.
 * 
 * @return string
 */
function output_site_type_dropdown($selected_site_type = null) { 
    $ret_val = html_writer::label(get_string('site_type_dropdown', 
            'tool_uclaroles'), 'menusite_type');
    
    $site_types = uclaroles_manager::get_site_types();

    // make sure that selected type is valid
    if (!empty($selected_site_type) && 
            !in_array($selected_site_type, array_keys($site_types))) {
        print_error('invalid_site_type', 'tool_uclaroles');
    }
    
    $ret_val .= html_writer::select($site_types, 
            'site_type', $selected_site_type, 
            array('' => get_string('showall', 'core', 'types')));
    return $ret_val;
}    

/**
 * Outputs form to select filters when viewing roles.
 * 
 * @param type $role_dropdown
 * @param type $site_type_dropdown
 */
function print_dropdown($role_dropdown, $site_type_dropdown) {
    global $PAGE;
        
    echo html_writer::start_tag('form', array(
            'method' => 'get',
            'action' => $PAGE->url
        ));
    
    echo $role_dropdown;
    echo $site_type_dropdown;

    echo html_writer::empty_tag('input', array(
            'type' => 'submit',
            'value' => 'Go'
        ));

    echo html_writer::end_tag('form');    
}
