<?php
require_once($CFG->libdir.'/formslib.php');
// submit a class to be built
class class_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        
        $selected_term = optional_param('term',NULL,PARAM_CLEAN) ? optional_param('term',NULL,PARAM_CLEAN) : $CFG->classrequestor_selected_term;
        
        $pulldown_term = array();

        foreach ($CFG->classrequestor_terms as $term) {
            $pulldown_term[$term]= $term;
        }

        $mform->addElement('header', 'srsform', '');
        

        $oneline=array();
        $oneline[] =& $mform->createElement('static', 'termlabel', null, '<label>TERM: </label>');
        $selectterm =& $mform->createElement('select', 'term', null, $pulldown_term);
        $oneline[] = $selectterm;
        $attrib=array('size'=>'25');
        $oneline[] =& $mform->createElement('static', 'srslabel', null, '<label>SRS: </label>');
        $oneline[] =& $mform->createElement('text', 'srs', null, $attrib);
        $oneline[] =& $mform->createElement('submit', 'submit', 'Build Course ');
        
        // put these elements in one group so that they appear on the same line
        // the last element is true. this way it can be refered in setDefaults
        $mform->addGroup($oneline, 'group1', null, ' ', true);
        

        $mform->setDefaults(array('group1'=> array('term'=>$selected_term)));
        
        $mform->addElement('hidden','action','fillform');
        $mform->setType('action', PARAM_TEXT);
        
        $mform->addGroupRule('group1', array('srs' => array(array('SRS is 9 digits', 'regex', '/^[0-9]{9}$/', 'client'))));
        
        $mform->addElement('html', '</fieldset>');
    }
}

?>
    