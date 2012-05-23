<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class ucla_modify_coursemenu_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $course_id  = $this->_customdata['course_id'];
        $topic      = $this->_customdata['topic'];        
        $sections   = $this->_customdata['sections'];

        $mform->addElement('hidden', 'course_id', $course_id);
        $mform->addElement('hidden', 'topic', $topic);   
       // echo json_encode($sections);
        
        
        
        
      
        
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
        
        
      
        //$mform->addElement('html', '<table>');
        foreach ($sections as $section) {
       
      
        //echo "Key: $key; Value: $value<br />\n";
      
        //echo $section->section;
         //$mform->addElement('text', 'email', '', 'maxlength="100" size="25 value="HAHA" ');
 
        
        $buttonarray=array();
       
        //$section->visible = 0;
       
        
      
   
       
        $buttonarray[] =& $mform->createElement('text', "name[$section->id]", "", "value='$section->name'; class='namesection'");
       
        $buttonarray[] =& $mform->createElement('advcheckbox', "hide[{$section->section}]", '',  '', array('group' => 1, 'class' => 'hide'), array(0,1));
       
        $buttonarray[] =& $mform->createElement('checkbox', "delete[$section->section]", '', '', "class='delete'");
        
        $buttonarray[] =& $mform->createElement('radio', 'landing', '', '', "$section->id", "class='landing'");
        
     
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        
        $mform->setDefault('landing', 613);
        $mform->setDefault("hide[$section->section]", $section->visible^1);
        
  
        }
        
    
        
        $this->add_action_buttons();
  
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
