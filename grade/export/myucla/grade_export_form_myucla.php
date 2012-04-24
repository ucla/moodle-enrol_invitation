<?php 

require_once $CFG->libdir.'/formslib.php';

class grade_export_form_myucla extends moodleform {
    function definition() {
        global $CFG, $COURSE, $USER;

        $mform =& $this->_form;
        if (isset($this->_customdata)) {  // hardcoding plugin names here is hacky
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        $mform->addElement('header', 'options', get_string('options', 'grades'));

        $mform->addElement('advcheckbox', 'export_feedback', get_string('exportfeedback', 'grades'));
        $mform->setDefault('export_feedback', 1);

        $options = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
        $mform->addElement('select', 'previewrows', get_string('previewrows', 'grades'), $options); 
        
        if (!empty($features['updategradesonly'])) {
            $mform->addElement('advcheckbox', 'updatedgradesonly', get_string('updatedgradesonly', 'grades'));
        }
        /// selections for decimal points and format, MDL-11667, defaults to site settings, if set
        //$default_gradedisplaytype = $CFG->grade_export_displaytype;
        $options = array(GRADE_DISPLAY_TYPE_REAL       => get_string('real', 'grades'),
                         GRADE_DISPLAY_TYPE_PERCENTAGE => get_string('percentage', 'grades'),
                         GRADE_DISPLAY_TYPE_LETTER     => get_string('letter', 'grades'));
        
        /*
        foreach ($options as $key=>$option) {
            if ($key == $default_gradedisplaytype) {
                $options[GRADE_DISPLAY_TYPE_DEFAULT] = get_string('defaultprev', 'grades', $option);
                break;
            }
        }
        */
        $mform->addElement('select', 'display', get_string('gradeexportdisplaytype', 'grades'), $options);  
        //Gradebook Express defaults to letter
        $mform->setDefault('display', GRADE_DISPLAY_TYPE_LETTER);
        //$mform->setDefault('display', $CFG->grade_export_displaytype);
        
        //$default_gradedecimals = $CFG->grade_export_decimalpoints;
        $options = array(0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5);
        $mform->addElement('select', 'decimals', get_string('gradeexportdecimalpoints', 'grades'), $options);
        $mform->setDefault('decimals', $CFG->grade_export_decimalpoints);
        $mform->disabledIf('decimals', 'display', 'eq', GRADE_DISPLAY_TYPE_LETTER);
        /*
        if ($default_gradedisplaytype == GRADE_DISPLAY_TYPE_LETTER) {
            $mform->disabledIf('decimals', 'display', "eq", GRADE_DISPLAY_TYPE_DEFAULT);
        }
        */

        $options = array('txt'=>'Text (tab-delimited)', 'csv'=>'CSV');
        $mform->addElement('select', 'filetype', get_string('filetype', 'gradeexport_myucla'), $options);
        $mform->setDefault('filetype', 'csv');

        if (!empty($features['includeseparator'])) {
            $radio = array();
            $radio[] = &MoodleQuickForm::createElement('radio', 'separator', null, get_string('septab', 'grades'), 'tab');
            $radio[] = &MoodleQuickForm::createElement('radio', 'separator', null, get_string('sepcomma', 'grades'), 'comma');
            $mform->addGroup($radio, 'separator', get_string('separator', 'grades'), ' ', false);
            $mform->setDefault('separator', 'comma');
        }

        if (!empty($CFG->gradepublishing) and !empty($features['publishing'])) {
            $mform->addElement('header', 'publishing', get_string('publishing', 'grades'));
            $options = array(get_string('nopublish', 'grades'), get_string('createnewkey', 'userkey'));
            if ($keys = get_records_select('user_private_key', "script='grade/export' AND instance={$COURSE->id} AND userid={$USER->id}")) {
                foreach ($keys as $key) {
                    $options[$key->value] = $key->value; // TODO: add more details - ip restriction, valid until ??
                }
            }
            $mform->addElement('select', 'key', get_string('userkey', 'userkey'), $options);
            $mform->setHelpButton('key', array('userkey', get_string('userkey', 'userkey'), 'grade'));
            $mform->addElement('static', 'keymanagerlink', get_string('keymanager', 'userkey'),
                    '<a href="'.$CFG->wwwroot.'/grade/export/keymanager.php?id='.$COURSE->id.'">'.get_string('keymanager', 'userkey').'</a>');

            $mform->addElement('text', 'iprestriction', get_string('keyiprestriction', 'userkey'), array('size'=>80));
            $mform->setHelpButton('iprestriction', array('keyiprestriction', get_string('keyiprestriction', 'userkey'), 'userkey'));
            $mform->setDefault('iprestriction', getremoteaddr()); // own IP - just in case somebody does not know what user key is

            $mform->addElement('date_time_selector', 'validuntil', get_string('keyvaliduntil', 'userkey'), array('optional'=>true));
            $mform->setHelpButton('validuntil', array('keyvaliduntil', get_string('keyvaliduntil', 'userkey'), 'userkey'));
            $mform->setDefault('validuntil', time()+3600*24*7); // only 1 week default duration - just in case somebody does not know what user key is

            $mform->disabledIf('iprestriction', 'key', 'noteq', 1);
            $mform->disabledIf('validuntil', 'key', 'noteq', 1);
        }

        $mform->addElement('header', 'gradeitems', get_string('gradeitemsinc', 'grades'));
        
        $switch = grade_get_setting($COURSE->id, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_seq for this course
        $gseq = new grade_seq($COURSE->id, $switch);

        if ($grade_items = $gseq->items) {
            $needs_multiselect = false;
            $coursetotal = get_string('coursetotal', 'grades');
            foreach ($grade_items as $grade_item) {
                if (!empty($features['idnumberrequired']) and empty($grade_item->idnumber)) {
                    $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->get_name(), get_string('noidnumber', 'grades'));
                    $mform->hardFreeze('itemids['.$grade_item->id.']');
                } else {
                    //Don't automatically add
                    //$mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->get_name(), null, array('group' => 1));
                    if ($grade_item->get_name() !== $coursetotal) {
                        //Don't add these, they don't make sense for Gradebook Express
                        //$mform->setDefault('itemids['.$grade_item->id.']', 0);
                    }
                    else {
                        //Course total (final grade)
                        $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->get_name(), null, array('group' => 1));
                        $mform->setDefault('itemids['.$grade_item->id.']', 1);
                    }
                    $needs_multiselect = true;
                }
            }
            
            if ($needs_multiselect) {
                //Doesn't make sense for Gradebook Express (only 1 item)
                //$this->add_checkbox_controller(1, null, null, 1); // 1st argument is group name, 2nd is link text, 3rd is attributes and 4th is original value
            }
        }
        $mform->addElement('static', 'expresswarning', '<br />Note: Express only allows '.get_string('coursetotal', 'grades'));
        
        $mform->addElement('hidden', 'id', $COURSE->id);
        $this->add_action_buttons(false, get_string('submit'));

    }
}
