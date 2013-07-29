<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $editid = $this->_customdata['editid'];
        $edit_email = $this->_customdata['edit_email'];        
        $courseid = $this->_customdata['courseid'];
        $defaults = $this->_customdata['defaults'];
        $website = $this->_customdata['url'];
        $email_settings = $this->_customdata['email_settings'];
        
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->addElement('hidden', 'editid', $editid);
        
        // office info \\
        $mform->addElement('header', 'header_office_info', 
                get_string('header_office_info', 'block_ucla_office_hours'));
        
        // office hours
        $mform->addElement('static', 'f_officehours_text', '', 
                get_string('f_officehours_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('f_officehours', 'block_ucla_office_hours'));

        // office location
        $mform->addElement('static', 'f_office_text', '', 
                get_string('f_office_text', 'block_ucla_office_hours'));        
        $mform->addElement('text', 'office', get_string('f_office', 'block_ucla_office_hours'));

        // contact info \\
        $mform->addElement('header', 'header_contact_info', 
                get_string('header_contact_info', 'block_ucla_office_hours'));

        // email of record
        if (empty($edit_email)) {
            $edit_email = get_string('f_email_of_record_empty', 'block_ucla_office_hours');
        }
        $mform->addElement('static', 'f_email_of_record', 
                get_string('f_email_of_record', 'block_ucla_office_hours'), $edit_email);

        // email display settings
        $display_opt = array(get_string('emaildisplayno', 'moodle'), 
                            get_string('emaildisplayyes', 'moodle'), get_string('emaildisplaycourse', 'moodle'));
        $mform->addElement('select', 'email_settings', get_string('f_email_display', 'block_ucla_office_hours'), $display_opt);
        $mform->setDefault('email_settings', $email_settings);

        // alternative email
        $mform->addElement('static', 'f_email_text', '', 
                get_string('f_email_text', 'block_ucla_office_hours', $edit_email));
        $mform->addElement('text', 'email', get_string('f_email', 'block_ucla_office_hours'));
        
        // phone
        $mform->addElement('static', 'f_phone_text', '', 
                get_string('f_phone_text', 'block_ucla_office_hours'));        
        $mform->addElement('text', 'phone', get_string('f_phone', 'block_ucla_office_hours'));
        
        // website
        $mform->addElement('text', 'website', get_string('f_website', 'block_ucla_office_hours'));

        // Set Rules, Types and Defaults
        // Set character limits for each field from field limits in DB.
        $fields = $DB->get_columns('ucla_officehours');
        $officehourslimit = $fields['officehours']->max_length;
        $officelimit = $fields['officelocation']->max_length;
        $emaillimit = $fields['email']->max_length;
        $phonelimit = $fields['phone']->max_length;

        // Set maxlength rule and type for office hours field.
        $mform->addRule('officehours', get_string('maximumchars', '', $officehourslimit).'.  '.get_string('officehours_format', 'block_ucla_office_hours'), 
                        'maxlength', $officehourslimit);
        $mform->setType('officehours', PARAM_TEXT);  

        // Set maxlength rule and type for office lcoation field.
        $mform->addRule('office', get_string('maximumchars', '', $officelimit), 
                        'maxlength', $officelimit);
        $mform->setType('office', PARAM_TEXT); 

        // Set maxlength and email verification rules and type for alternate email field.
        $mform->addRule('email', get_string('maximumchars', '', $emaillimit), 
                        'maxlength', $emaillimit);
        $mform->addRule('email', get_string('err_email', 'form'), 'email'); 
        $mform->setType('email', PARAM_EMAIL);    

        // Set maxlenth rule and type for phone field.
        $mform->addRule('phone', get_string('maximumchars', '', $phonelimit), 
                        'maxlength', $phonelimit);
        $mform->setType('phone', PARAM_TEXT); 

        // Set default and type for website field.
        $mform->setDefault('website', $website);
        $mform->setType('website', PARAM_URL);        

        // Set defaults for other fields supplied by $defaults.
        if(!empty($defaults)) {
            $mform->setDefault('office', $defaults->officelocation);
            $mform->setDefault('officehours', $defaults->officehours);
            $mform->setDefault('phone', $defaults->phone);
            $mform->setDefault('email', $defaults->email);
        }
        
        $this->add_action_buttons();
    }
}

//EOF
