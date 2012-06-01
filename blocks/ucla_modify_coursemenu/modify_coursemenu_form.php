<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class ucla_modify_coursemenu_form extends moodleform {

    function definition() {
        global $CFG, $DB, $array;
        
        $array = array();

        $mform =& $this->_form;

        $course_id  = $this->_customdata['course_id'];
        $topic      = $this->_customdata['topic'];        
        $sections   = $this->_customdata['sections'];
        $landing_page = $this->_customdata['landing_page'];

       $mform->addElement('hidden', 'course_id', $course_id);
       $mform->addElement('hidden', 'topic', $topic);     
       $mform->addElement('html', '<div class="headercontainer">');
       $mform->addElement('html', '<div id="title">Title');
       $mform->addElement('html', '</div>');
       $mform->addElement('html', '<div id="hide">Hide');    
       $mform->addElement('html', '</div>');
       $mform->addElement('html', '<div id="delete">Delete');
       $mform->addElement('html', '</div>');
       $mform->addElement('html', '<div id="landing">Landing Page');
       $mform->addElement('html', '</div>');
       $mform->addElement('html', '</div>');
       $mform->addElement('html', '<br>');

       foreach ($sections as $section) {

        $buttonarray=array();
    
        if($section->sequence != null) {
            array_push($array, $section->section);       
        }
      
        if($section->section == 0) {
            $buttonarray[] =& $mform->createElement('text', "", "", "value='Site info'; class='namesection'"); 
            $buttonarray[] =& $mform->createElement('radio', 'landing', '', '', "$section->section", "class='landingsi'");
        }
        
        else {
        $buttonarray[] =& $mform->createElement('text', "name[$section->id]", "", "value='$section->name'; class='namesection'");
       
        $buttonarray[] =& $mform->createElement('advcheckbox', "hide[$section->section]", '',  '', array('group' => 1, 'class' => 'hide'), array(0,1));
       
        $buttonarray[] =& $mform->createElement('checkbox', "delete[$section->section]", '', '', "class='delete'");
        
        $buttonarray[] =& $mform->createElement('radio', 'landing', '', '', "$section->section", "class='landing'");
        }
     
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        
        $mform->setDefault('landing', $landing_page);
        $mform->setDefault("hide[$section->section]", $section->visible^1);

        }
        
        $this->add_action_buttons();
  
    }
    
    function search($secnum, $array) {
        foreach($array as $num) {
            if($num == $secnum) return 1;           
        }
        return 0;
    }
    
    function get_data() {
        global $array;
        $warning = 0;
        $mform =& $this->_form;

        if (!$this->is_cancelled() and $this->is_submitted() and $this->is_validated()) {
            $data = $mform->exportValues();
            unset($data['sesskey']); // we do not need to return sesskey
            unset($data['_qf__'.$this->_formname]);   // we do not need the submission marker too
            if (empty($data)) {
                return NULL;
            } else {
                $objdata = (object)$data;
                if(isset($objdata->delete)) {
                foreach($objdata->delete as $delete => $val) {
                    if($this->search($delete, $array)) {
                        $mform->addElement('html', 
                                '<div id="warning">Cannot delete non-empty sections. Please delete or move existing content to delete it</div>');
                                $warning = 1;                                
                    }
                }
                }
                if($warning) return 0;
                else return $objdata;    
            }
        } else {
            return NULL;
        }
    }
    
     function is_cancelled() {
        $result = parent::is_cancelled();
        
        if (!empty($result)) {
            $course_id  = $this->_customdata['course_id'];
            $topic      = $this->_customdata['topic'];             
            
            redirect(new moodle_url('/course/view.php',
                array('id' => $course_id, 'topic' => $topic)));
        }
    }
}
