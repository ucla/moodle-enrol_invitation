<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once('edit_form.php');

$cmid = required_param('cmid', PARAM_INT);            // Course Module ID
$id = optional_param('id', 0, PARAM_INT);           // EntryID

if (!$cm = get_coursemodule_from_id('qanda', $cmid)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/qanda:view', $context);

if (!$qanda = $DB->get_record('qanda', array('id' => $cm->instance))) {
    print_error('invalidid', 'qanda');
}

$url = new moodle_url('/mod/qanda/edit.php', array('cmid' => $cm->id));
if (!empty($id)) {
    $url->param('id', $id);
}
$PAGE->set_url($url);

if ($id) { // if entry is specified
    if (isguestuser()) {

        print_error('guestnoedit', 'qanda', "$CFG->wwwroot/mod/qanda/view.php?id=$cmid");
    }

    if (!$entry = $DB->get_record('qanda_entries', array('id' => $id, 'qandaid' => $qanda->id))) {
        print_error('invalidentry');
    }

    $ineditperiod = ((time() - $entry->timecreated < $CFG->maxeditingtime) || $qanda->editalways);
    if (!has_capability('mod/qanda:manageentries', $context) and !($entry->userid == $USER->id and ($ineditperiod and has_capability('mod/qanda:write', $context)))) {
        if ($USER->id != $fromdb->userid) {
            print_error('errcannoteditothers', 'qanda', "view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
        } elseif (!$ineditperiod) {
            print_error('erredittimeexpired', 'qanda', "view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
        }
    }

    //prepare extra data
    if ($aliases = $DB->get_records_menu("qanda_alias", array("entryid" => $id), '', 'id, alias')) {
        $entry->aliases = implode("\n", $aliases) . "\n";
    }
    if ($categoriesarr = $DB->get_records_menu("qanda_entries_categories", array('entryid' => $id), '', 'id, categoryid')) {
        // this fetches cats from both main and secondary qanda
        $entry->categories = array_values($categoriesarr);
    }
} else { // new entry
    require_capability('mod/qanda:write', $context);

    $entry = new stdClass();
    $entry->id = null;
}

$maxfiles = 99;
$maxbytes = $course->maxbytes;

$answeroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $context);
$questionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $context);
$attachmentoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);

$entry = file_prepare_standard_editor($entry, 'question', $questionoptions, $context, 'mod_qanda', 'question', $entry->id);

$entry = file_prepare_standard_editor($entry, 'answer', $answeroptions, $context, 'mod_qanda', 'answer', $entry->id);
$entry = file_prepare_standard_filemanager($entry, 'attachment', $attachmentoptions, $context, 'mod_qanda', 'attachment', $entry->id);

$entry->cmid = $cm->id;


// create form and set initial data
$mform = new mod_qanda_entry_form(null, array('current' => $entry, 'cm' => $cm, 'qanda' => $qanda,
            'answeroptions' => $answeroptions, 'questionoptions' => $questionoptions, 'attachmentoptions' => $attachmentoptions));

if ($mform->is_cancelled()) {
    if ($id) {
        redirect("view.php?id=$cm->id&mode=date&hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }
} else if ($entry = $mform->get_data()) {
    $timenow = time();

    $categories = empty($entry->categories) ? array() : $entry->categories;
    unset($entry->categories);
    $aliases = trim($entry->aliases);
    unset($entry->aliases);

    global $USER;
    if (empty($entry->id)) {
        $entry->qandaid = $qanda->id;
        $entry->timecreated = $timenow;
        $entry->userid = $USER->id;
        $entry->username = $USER->username;
        $entry->useremail = $USER->email;
        $entry->timecreated = $timenow;
        $entry->sourceqandaid = 0;
        $entry->teacherentry = has_capability('mod/qanda:manageentries', $context);
    }

    $entry->question = ' '; //$entry->question_editor;//$entry->question_editor["text"];
    $entry->questionformat = FORMAT_HTML; // updated later
    $entry->questiontrust = 0;
    $entry->answer = ' ';          // updated later
    $entry->answerformat = FORMAT_HTML; // updated later
    $entry->answertrust = 0;           // updated later
    $entry->timemodified = $timenow;
    $entry->approved = 0;
    $entry->usedynalink = isset($entry->usedynalink) ? $entry->usedynalink : 0;
    $entry->casesensitive = isset($entry->casesensitive) ? $entry->casesensitive : 0;
    $entry->fullmatch = isset($entry->fullmatch) ? $entry->fullmatch : 0;

    if ($qanda->defaultapproval or has_capability('mod/qanda:answer', $context)) {
        $entry->approved = 1;
    }

    if (empty($entry->id)) {
        //new entry
        $entry->id = $DB->insert_record('qanda_entries', $entry);

        // Update completion state
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $qanda->completionentries && $entry->approved) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }

        add_to_log($course->id, "qanda", "add entry", "view.php?id=$cm->id&amp;mode=entry&amp;hook=$entry->id", $entry->id, $cm->id);
    } else {
        //existing entry
        $DB->update_record('qanda_entries', $entry);
        add_to_log($course->id, "qanda", "update entry", "view.php?id=$cm->id&amp;mode=entry&amp;hook=$entry->id", $entry->id, $cm->id);
    }

    // save and relink embedded images and save attachments

    $entry = file_postupdate_standard_editor($entry, 'question', $questionoptions, $context, 'mod_qanda', 'question', $entry->id);

    if (isset($entry->{'answer_editor'})) {

        $entry = file_postupdate_standard_editor($entry, 'answer', $answeroptions, $context, 'mod_qanda', 'answer', $entry->id);
    }
    //$entry = file_postupdate_standard_filemanager($entry, 'attachment', $attachmentoptions, $context, 'mod_qanda', 'attachment', $entry->id);
    // store the updated  values
    $DB->update_record('qanda_entries', $entry);

    //refetch complete entry
    $entry = $DB->get_record('qanda_entries', array('id' => $entry->id));

    // update entry categories
    $DB->delete_records('qanda_entries_categories', array('entryid' => $entry->id));

    if (!empty($categories) and array_search(0, $categories) === false) {
        foreach ($categories as $catid) {
            $newcategory = new stdClass();
            $newcategory->entryid = $entry->id;
            $newcategory->categoryid = $catid;
            $DB->insert_record('qanda_entries_categories', $newcategory, false);
        }
    }

    // update aliases
    $DB->delete_records('qanda_alias', array('entryid' => $entry->id));
    if ($aliases !== '') {
        $aliases = explode("\n", $aliases);
        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if ($alias !== '') {
                $newalias = new stdClass();
                $newalias->entryid = $entry->id;
                $newalias->alias = $alias;
                $DB->insert_record('qanda_alias', $newalias, false);
            }
        }
    }

    redirect("view.php?id=$cm->id&mode=date&hook=$entry->id");
}

if (!empty($id)) {
    $PAGE->navbar->add(get_string('edit'));
}

$PAGE->set_title(format_string($qanda->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($qanda->name));

$mform->display();

echo $OUTPUT->footer();

