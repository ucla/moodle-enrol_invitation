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
 * UCLA course format.  Display the whole course as "topics" made of modules.
 * 
 * Copied from Topic format
 *
 * @package format_ucla
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

$context = context_course::instance($course->id);

if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

$renderer = $PAGE->get_renderer('format_ucla');

$renderer->print_header();

if (optional_param('show_all', 0, PARAM_BOOL)) {
    // user wants to view all sections    
    $renderer->print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);    
} else if (!empty($displaysection)) {
    // user wants to view a given section
    $renderer->print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection);
} else {
    // no section specified, so view landing page or section 0
    $course_prefs = new ucla_course_prefs($course->id);
    $landing_page = $course_prefs->get_preference('landing_page', false);    
    if (empty($landing_page)) {
        $landing_page = 0;  // no landing page, so use section 0
    }
    $renderer->print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $landing_page);            
}

// Include course format js module
$PAGE->requires->js('/course/format/ucla/format.js');
