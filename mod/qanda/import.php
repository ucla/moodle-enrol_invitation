<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once("$CFG->dirroot/course/lib.php");
require_once('import_form.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

$mode = optional_param('mode', 'letter', PARAM_ALPHA);
$hook = optional_param('hook', 'ALL', PARAM_ALPHANUM);

$url = new moodle_url('/mod/qanda/import.php', array('id' => $id));
if ($mode !== 'letter') {
    $url->param('mode', $mode);
}
if ($hook !== 'ALL') {
    $url->param('hook', $hook);
}
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('qanda', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (!$qanda = $DB->get_record("qanda", array("id" => $cm->instance))) {
    print_error('invalidid', 'qanda');
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qanda:import', $context);

$strqandas = get_string("modulenameplural", "qanda");
$strqanda = get_string("modulename", "qanda");
$strallcategories = get_string("allcategories", "qanda");
$straddentry = get_string("addentry", "qanda");
$strnoentries = get_string("noentries", "qanda");
$strsearchinanswer = get_string("searchinanswer", "qanda");
$strsearch = get_string("search");
$strimportentries = get_string('importentriesfromxml', 'qanda');

$PAGE->navbar->add($strimportentries);
$PAGE->set_title(format_string($qanda->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($strimportentries);

$form = new mod_qanda_import_form();

if (!$data = $form->get_data()) {
    echo $OUTPUT->box_start('qanda-display generalbox');
    // display upload form
    $data = new stdClass();
    $data->id = $id;
    $form->set_data($data);
    $form->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

$result = $form->get_file_content('file');

if (empty($result)) {
    echo $OUTPUT->box_start('qanda-display generalbox');
    echo $OUTPUT->continue_button('import.php?id=' . $id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die();
}

if ($xml = qanda_read_imported_file($result)) {
    $importedentries = 0;
    $importedcats = 0;
    $entriesrejected = 0;
    $rejections = '';

    if ($data->dest == 'newqanda') {
        // If the user chose to create a new qanda
        $xmlqanda = $xml['QANDA']['#']['INFO'][0]['#'];

        if ($xmlqanda['NAME'][0]['#']) {
            $qanda = new stdClass();
            $qanda->name = ($xmlqanda['NAME'][0]['#']);
            $qanda->course = $course->id;
            $qanda->globalqanda = ($xmlqanda['GLOBALQANDA'][0]['#']);

            $qanda->intro = ($xmlqanda['INTRO'][0]['#']);
            $qanda->introformat = isset($xmlqanda['INTROFORMAT'][0]['#']) ? $xmlqanda['INTROFORMAT'][0]['#'] : FORMAT_MOODLE;
            $qanda->showspecial = ($xmlqanda['SHOWSPECIAL'][0]['#']);
            $qanda->showalphabet = ($xmlqanda['SHOWALPHABET'][0]['#']);
            $qanda->showall = ($xmlqanda['SHOWALL'][0]['#']);
            $qanda->timecreated = time();
            $qanda->timemodified = time();
            $qanda->cmidnumber = $cm->idnumber;

            // Setting the default values if no values were passed
            if (isset($xmlqanda['ENTBYPAGE'][0]['#'])) {
                $qanda->entbypage = ($xmlqanda['ENTBYPAGE'][0]['#']);
            } else {
                $qanda->entbypage = $CFG->qanda_entbypage;
            }
            if (isset($xmlqanda['MAINQANDA'][0]['#'])) {
                $qanda->mainqanda = ($xmlqanda['MAINQANDA'][0]['#']);
            } else {
                $qanda->mainqanda = 0;
            }
            if (isset($xmlqanda['ALLOWDUPLICATEDENTRIES'][0]['#'])) {
                $qanda->allowduplicatedentries = ($xmlqanda['ALLOWDUPLICATEDENTRIES'][0]['#']);
            } else {
                $qanda->allowduplicatedentries = $CFG->qanda_dupentries;
            }
            if (isset($xmlqanda['DISPLAYFORMAT'][0]['#'])) {
                $qanda->displayformat = ($xmlqanda['DISPLAYFORMAT'][0]['#']);
            } else {
                $qanda->displayformat = 2;
            }
            if (isset($xmlqanda['ALLOWCOMMENTS'][0]['#'])) {
                $qanda->allowcomments = ($xmlqanda['ALLOWCOMMENTS'][0]['#']);
            } else {
                $qanda->allowcomments = $CFG->qanda_allowcomments;
            }
            if (isset($xmlqanda['USEDYNALINK'][0]['#'])) {
                $qanda->usedynalink = ($xmlqanda['USEDYNALINK'][0]['#']);
            } else {
                $qanda->usedynalink = $CFG->qanda_linkentries;
            }
            if (isset($xmlqanda['DEFAULTAPPROVAL'][0]['#'])) {
                $qanda->defaultapproval = ($xmlqanda['DEFAULTAPPROVAL'][0]['#']);
            } else {
                $qanda->defaultapproval = $CFG->qanda_defaultapproval;
            }

            // Include new qanda and return the new ID
            if (!$qanda->id = qanda_add_instance($qanda)) {
                echo $OUTPUT->notification("Error while trying to create the new qanda.");
                qanda_print_tabbed_table_end();
                echo $OUTPUT->footer();
                exit;
            } else {
                //The instance has been created, so lets do course_modules
                //and course_sections
                $mod = new stdClass();
                if (isset($course->groupmode)) {
                    $mod->groupmode = $course->groupmode;  /// Default groupmode the same as course
                }
                if (isset($qanda->id)) {
                    $mod->instance = $qanda->id;
                }

                // course_modules and course_sections each contain a reference
                // to each other, so we have to update one of them twice.

                if (!$currmodule = $DB->get_record("modules", array("name" => 'qanda'))) {
                    print_error('modulenotexist', 'debug', '', 'qanda');
                }
                $mod->module = $currmodule->id;
                $mod->course = $course->id;
                $mod->modulename = 'qanda';
                $mod->section = 0;

                if (!$mod->coursemodule = add_course_module($mod)) {
                    print_error('cannotaddcoursemodule');
                }
                // After Moodle 2.3 the add_mod_to_section() function was deprecated.
                $sectionid = course_add_cm_to_section($course, $mod->coursemodule, 0);
                
                //We get the section's visible field status
                $visible = $DB->get_field("course_sections", "visible", array("id" => $sectionid));

                $DB->set_field("course_modules", "visible", $visible, array("id" => $mod->coursemodule));


                // mdl_course_modules.section was set to 0, instead of the index of mdl_course_sections.section=0
                //Set the proper course_modules.section with the retrieved $sectionid (the index of the 0 section from  mdl_course_sections for that course)
                $DB->set_field("course_modules", "section", $sectionid, array("id" => $mod->coursemodule));

                add_to_log($course->id, "course", "add mod", "../mod/$mod->modulename/view.php?id=$mod->coursemodule", "$mod->modulename $mod->instance");
                add_to_log($course->id, $mod->modulename, "add", "view.php?id=$mod->coursemodule", "$mod->instance", $mod->coursemodule);

                rebuild_course_cache($course->id);

                echo $OUTPUT->box(get_string("newqandacreated", "qanda"), 'generalbox box-align-center boxwidthnormal');
            }
        } else {
            echo $OUTPUT->notification("Error while trying to create the new qanda.");
            echo $OUTPUT->footer();
            exit;
        }
    }

    $xmlentries = $xml['QANDA']['#']['INFO'][0]['#']['ENTRIES'][0]['#']['ENTRY'];
    $sizeofxmlentries = sizeof($xmlentries);
    for ($i = 0; $i < $sizeofxmlentries; $i++) {
        // Inserting the entries
        $xmlentry = $xmlentries[$i];
        $newentry = new stdClass();
        $newentry->question = trim($xmlentry['#']['QUESTION'][0]['#']);
        $newentry->answer = trusttext_strip($xmlentry['#']['ANSWER'][0]['#']);
        if (isset($xmlentry['#']['CASESENSITIVE'][0]['#'])) {
            $newentry->casesensitive = $xmlentry['#']['CASESENSITIVE'][0]['#'];
        } else {
            $newentry->casesensitive = $CFG->qanda_casesensitive;
        }

        $permissiongranted = 1;
        if ($newentry->question and $newentry->answer) {
            if (!$qanda->allowduplicatedentries) {
                // checking if the entry is valid (checking if it is duplicated when should not be)
                if ($newentry->casesensitive) {
                    $dupentry = $DB->record_exists_select('qanda_entries', 'qandaid = :qandaid AND question = :question', array(
                        'qandaid' => $qanda->id,
                        'question' => $newentry->question));
                } else {
                    $dupentry = $DB->record_exists_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question', array(
                        'qandaid' => $qanda->id,
                        'question' => textlib::strtolower($newentry->question)));
                }
                if ($dupentry) {
                    $permissiongranted = 0;
                }
            }
        } else {
            $permissiongranted = 0;
        }
        if ($permissiongranted) {
            $newentry->qandaid = $qanda->id;
            $newentry->sourceqandaid = 0;
            $newentry->approved = 1;
            $newentry->userid = $USER->id;
            $newentry->teacherentry = 1;
            $newentry->questionformat = $xmlentry['#']['QUESTIONFORMAT'][0]['#'];
            $newentry->answerformat = $xmlentry['#']['ANSWERFORMAT'][0]['#'];
            $newentry->timecreated = time();
            $newentry->timemodified = time();

            // Setting the default values if no values were passed
            if (isset($xmlentry['#']['USEDYNALINK'][0]['#'])) {
                $newentry->usedynalink = $xmlentry['#']['USEDYNALINK'][0]['#'];
            } else {
                $newentry->usedynalink = $CFG->qanda_linkentries;
            }
            if (isset($xmlentry['#']['FULLMATCH'][0]['#'])) {
                $newentry->fullmatch = $xmlentry['#']['FULLMATCH'][0]['#'];
            } else {
                $newentry->fullmatch = $CFG->qanda_fullmatch;
            }

            $newentry->id = $DB->insert_record("qanda_entries", $newentry);
            $importedentries++;

            $xmlaliases = @$xmlentry['#']['ALIASES'][0]['#']['ALIAS']; // ignore missing ALIASES
            $sizeofxmlaliases = sizeof($xmlaliases);
            for ($k = 0; $k < $sizeofxmlaliases; $k++) {
                /// Importing aliases
                $xmlalias = $xmlaliases[$k];
                $aliasname = $xmlalias['#']['NAME'][0]['#'];

                if (!empty($aliasname)) {
                    $newalias = new stdClass();
                    $newalias->entryid = $newentry->id;
                    $newalias->alias = trim($aliasname);
                    $newalias->id = $DB->insert_record("qanda_alias", $newalias);
                }
            }

            if (!empty($data->catsincl)) {
                // If the categories must be imported...
                $xmlcats = @$xmlentry['#']['CATEGORIES'][0]['#']['CATEGORY']; // ignore missing CATEGORIES
                $sizeofxmlcats = sizeof($xmlcats);
                for ($k = 0; $k < $sizeofxmlcats; $k++) {
                    $xmlcat = $xmlcats[$k];

                    $newcat = new stdClass();
                    $newcat->name = $xmlcat['#']['NAME'][0]['#'];
                    $newcat->usedynalink = $xmlcat['#']['USEDYNALINK'][0]['#'];
                    if (!$category = $DB->get_record("qanda_categories", array("qandaid" => $qanda->id, "name" => $newcat->name))) {
                        // Create the category if it does not exist
                        $category = new stdClass();
                        $category->name = $newcat->name;
                        $category->qandaid = $qanda->id;
                        $category->id = $DB->insert_record("qanda_categories", $category);
                        $importedcats++;
                    }
                    if ($category) {
                        // inserting the new relation
                        $entrycat = new stdClass();
                        $entrycat->entryid = $newentry->id;
                        $entrycat->categoryid = $category->id;
                        $DB->insert_record("qanda_entries_categories", $entrycat);
                    }
                }
            }
        } else {
            $entriesrejected++;
            if ($newentry->question and $newentry->answer) {
                // add to exception report (duplicated entry))
                $rejections .= "<tr><td>$newentry->question</td>" .
                        "<td>" . get_string("duplicateentry", "qanda") . "</td></tr>";
            } else {
                // add to exception report (no question or answer found))
                $rejections .= "<tr><td>---</td>" .
                        "<td>" . get_string("noquestionfound", "qanda") . "</td></tr>";
            }
        }
    }
    // processed entries
    echo $OUTPUT->box_start('qanda-display generalbox');
    echo '<table class="qanda-import-export">';
    echo '<tr>';
    echo '<td width="50%" align="right">';
    echo get_string("totalentries", "qanda");
    echo ':</td>';
    echo '<td width="50%" align="left">';
    echo $importedentries + $entriesrejected;
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td width="50%" align="right">';
    echo get_string("importedentries", "qanda");
    echo ':</td>';
    echo '<td width="50%" align="left">';
    echo $importedentries;
    if ($entriesrejected) {
        echo ' <small>(' . get_string("rejectedentries", "qanda") . ": $entriesrejected)</small>";
    }
    echo '</td>';
    echo '</tr>';
    if (!empty($data->catsincl)) {
        echo '<tr>';
        echo '<td width="50%" align="right">';
        echo get_string("importedcategories", "qanda");
        echo ':</td>';
        echo '<td width="50%">';
        echo $importedcats;
        echo '</td>';
        echo '</tr>';
    }
    echo '</table><hr />';

    // rejected entries
    if ($rejections) {
        echo $OUTPUT->heading(get_string("rejectionrpt", "qanda"), 4);
        echo '<table class="qanda-import-export">';
        echo $rejections;
        echo '</table><hr />';
    }
    // Print continue button, based on results
    if ($importedentries) {
        echo $OUTPUT->continue_button('view.php?id=' . $id);
    } else {
        echo $OUTPUT->continue_button('import.php?id=' . $id);
    }
    echo $OUTPUT->box_end();
} else {
    echo $OUTPUT->box_start('qanda-display generalbox');
    echo get_string('errorparsingxml', 'qanda');
    echo $OUTPUT->continue_button('import.php?id=' . $id);
    echo $OUTPUT->box_end();
}

/// Finish the page
echo $OUTPUT->footer();
