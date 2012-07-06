<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class ucla_modify_coursemenu_form extends moodleform {
    /**
     *  This is going to serve as a proxy for our custom UI.
     **/
    function definition() {
        $mform =& $this->_form;

        $courseid  = $this->_customdata['courseid'];
        $topic = $this->_customdata['topic'];
        $sections   = $this->_customdata['sections'];
        $landing_page = $this->_customdata['landing_page'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->addElement('hidden', 'topic', $topic);

        $mform->addElement('hidden', 'newsections', '',
            array('id' => block_ucla_modify_coursemenu::newnodes_domnode));
        
        $mform->addElement('hidden', 'sectionsorder', '',
            array(
                'id' => block_ucla_modify_coursemenu::sectionsorder_domnode
            ));

        $mform->addElement('hidden', 'landingpage', $landing_page,
            array(
                'id' => block_ucla_modify_coursemenu::landingpage_domnode
            ));

        $mform->addElement('hidden', 'serialized', '',
            array(
                'id' => block_ucla_modify_coursemenu::serialized_domnode
            ));

        $mform->addElement('html', html_writer::tag('div',
            get_string('javascriptrequired', 'group'), array('id' => 
                block_ucla_modify_coursemenu::primary_domnode)));
        
        $mform->addElement('button', 'addsectionbutton', 
            get_string('addnewsection', 'block_ucla_modify_coursemenu'),
            array('id' => block_ucla_modify_coursemenu::add_section_button));
        
        $this->add_action_buttons();
    }
}
    
