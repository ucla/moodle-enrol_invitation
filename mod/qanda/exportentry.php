<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = required_param('id', PARAM_INT);          // Entry ID
$confirm = optional_param('confirm', 0, PARAM_BOOL); // export confirmation
$prevmode = required_param('prevmode', PARAM_ALPHA);
$hook = optional_param('hook', '', PARAM_CLEAN);

$url = new moodle_url('/mod/qanda/exportentry.php', array('id' => $id, 'prevmode' => $prevmode));
if ($confirm !== 0) {
    $url->param('confirm', $confirm);
}
if ($hook !== 'ALL') {
    $url->param('hook', $hook);
}
$PAGE->set_url($url);

if (!$entry = $DB->get_record('qanda_entries', array('id' => $id))) {
    print_error('invalidentry');
}

if ($entry->sourceqandaid) {
    //already exported
    if (!$cm = get_coursemodule_from_id('qanda', $entry->sourceqandaid)) {
        print_error('invalidcoursemodule');
    }
    redirect('view.php?id=' . $cm->id . '&amp;mode=entry&amp;hook=' . $entry->id);
}

if (!$cm = get_coursemodule_from_instance('qanda', $entry->qandaid)) {
    print_error('invalidcoursemodule');
}

if (!$qanda = $DB->get_record('qanda', array('id' => $cm->instance))) {
    print_error('invalidid', 'qanda');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course->id, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qanda:export', $context);

$returnurl = "view.php?id=$cm->id&amp;mode=$prevmode&amp;hook=" . urlencode($hook);

if (!$mainqanda = $DB->get_record('qanda', array('course' => $cm->course, 'mainqanda' => 1))) {
    //main qanda not present
    redirect($returnurl);
}

if (!$maincm = get_coursemodule_from_instance('qanda', $mainqanda->id)) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);
$maincontext = context_module::instance($maincm->id);

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}


$strqandas = get_string('modulenameplural', 'qanda');
$entryalreadyexist = get_string('entryalreadyexist', 'qanda');
$entryexported = get_string('entryexported', 'qanda');

if (!$mainqanda->allowduplicatedentries) {
    if ($DB->record_exists_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question', array(
                'qandaid' => $mainqanda->id,
                'question' => textlib::strtolower($entry->question)))) {
        $PAGE->set_title(format_string($qanda->name));
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('errquestionalreadyexists', 'qanda'));
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        die;
    }
}

if (!data_submitted() or !$confirm or !confirm_sesskey()) {
    $PAGE->set_title(format_string($qanda->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();
    echo '<div class="box-align-center">';
    $areyousure = '<h2>' . format_string($entry->question) . '</h2><p align="center">' . get_string('areyousureexport', 'qanda') . '<br /><b>' . format_string($mainqanda->name) . '</b>?';
    $linkyes = 'exportentry.php';
    $linkno = 'view.php';
    $optionsyes = array('id' => $entry->id, 'confirm' => 1, 'sesskey' => sesskey(), 'prevmode' => $prevmode, 'hook' => $hook);
    $optionsno = array('id' => $cm->id, 'mode' => $prevmode, 'hook' => $hook);

    echo $OUTPUT->confirm($areyousure, new moodle_url($linkyes, $optionsyes), new moodle_url($linkno, $optionsno));
    echo '</div>';
    echo $OUTPUT->footer();
    die;
} else {
    $entry->qandaid = $mainqanda->id;
    $entry->sourceqandaid = $qanda->id;

    $DB->update_record('qanda_entries', $entry);

    // move attachments too
    $fs = get_file_storage();

    if ($oldfiles = $fs->get_area_files($context->id, 'mod_qanda', 'attachment', $entry->id)) {
        foreach ($oldfiles as $oldfile) {
            $file_record = new stdClass();
            $file_record->contextid = $maincontext->id;
            $fs->create_file_from_storedfile($file_record, $oldfile);
        }
        $fs->delete_area_files($context->id, 'mod_qanda', 'attachment', $entry->id);
        $entry->attachment = '1';
    } else {
        $entry->attachment = '0';
    }
    $DB->update_record('qanda_entries', $entry);

    redirect($returnurl);
}

