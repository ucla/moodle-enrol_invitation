<?php

/*
 * TinyMCE settings:
 * bold,italic,underline,sub,sup,|,justifyleft,justifycenter,justifyright, |, bullist,numlist,outdent,indent,|,link,unlink,|,image,charmap,table,|,code
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot . '/lib/formslib.php');

class mod_qanda_entry_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $currententry = $this->_customdata['current'];
        $qanda = $this->_customdata['qanda'];
        $cm = $this->_customdata['cm'];
        $answeroptions = $this->_customdata['answeroptions'];

        $questionoptions = $this->_customdata['questionoptions'];
        $attachmentoptions = $this->_customdata['attachmentoptions'];

        $context = context_module::instance($cm->id);
        // Prepare format_string/text options
        $fmtoptions = array(
            'context' => $context);


//start div.question-editor

        $mform->addElement('html', '<div class="question-editor">');

        //start div.question-editor div.question    
        $mform->addElement('html', '<div class="question">');

        //start div.question-editor div.question div.question-label
        $mform->addElement('html', '<div class="question-label">');
        $mform->addElement('html', get_string('question', 'qanda'));
        $mform->addElement('html', '</div>');
        //end div.question-editor div.question div.question-label
        //start div.question-editor div.question div.question-text
        $mform->addElement('html', '<div class="question-text">');
        $mform->addElement('editor', 'question_editor', get_string('question', 'qanda'), null, $questionoptions); //
        $mform->setType('question_editor', PARAM_RAW);
        $mform->addRule('question_editor', 'Required field', 'required', null, 'client');

        $mform->addElement('html', '</div>');
        //end div.question-editor div.question div.question-text

        $mform->addElement('html', '</div>');
        //end div.question-editor div.question
        $mform->addElement('html', '</div>');
//end div.question-editor

        if (has_capability('mod/qanda:manageentries', $context)) {

//start div.editor-admin
            $mform->addElement('html', '<div class="editor-admin">');
            //start div.editor-admin div.answer 
            $mform->addElement('html', '<div class="answer">');

            //start div.editor-admin div.answer div.answer-label    
            $mform->addElement('html', '<div class="answer-label">');
            $mform->addElement('html', get_string('answer', 'qanda'));
            $mform->addElement('html', '</div>');
            //end div.editor-admin div.answer div.answer-label  
            //start div.editor-admin div.answer div.answer-text 
            $mform->addElement('html', '<div class="answer-text">');
            $mform->addElement('editor', 'answer_editor', get_string('answer', 'qanda'), null, $answeroptions); //, null, $answeroptions
            $mform->setType('answer_editor', PARAM_RAW);
            //$mform->addRule('answer_editor', get_string('required'), 'required', null, 'client');
            $mform->addElement('html', '</div>');
            //end div.editor-admin div.answer div.answer-text 
            $mform->addElement('html', '</div>');
            //end div.editor-admin div.answer 
        }
        $this->add_action_buttons();
        $mform->addElement('html', '</div>');

        if ($categories = $DB->get_records_menu('qanda_categories', array('qandaid' => $qanda->id), 'name ASC', 'id, name')) {
            foreach ($categories as $id => $name) {
                $categories[$id] = format_string($name, true, $fmtoptions);
            }
            $categories = array(0 => get_string('notcategorised', 'qanda')) + $categories;
            $categoriesEl = $mform->addElement('select', 'categories', get_string('categories', 'qanda'), $categories);
            $categoriesEl->setMultiple(true);
            $categoriesEl->setSize(5);
        }

        //$mform->addElement('textarea', 'aliases', get_string('aliases', 'qanda'), 'rows="2" cols="40"');
        $mform->addElement('hidden', 'aliases', get_string('aliases', 'qanda'), 'rows="2" cols="40"');
        $mform->setType('aliases', PARAM_TEXT);
        //$mform->addHelpButton('aliases', 'aliases', 'qanda');
        //$mform->addElement('filemanager', 'attachment_filemanager', get_string('attachment', 'qanda'), null, $attachmentoptions);
        //$mform->addHelpButton('attachment_filemanager', 'attachment', 'qanda');


        if (!$qanda->usedynalink) {
            /* $mform->addElement('hidden', 'usedynalink', $CFG->qanda_linkentries);
              $mform->setType('usedynalink', PARAM_INT);
              $mform->addElement('hidden', 'casesensitive', $CFG->qanda_casesensitive);
              $mform->setType('casesensitive', PARAM_INT);
              $mform->addElement('hidden', 'fullmatch', $CFG->qanda_fullmatch);
              $mform->setType('fullmatch', PARAM_INT); */
        } else {
//-------------------------------------------------------------------------------
            $mform->addElement('header', 'linkinghdr', get_string('linking', 'qanda'));

            $mform->addElement('checkbox', 'usedynalink', get_string('entryusedynalink', 'qanda'));
            $mform->addHelpButton('usedynalink', 'entryusedynalink', 'qanda');
            $mform->setDefault('usedynalink', $CFG->qanda_linkentries);

            $mform->addElement('checkbox', 'casesensitive', get_string('casesensitive', 'qanda'));
            $mform->addHelpButton('casesensitive', 'casesensitive', 'qanda');
            $mform->disabledIf('casesensitive', 'usedynalink');
            $mform->setDefault('casesensitive', $CFG->qanda_casesensitive);

            $mform->addElement('checkbox', 'fullmatch', get_string('fullmatch', 'qanda'));
            $mform->addHelpButton('fullmatch', 'fullmatch', 'qanda');
            $mform->disabledIf('fullmatch', 'usedynalink');
            $mform->setDefault('fullmatch', $CFG->qanda_fullmatch);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

//-------------------------------------------------------------------------------
//end div.editor-admin OR end div.editor-not-admin
//-------------------------------------------------------------------------------
        //var_dump($currententry);
        $this->set_data($currententry);
        //var_dump($mform);
    }

    function validation($data, $files) {
        global $CFG, $USER, $DB;
        $errors = parent::validation($data, $files);

        $qanda = $this->_customdata['qanda'];
        $cm = $this->_customdata['cm'];
        $context = context_module::instance($cm->id);

        $id = (int) $data['id'];
        //$data['question'] = $data['question_editor']['text']; //trim($data['question']);
        $temp_question = $data['question_editor']['text'];
        if ($id) {
            //We are updating an entry, so we compare current session user with
            //existing entry user to avoid some potential problems if secureforms=off
            //Perhaps too much security? Anyway thanks to skodak (Bug 1823)
            $old = $DB->get_record('qanda_entries', array('id' => $id));
            $ineditperiod = ((time() - $old->timecreated < $CFG->maxeditingtime) || $qanda->editalways);
            if ((!$ineditperiod || $USER->id != $old->userid) and !has_capability('mod/qanda:manageentries', $context)) {
                if ($USER->id != $old->userid) {
                    $errors['question'] = get_string('errcannoteditothers', 'qanda');
                } elseif (!$ineditperiod) {
                    $errors['question'] = get_string('erredittimeexpired', 'qanda');
                }
            }
            if ($old->approved and !has_capability('mod/qanda:manageentries', $context)) {
                $errors['question'] = get_string('erralreadyanswered', 'qanda');
            }
            if (!$qanda->allowduplicatedentries) {
                if ($DB->record_exists_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question AND id != :id', array(
                            'qandaid' => $qanda->id,
                            'question' => textlib::strtolower($temp_question),
                            'id' => $id))) {
                    $result = $DB->get_record_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question AND id != :id', array(
                        'qandaid' => $qanda->id,
                        'question' => textlib::strtolower($temp_question),
                        'id' => $id), 'id,approved');
                    if ($result->approved == '1') {
                        $link = "view.php?id=$cm->id&mode=entry&hook=" . urlencode($result->id);
                        $link = html_writer::link($link, get_string("qanda_link", "qanda"), array('title' => get_string("view")));
                    } else {
                        $link = get_string('qanda_questionnotapproved', 'qanda');
                    }
                    $errors['question_editor'] = get_string('errquestionalreadyexists', 'qanda') . ' ' . $link . '<br />';
                }
            }
        } else {
            if (!$qanda->allowduplicatedentries) {
                if ($DB->record_exists_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question', array(
                            'qandaid' => $qanda->id,
                            'question' => textlib::strtolower($temp_question)))) {
                    $result = $DB->get_record_select('qanda_entries', 'qandaid = :qandaid AND LOWER(question) = :question AND id != :id', array(
                        'qandaid' => $qanda->id,
                        'question' => textlib::strtolower($temp_question),
                        'id' => $id), 'id,approved');
                    if ($result->approved == '1') {
                        $link = "view.php?id=$cm->id&mode=entry&hook=" . urlencode($result->id);
                        $link = html_writer::link($link, get_string("qanda_link", "qanda"), array('title' => get_string("view")));
                    } else {
                        $link = get_string('qanda_questionnotapproved', 'qanda');
                    }
                    $errors['question_editor'] = get_string('errquestionalreadyexists', 'qanda') . ' ' . $link . '<br />';
                }
            }
        }

        return $errors;
    }

}

