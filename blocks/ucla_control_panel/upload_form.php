<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once(dirname(__FILE__) . '/uploadlib.php');

abstract class easy_upload_form extends moodleform {
    protected $course;
    protected $context;

    // This will enable the section switcher
    var $allow_js_select = false;

    // This will enable the naming field
    var $allow_renaming = false;

    /**
     *  Called by moodleforms when we are rendering the form.
     **/
    function definition() {
        $mform = $this->_form;

        $course = $this->_customdata['course'];
        $type = $this->_customdata['type'];
        $sections = $this->_customdata['sectionnames'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            'block_ucla_control_panel'));

        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->addElement('hidden', 'type', $type);
        $mform->addElement('hidden', 'modulename', $this->get_coursemodule());

        // Configure what it is you exactly are adding
        $this->specification();

        if ($this->allow_renaming) {
            $renametitle = 'dialog_rename_' . $type;
            $mform->addElement('header', '', get_string($renametitle,
                'block_ucla_control_panel'));

            $mform->addElement('text', 'name', get_string('name'),
                array('size' => 40));

            $mform->addElement('textarea', 'intro', 
                get_string('description'), array('rows' => 9, 'cols' => 40));
            
            $mform->addElement('hidden', 'introformat', FORMAT_HTML);
        } else {
            debugging('Renaming not allowed');
        }

        // Section selection.
        $mform->addElement('header', '', get_string('select_section',
            'block_ucla_control_panel'));

        // End code that probably needs to go somewhere else

        // Show the section selector
        $mform->addElement('select', 'section',
            get_string('select_section', 'block_ucla_control_panel'), 
            $sections);

        if (class_exists('PublicPrivate_Site')) {
            if (PublicPrivate_Site::is_enabled()) {
                // TODO
            }
        }

        if ($this->allow_js_select) {
            // If needed, add the section rearranges.
        }

        $this->add_action_buttons();
    }

    /** 
     *  Called within the form, to specify what it is the form is specifying
     *  from the user.
     **/
    abstract function specification();
    
    /**
     *  Called once the form has been submitted, to act upon the data
     *  that was submitted.
     **/
    abstract function process_data($data);

    /**
     *  Called when attempting to figure out what module to add.
     *
     *  @return String
     **/
    abstract function get_coursemodule();
}
