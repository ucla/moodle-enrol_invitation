<?php 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class ucla_rearrange_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $courseid  = $this->_customdata['courseid'];
        $section      = $this->_customdata['section'];        
        $sections   = $this->_customdata['sections'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        
        $mform->addElement('hidden', 'section', $section);        
        $mform->setType('section', PARAM_INT);
        
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

        // First set of submit buttons
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton1', get_string('savechanges'), array('disabled' => 'disabled'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar1', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar1');


        $mform->addElement('header', 'header', get_string('sections'));

        $classset = array('class' => 'expandall');
        
        $mform->addElement('button', 'mass-expander-top', $eall, $classset);

        $mform->addElement('html', html_writer::tag('div',
            get_string('javascriptrequired', 'group'), array('id' => 
                block_ucla_rearrange::primary_domnode)));

        $mform->addElement('button', 'mass-expander-bot', $eall, $classset);

        // Second set of submit buttons
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', get_string('savechanges'), array('disabled' => 'disabled'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar2', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar2');
    }
    
    /**
     * If user hits cancel, rather than reset the form, redirect them to the
     * section that they were previously on. 
     */
    function is_cancelled() {
        $result = parent::is_cancelled();
        
        if (!empty($result)) {
            $courseid = $this->_customdata['courseid'];
            $section  = $this->_customdata['section'];             
            
            redirect(new moodle_url('/course/view.php',
                array('id' => $courseid, 'section' => $section)));
        }
    }
}
