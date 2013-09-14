<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot . '/course/moodleform_mod.php');

class mod_qanda_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $DB;

        $mform = & $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true);

        $mform->addElement('text', 'entbypage', get_string('entbypage', 'qanda'));
        $mform->setType('entbypage', PARAM_INT); 
        $mform->setDefault('entbypage', 10);
        $mform->addRule('entbypage', null, 'required', null, 'client');
        $mform->addRule('entbypage', null, 'numeric', null, 'client');

        /*
          if (has_capability('mod/qanda:manageentries', context_system::instance())) {
          $mform->addElement('checkbox', 'globalqanda', get_string('isglobal', 'qanda'));
          $mform->addHelpButton('globalqanda', 'isglobal', 'qanda');

          }else{
          $mform->addElement('hidden', 'globalqanda');
          $mform->setType('globalqanda', PARAM_INT);
          }

          $options = array(1=>get_string('mainqanda', 'qanda'), 0=>get_string('secondaryqanda', 'qanda'));
          $mform->addElement('select', 'mainqanda', get_string('qandatype', 'qanda'), $options);
          $mform->addHelpButton('mainqanda', 'qandatype', 'qanda');
          $mform->setDefault('mainqanda', 0);

          $mform->addElement('selectyesno', 'allowduplicatedentries', get_string('allowduplicatedentries', 'qanda'));
          $mform->setDefault('allowduplicatedentries', $CFG->qanda_dupentries);
          $mform->addHelpButton('allowduplicatedentries', 'allowduplicatedentries', 'qanda');

          $mform->addElement('selectyesno', 'allowcomments', get_string('allowcomments', 'qanda'));
          $mform->setDefault('allowcomments', $CFG->qanda_allowcomments);
          $mform->addHelpButton('allowcomments', 'allowcomments', 'qanda');

          $mform->addElement('selectyesno', 'allowprintview', get_string('allowprintview', 'qanda'));
          $mform->setDefault('allowprintview', 0);
          $mform->addHelpButton('allowprintview', 'allowprintview', 'qanda');

          $mform->addElement('selectyesno', 'usedynalink', get_string('usedynalink', 'qanda'));
          $mform->setDefault('usedynalink', $CFG->qanda_linkbydefault);
          $mform->addHelpButton('usedynalink', 'usedynalink', 'qanda');


          $mform->addElement('selectyesno', 'defaultapproval', get_string('defaultapproval', 'qanda'));
          $mform->setDefault('defaultapproval', $CFG->qanda_defaultapproval);
          $mform->addHelpButton('defaultapproval', 'defaultapproval', 'qanda');

         * 
         *      $mform->addElement('selectyesno', 'showspecial', get_string('showspecial', 'qanda'));
          $mform->setDefault('showspecial', 0);
          $mform->addHelpButton('showspecial', 'showspecial', 'qanda');

         * 
         *         $mform->addElement('selectyesno', 'showalphabet', get_string('showalphabet', 'qanda'));
          $mform->setDefault('showalphabet', 0);
          $mform->addHelpButton('showalphabet', 'showalphabet', 'qanda');

          $mform->addElement('selectyesno', 'showall', get_string('showall', 'qanda'));
          $mform->setDefault('showall', 1);
          $mform->addHelpButton('showall', 'showall', 'qanda');
         * 
         */
        if (!isset($CFG->qanda_dupentries)) {
            $CFG->qanda_dupentries = 0;
        }
        if (!isset($CFG->qanda_allowcomments)) {
            $CFG->qanda_allowcomments = 0;
        }
        if (!isset($CFG->qanda_linkbydefault)) {
            $CFG->qanda_linkbydefault = 0;
        }
        if (!isset($CFG->qanda_defaultapproval)) {
            $CFG->qanda_defaultapproval = 0;
        }

        $mform->addElement('hidden', 'mainqanda', 0);
        $mform->setType('mainqanda', PARAM_INT); 
        $mform->addElement('hidden', 'allowduplicatedentries', $CFG->qanda_dupentries);
        $mform->setType('allowduplicatedentries', PARAM_INT); 
        $mform->addElement('hidden', 'allowcomments', $CFG->qanda_allowcomments);
        $mform->setType('allowcomments', PARAM_INT); 
        $mform->addElement('hidden', 'allowprintview', 1);
        $mform->setType('allowprintview', PARAM_INT); 
        $mform->addElement('hidden', 'usedynalink', $CFG->qanda_linkbydefault);
        $mform->setType('usedynalink', PARAM_INT);
        $mform->addElement('hidden', 'defaultapproval', $CFG->qanda_defaultapproval);
        $mform->setType('defaultapproval', PARAM_INT); 
        $mform->addElement('hidden', 'showspecial', 0);
        $mform->setType('showspecial', PARAM_INT); 
        $mform->addElement('hidden', 'showall', 1);
        $mform->setType('showall', PARAM_INT);
        $mform->addElement('hidden', 'globalqanda', 0);
        $mform->setType('globalqanda', PARAM_INT);
        $mform->addElement('hidden', 'showalphabet', 0);
        $mform->setType('showalphabet', PARAM_INT);
        
        //get and update available formats
        $recformats = qanda_get_available_formats();

        $formats = array();

        //Take names
        foreach ($recformats as $format) {
            $formats[$format->name] = get_string('displayformat' . $format->name, 'qanda');
        }

        //Sort it
        asort($formats);
        $mform->addElement('select', 'displayformat', get_string('displayformat', 'qanda'), $formats);
        $mform->setDefault('displayformat', 'faq');
        $mform->addHelpButton('displayformat', 'displayformat', 'qanda');

        $displayformats['default'] = get_string('displayformatdefault', 'qanda');
        $displayformats = array_merge($displayformats, $formats);
        $mform->addElement('select', 'approvaldisplayformat', get_string('approvaldisplayformat', 'qanda'), $displayformats);
        $mform->setDefault('approvaldisplayformat', 'faq');
        $mform->addHelpButton('approvaldisplayformat', 'approvaldisplayformat', 'qanda');



        $mform->addElement('selectyesno', 'editalways', get_string('editalways', 'qanda'));
        $mform->setDefault('editalways', 0);
        $mform->addHelpButton('editalways', 'editalways', 'qanda');

        if ($CFG->enablerssfeeds && isset($CFG->qanda_enablerssfeeds) && $CFG->qanda_enablerssfeeds) {
//-------------------------------------------------------------------------------
            $mform->addElement('header', '', get_string('rss'));
            $choices = array();
            $choices[0] = get_string('none');
            $choices[1] = get_string('withauthor', 'qanda');
            $choices[2] = get_string('withoutauthor', 'qanda');
            $mform->addElement('select', 'rsstype', get_string('rsstype'), $choices);
            $mform->addHelpButton('rsstype', 'rsstype', 'qanda');

            $choices = array();
            $choices[0] = '0';
            $choices[1] = '1';
            $choices[2] = '2';
            $choices[3] = '3';
            $choices[4] = '4';
            $choices[5] = '5';
            $choices[10] = '10';
            $choices[15] = '15';
            $choices[20] = '20';
            $choices[25] = '25';
            $choices[30] = '30';
            $choices[40] = '40';
            $choices[50] = '50';
            $mform->addElement('select', 'rssarticles', get_string('rssarticles'), $choices);
            $mform->addHelpButton('rssarticles', 'rssarticles', 'qanda');
            $mform->disabledIf('rssarticles', 'rsstype', 'eq', 0);
        }

