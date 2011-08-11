<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

class easy_upload_form extends moodleform {
    protected $course;
    protected $context;

    var $allow_js_select = false;
    var $allow_renaming = false;

    /**
     *  Called by moodleforms when we are rendering the form.
     **/
    function definition() {
        $mform = $this->_form;

        $course = $this->_customdata['course'];
        $type = $this->_customdata['type'];
        $sections = $this->_customdata['sections'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            'block_ucla_control_panel'));

        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->addElement('hidden', 'type', $type);

        // Configure what it is you exactly are adding
        $this->specification();

        if ($this->allow_renaming) {
            $renametitle = 'dialog_rename_' . $type;
            $mform->addElement('header', '', get_string($renametitle,
                'block_ucla_control_panel'));

            $mform->addElement('text', 'uploadname', get_string('name'));

            $mform->addElement('textarea', 'description', 
                get_string('description'));
        } else {
            debugging('Renaming not allowed');
        }

        // Section selection.
        $mform->addElement('header', '', get_string('select_section',
            'block_ucla_control_panel'));

        // Figure out what to name each section...
        // TODO This code needs to go somewhere else
        $format = $course->format;
        $sectionnamefn = 'callback_' . $format . '_get_section_name';

        $fn_exists = false;
        if (function_exists($sectionnamefn)) {
            $fn_exists = true;
        }

        if (!$fn_exists) {
            $fallback = get_string('section');
        }
        // End code that probably needs to go somewhere else

        $select_sections = array();
        foreach ($sections as $section) {
            $section_title = '';
            if (!$section->name) {
                if ($fn_exists) {
                    $section_title = $sectionnamefn($course, $section);
                } else {
                    $section_title = $fallback . ' ' . $section->section;
                }
            } else {
                $section_title = $section->name;
            }

            $select_sections[] = $section_title;
        }

        $mform->addElement('select', 'section',
            get_string('select_section', 'block_ucla_control_panel'), 
            $select_sections);

        if (class_exists('PublicPrivate_Site')) {
            if (PublicPrivate_Site::is_enabled()) {
                // TODO
            }
        }

        if ($this->allow_js_select) {
            // Show the section modifier selector
        }

        // If needed, add the section rearranges.
        $this->add_action_buttons();
    }

    /** 
     *  Called within the form, to specify what it is the form is specifying
     *  from the user.
     **/
    function specification() {
        //print_error('');
    }

    /**
     *  Called once the form has been submitted, to act upon the data
     *  that was submitted.
     **/
    function process_data($data) {
        return false;
    }
}
