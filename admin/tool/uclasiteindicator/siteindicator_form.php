<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->libdir.'/formslib.php');

class siteindicator_form extends moodleform {
    function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        

//        $mform->addElement('header','siteindicator_types', 'Add indicator types');
        
        $mform->addElement('header','siteindicator_types', 'Indicator role assignments');

        $this->add_action_buttons(true, get_string('requestcourse'));
    }

}