//-------------------------------------------------------------------------------
        //$this->standard_grading_coursemodule_elements();
        //RATINGS
        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function definition_after_data() {
        global $COURSE, $DB;

        parent::definition_after_data();
        $mform = & $this->_form;
        $mainqandael = & $mform->getElement('mainqanda');
        $mainqanda = $DB->get_record('qanda', array('mainqanda' => 1, 'course' => $COURSE->id));
        if ($mainqanda && ($mainqanda->id != $mform->getElementValue('instance'))) {
            //secondary qanda, a main one already exists in this course.
            $mainqandael->setValue(0);
            $mainqandael->freeze();
            $mainqandael->setPersistantFreeze(true);
        } else {
            $mainqandael->unfreeze();
            $mainqandael->setPersistantFreeze(false);
        }
    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionentriesenabled'] =
                !empty($default_values['completionentries']) ? 1 : 0;
        if (empty($default_values['completionentries'])) {
            $default_values['completionentries'] = 1;
        }
    }

    function add_completion_rules() {
        $mform = & $this->_form;

        $group = array();
        $group[] = & $mform->createElement('checkbox', 'completionentriesenabled', '', get_string('completionentries', 'qanda'));
        $group[] = & $mform->createElement('text', 'completionentries', '', array('size' => 3));
        $mform->setType('completionentries', PARAM_INT);
        $mform->addGroup($group, 'completionentriesgroup', get_string('completionentriesgroup', 'qanda'), array(' '), false);
        $mform->disabledIf('completionentries', 'completionentriesenabled', 'notchecked');

        return array('completionentriesgroup');
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionentriesenabled']) && $data['completionentries'] != 0);
    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
        if (empty($data->completionentriesenabled) || !$autocompletion) {
            $data->completionentries = 0;
        }
        return $data;
    }

}

