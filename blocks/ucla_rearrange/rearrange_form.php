<?php 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class ucla_rearrange_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $course_id = $this->_customdata['course_id'];
        $sections = $this->_customdata['sections'];

        $mform->addElement('hidden', 'course_id', $course_id);
        $mform->addElement('hidden', 'serialized', '',
            array('id' => 'serialized'));
        $mform->setType('serialized', PARAM_RAW);

        foreach ($sections as $section) {
            $fieldname = 'serialized-section-' . $section;
            $mform->addElement('hidden', $fieldname,
                '', array('id' => 'serialized-' . $section));
            $mform->setType($fieldname, PARAM_RAW);
        }

        $eall = get_string('allcollapse', 'block_ucla_rearrange');

        //$this->add_action_buttons();
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Save Changes', array('onclick' => 'M.block_ucla_rearrange.not_saved = false;'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        
        $mform->addElement('header');

        $classset = array('class' => 'expandall');
        
        $mform->addElement('button', 'mass-expander-top', $eall, $classset);

        $mform->addElement('html', html_writer::tag('div',
            get_string('javascriptrequired', 'group'), array('id' => 
                block_ucla_rearrange::primary_domnode)));

        $mform->addElement('button', 'mass-expander-bot', $eall, $classset);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Save Changes', array('onclick' => 'M.block_ucla_rearrange.not_saved = false;'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}

