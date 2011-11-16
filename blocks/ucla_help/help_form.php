<?php
/**
 * Form for users to send messages to support contacts.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

class help_form extends moodleform {
 
    function definition() {
        global $COURSE, $USER;

        // if on a real course, be sure to include courseid as GET variable
        if($COURSE->id > 1) {
            $this->_form->_attributes['action'] .= '?course='. $COURSE->id;
        } 
        
        $this->_form->_attributes['id'] = 'help_form';
        
        $mform =& $this->_form;
        
        // css should be used to define widths of input/textarea fields
        $mform->addElement('text', 'ucla_help_name', 
                get_string('name_field', 'block_ucla_help'));
        $mform->addElement('text', 'ucla_help_email', 
                get_string('email_field', 'block_ucla_help'));            
        $mform->addElement('textarea', 'ucla_help_description', 
                get_string("description_field", "block_ucla_help"), 
                'wrap="virtual" rows="6"');        
        
        // no point in having a cancel option
        $this->add_action_buttons(false, get_string('submit_button', 'block_ucla_help'));
        
        // set proper types for each element
        $mform->setType('ucla_help_name', PARAM_TEXT);
        $mform->setType('ucla_help_email', PARAM_EMAIL);        
        $mform->setType('ucla_help_description', PARAM_TEXT);
        
        // trim all input
        $mform->applyFilter('ucla_help_name', 'trim');
        $mform->applyFilter('ucla_help_email', 'trim');
        $mform->applyFilter('ucla_help_description', 'trim');
        
        // if email is present, make sure it is a valid email address
        $mform->addRule('ucla_help_email', get_string('err_email', 'form'), 'email');
        
        // make description field a required field 
        $mform->addRule('ucla_help_description', get_string('requiredelement', 'form'), 'required');        
        
        // set defaults for name/email
        if(isloggedin() && !isguestuser()) {
            $mform->setDefault('ucla_help_name', "$USER->firstname $USER->lastname");
            $mform->setDefault('ucla_help_email', $USER->email);
        }        
    }                           
}          
?>