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
require_once(dirname(__FILE__) . '/lib.php');

// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

// Build our required forums
$forum_new = forum_get_course_forum($course->id, 'news');
$forum_gen = forum_get_course_forum($course->id, 'general');

$context = context_course::instance($course->id);

if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

/* @var $format format_ucla */
$format = course_get_format($course);

/* @var $renderer format_ucla_renderer */
$renderer = $PAGE->get_renderer('format_ucla');
$renderer->print_header();

// make sure all sections are created
course_create_sections_if_missing($format->get_course(), range(0, $format->get_course()->numsections));

$displaysection = $format->figure_section();

if ($displaysection === $format::UCLA_FORMAT_DISPLAY_ALL) {
    $renderer->print_multiple_section_page($format->get_course(), $sections, $mods, $modnames, $modnamesused);
} else {
    $renderer->print_single_section_page($format->get_course(), null, null, null, null, $displaysection);
}

// Include course format js module
$PAGE->requires->js('/course/format/ucla/format.js');
