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
 * Report to list role remappings on system when you change site types.
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

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclaroles', '', null, 
        $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclaroles/report/remappings.php');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclaroles') . ': ' . 
        get_string('remapping', 'tool_uclaroles'), 2, 'headingblock');
echo html_writer::link($CFG->wwwroot . '/' . $CFG->admin . 
        '/tool/uclaroles/index.php', get_string('back', 'tool_uclaroles'));

echo html_writer::tag('div', get_string('remapping_intro', 'tool_uclaroles'));

$remappings_table = uclaroles_manager::display_role_remappings();

echo html_writer::table($remappings_table);

echo $OUTPUT->footer();
