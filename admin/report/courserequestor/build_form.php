<?php
require_once($CFG->libdir.'/formslib.php');
// view courses to be built
class build_form extends moodleform {
    private $dept = null;
    function __construct($dept_temp) {
        $this->dept = $dept_temp;
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
        
        $pulldown_livebuild = array();
        $pulldown_livebuild['built'] = 'To be built';
        $pulldown_livebuild['live'] = 'Live';
        
        $pulldown_dept = array();
        $pulldown_dept[] = 'ALL';

        foreach ($this->dept as $row) {
            $pulldown_dept[$row->department] = $row->department;
        }
        
        $mform->addElement('header', 'buildform', '');
        $oneline=array();
        /*
        To display multiple elements on the same line, I have to group them, 
        but grouping ignores the label of individual element inside the group 
        (the third label parameter of createElement gets ignored). 
        Therefore, I had to add these static elements to make up for the missing labels.
        */
        $oneline[] =& $mform->createElement('static', 'termlabel', null, '<label for="id_group2_term">TERM: </label>');
        $selectterm =& $mform->createElement('select', 'term', null, $pulldown_term);
        $oneline[] = $selectterm;
        $oneline[] =& $mform->createElement('static', 'deptlabel', null, '<label for="id_group2_department">DEPARTMENT: </label>');
        $selectdept =& $mform->createElement('select', 'department', null, $pulldown_dept);
        $oneline[] = $selectdept;
        $livebuild =& $mform->createElement('select', 'livebuild', null, $pulldown_livebuild);
        $oneline[] = $livebuild;
        
        $oneline[] =& $mform->createElement('submit', 'submit', 'View courses');

        $mform->addGroup($oneline, 'group2', null, ' ', true);

        $mform->setDefaults(array('group2'=> array('term'=>$selected_term)));
    }
}
?>