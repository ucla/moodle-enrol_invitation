<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/filelib.php');

$question = optional_param('question', '', PARAM_CLEAN);
$courseid = optional_param('courseid', 0, PARAM_INT);
$eid = optional_param('eid', 0, PARAM_INT); // qanda entry id
$displayformat = optional_param('displayformat', -1, PARAM_SAFEDIR);

$url = new moodle_url('/mod/qanda/showentry.php');
$url->param('question', $question);
$url->param('courseid', $courseid);
$url->param('eid', $eid);
$url->param('displayformat', $displayformat);
$PAGE->set_url($url);


if ($eid) {
    $entry = $DB->get_record('qanda_entries', array('id' => $eid), '*', MUST_EXIST);
    $qanda = $DB->get_record('qanda', array('id' => $entry->qandaid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('qanda', $qanda->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/qanda:view', $context);
    $entry->qandaname = $qanda->name;
    $entry->cmid = $cm->id;
    $entry->courseid = $cm->course;
    $entries = array($entry);
} else if ($question) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_course_login($course);
    $context = context_module::instance($cm->id);
    require_capability('mod/qanda:view', $context);
    $entries = qanda_get_entries_search($question, $courseid);
} else {
    print_error('invalidelementid');
}

if ($entries) {
    foreach ($entries as $key => $entry) {
        // Need to get the course where the entry is,
        // in order to check for visibility/approve permissions there
        $entrycourse = $DB->get_record('course', array('id' => $entry->courseid), '*', MUST_EXIST);
        $modinfo = get_fast_modinfo($entrycourse);
        // make sure the entry is visible
        if (empty($modinfo->cms[$entry->cmid]->uservisible)) {
            unset($entries[$key]);
            continue;
        }
        // make sure the entry is approved (or approvable by current user)
        if (!$entry->approved and ($USER->id != $entry->userid)) {
            $context = context_module::instance($entry->cmid);
            if (!has_capability('mod/qanda:answer', $context)) {
                unset($entries[$key]);
                continue;
            }
        }

        $context = context_module::instance($entry->cmid);
        $answer = file_rewrite_pluginfile_urls($entry->answer, 'pluginfile.php', $context->id, 'mod_qanda', 'answer', $entry->id);

        $options = new stdClass();
        $options->para = false;
        $options->trusted = $entry->answertrust;
        $options->context = $context;
        $entries[$key]->answer = format_text($answer, $entry->answerformat, $options);

        $entries[$key]->attachments = '';
        if (!empty($entries[$key]->attachment)) {
            $attachments = qanda_print_attachments($entry, $cm, 'html');
            $entries[$key]->attachments = html_writer::tag('p', $attachments);
        }

        $entries[$key]->footer = "<p style=\"text-align:right\">&raquo;&nbsp;<a href=\"$CFG->wwwroot/mod/qanda/view.php?g=$entry->qandaid\">" . format_string($entry->qandaname, true) . "</a></p>";
        add_to_log($entry->courseid, 'qanda', 'view entry', "showentry.php?eid=$entry->id", $entry->id, $entry->cmid);
    }
}

echo $OUTPUT->header();

$result = new stdClass;
$result->success = true;
$result->entries = $entries;
echo json_encode($result);

echo $OUTPUT->footer();

