<?php
require_once($CFG->libdir.'/formslib.php');
// view all the classes of a department
class view_dept_form extends moodleform {
    private $allsubjects = null;
    function __construct($subj_temp) {
        $this->allsubjects = $subj_temp;
        parent::__construct();
    }
    function definition() {
        global $CFG;
        global $DB;
        $mform =& $this->_form;
        
        $selected_term = optional_param('term',NULL,PARAM_ALPHANUM) ? 
        optional_param('term',NULL,PARAM_ALPHANUM) : $CFG->classrequestor_selected_term;
        $pulldown_term = array();

        foreach ($CFG->classrequestor_terms as $term) {
            $pulldown_term[$term]= $term;
        }
              
        $pulldown_subject = array();
        foreach ($this->allsubjects as $row)
        {
            $pulldown_subject[$row[0]] = $row[0].' - '.$row[1];
        }
          
        $mform->addElement('header', 'buildform', '');
        $oneline=array();
        /*
        To display multiple elements on the same line, I have to group them, 
        but grouping ignores the label of individual element inside the group 
        (the third label parameter of createElement gets ignored). 
        Therefore, I had to add these static elements to make up for the missing labels.
        */
        $oneline[] =& $mform->createElement('static', 'termlabel', null, 
            '<label for="id_group2_term">TERM: </label>');
        $selectterm =& $mform->createElement('select', 'term', null, $pulldown_term);
        $oneline[] = $selectterm;
        $oneline[] =& $mform->createElement('static', 'subjectlabel', null, 
            '<label for="id_group2_subjarea">SUBJECT AREA: </label>');
        $selectsubj =& $mform->createElement('select', 'subjarea', null, $pulldown_subject);
        $oneline[] = $selectsubj;
        $oneline[] =& $mform->createElement('submit', 'submit', get_string('builddept', 'report_courserequestor'));
        // put these elements in one group so that they appear on the same line
        // see style.css for overloading the default moodle form stylesheet
        $mform->addGroup($oneline, 'group2', null, ' ', true);
        // make sure the last element is true. this way it can be refered in setDefaults
        $mform->setDefaults(array('group2'=> array('term'=>$selected_term)));
        $mform->addElement('hidden','action','viewdept');
        $mform->setType('action', PARAM_TEXT);
        
    }
}
?>