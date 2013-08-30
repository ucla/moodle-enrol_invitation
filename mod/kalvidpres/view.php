<?php

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
 * Kaltura video presentation
 *
 * @package    mod
 * @subpackage kalvidpres
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id         = optional_param('id', 0, PARAM_INT);           // Course Module ID
$admin_mode = '0';

// Retrieve module instance
if (empty($id)) {
    print_error('invalidid', 'kalvidpres');
}

if (!empty($id)) {

    if (! $cm = get_coursemodule_from_id('kalvidpres', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $kalvidpres = $DB->get_record('kalvidpres', array("id"=>$cm->instance))) {
        print_error('invalidid', 'kalvidpres');
    }
}

require_course_login($course->id, true, $cm);

global $SESSION, $CFG;

$PAGE->set_url('/mod/kalvidpres/view.php', array('id'=>$id));
$PAGE->set_title(format_string($kalvidpres->name));
$PAGE->set_heading($course->fullname);

$renderer = $PAGE->get_renderer('mod_kalvidpres');

$PAGE->requires->js('/local/kaltura/js/jquery.js', true);
$PAGE->requires->js('/local/kaltura/js/swfobject.js', true);
$PAGE->requires->js('/local/kaltura/js/kcwcallback.js', true);

// Check if the user has the capability to manage activites
$context = get_context_instance(CONTEXT_COURSE, $cm->course);
if (has_capability('moodle/course:manageactivities', $context)) {
    $admin_mode = '1';
}

// Try connection
$result = local_kaltura_login(true, '');

if ($result) {
//    if (local_kaltura_has_mobile_flavor_enabled() && local_kaltura_get_enable_html5()) {
//        $uiconf_id = local_kaltura_get_player_uiconf('presentation');
//        $url = new moodle_url(local_kaltura_htm5_javascript_url($uiconf_id));
//        $PAGE->requires->js($url, true);
//        $PAGE->requires->js('/local/kaltura/js/frameapi.js', true);
//    }

    add_to_log($course->id, 'kalvidpres', 'view video resource', 'view.php?id='.$cm->id, $kalvidpres->id, $cm->id);

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}


echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox');

echo $renderer->display_mod_info($kalvidpres->name);

echo format_module_intro('kalvidpres', $kalvidpres, $cm->id);

echo $OUTPUT->box_end();

echo $renderer->video_notification_bar();

if ($result) {
    echo $renderer->player_markup($kalvidpres, $admin_mode);
} else {
    echo $renderer->connection_failure();
}

echo $OUTPUT->footer();
