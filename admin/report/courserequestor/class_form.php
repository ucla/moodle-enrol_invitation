<?php
require_once($CFG->libdir.'/formslib.php');
// submit a class to be built
class class_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        
        $selected_term = optional_param('term',NULL,PARAM_ALPHANUM) ? 
        optional_param('term',NULL,PARAM_ALPHANUM) : $CFG->classrequestor_selected_term;
        
        $pulldown_term = array();

        foreach ($CFG->classrequestor_terms as $term) {
            $pulldown_term[$term]= $term;
        }

        $mform->addElement('header', 'srsform', '');
        

        $oneline=array();
        /*
        To display multiple elements on the same line, I have to group them, 
        but grouping hides the label of individual element inside the group 
        (the third label parameter of createElement gets ignored). 
        Therefore, I had to add these static elements to make up for the missing labels.
        */
        $oneline[] =& $mform->createElement('static', 'termlabel', null, '<label 
            for="id_group1_term">TERM: </label>');
        $selectterm =& $mform->createElement('select', 'term', null, $pulldown_term);
        $oneline[] = $selectterm;
        $attrib=array('size'=>'25');
        $oneline[] =& $mform->createElement('static', 'srslabel', null, '<label 
            for="id_group1_srs">SRS: </label>');
        $oneline[] =& $mform->createElement('text', 'srs', null, $attrib);
        $oneline[] =& $mform->createElement('submit', 'submit', 'Build course ');
        
        // put these elements in one group so that they appear on the same line
        // the last element is true. this way it can be refered in setDefaults
        $mform->addGroup($oneline, 'group1', null, ' ', true);
        

        $mform->setDefaults(array('group1'=> array('term'=>$selected_term)));
        
        $mform->addElement('hidden','action','fillform');
        $mform->setType('action', PARAM_TEXT);
        
        $mform->addGroupRule('group1', 
            array('srs' => array(array(get_string('srserror', 'report_courserequestor'), 
            'regex', '/^[0-9]{9}$/', 'client'))));
        
        $mform->addElement('html', '</fieldset>');
    }
}

?>
    