<?php
require_once($CFG->libdir.'/formslib.php');
// view live courses
class live_form extends moodleform {

    function definition() {
        global $CFG;
        global $DB;
        $mform =& $this->_form;
        $selected_term = optional_param('term',NULL,PARAM_CLEAN) ? optional_param('term',NULL,PARAM_CLEAN) : $CFG->classrequestor_selected_term;
        
        $pulldown_term = array();

        foreach ($CFG->classrequestor_terms as $term) {
            $pulldown_term[$term]= $term;
        }
        
        $pulldown_dept = array();
        $pulldown_dept[] = 'ALL';
        $rs=$DB->get_records_sql("select distinct department from mdl_ucla_request_classes order by department");

        foreach ($rs as $row) {
            $pulldown_dept[$row->department] = $row->department;
        }
        
        $mform->addElement('header', 'buildform', '');
        $oneline=array();
        $oneline[] =& $mform->createElement('static', 'termlabel', null, '<label>TERM: </label>');
        $selectterm =& $mform->createElement('select', 'term', null, $pulldown_term);
        $oneline[] = $selectterm;
        $oneline[] =& $mform->createElement('static', 'deptlabel', null, '<label>DEPARTMENT: </label>');
        $selectdept =& $mform->createElement('select', 'department', null, $pulldown_dept);
        $oneline[] = $selectdept;
        $oneline[] =& $mform->createElement('submit', 'submit', 'View Live Courses ');
        
        // put these elements in one group so that they appear on the same line
        $mform->addGroup($oneline, 'group2', null, ' ', true);
        $mform->setDefaults(array('group2'=> array('term'=>$selected_term)));
        $mform->addElement('hidden','action','viewlivecourses');
        $mform->setType('action', PARAM_TEXT);
    }
}
?>