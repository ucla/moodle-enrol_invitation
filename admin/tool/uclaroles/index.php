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
 * Landing page to display available UCLA role reports.
 *
 * @package    tool
 * @subpackage uclaroles
 * @copyright  2012 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclaroles/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclaroles:view', $syscontext);

// Initialize $PAGE
$PAGE->set_url($CFG->wwwroot . '/' . $CFG->admin . '/tool/uclaroles/index.php');
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclaroles'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclaroles');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclaroles'), 2, 'headingblock');

echo $OUTPUT->box_start('generalbox');

echo $OUTPUT->heading(get_string('reports_heading', 'tool_uclaroles'));
echo html_writer::tag('p', get_string('reports_intro', 'tool_uclaroles'));

// NOTE: report types need to match script name, have corresponding entry in 
// lang file and be located in "report" directory
$report_types = array(
    'listing'
);

// create nodes to put in ordered list
foreach ($report_types as $index => $report_type) {
    $url = '/' . $CFG->admin . '/tool/uclaroles/report/' . $report_type . '.php';
    $report_types[$index] = html_writer::link(new moodle_url($url), 
            get_string($report_type, 'tool_uclaroles'));
}

echo html_writer::alist($report_types, array(), 'ol');

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

