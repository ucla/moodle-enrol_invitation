<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

abstract class easy_upload_form extends moodleform {
    protected $course;
    protected $activities;
    protected $resources;

    const associated_block = 'block_ucla_easyupload';

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
        $this->course = $course;

        $acts = $this->_customdata['activities'];
        $this->activities = $acts;

        $reso = $this->_customdata['resources'];
        $this->resources = $reso;

        $type = $this->_customdata['type'];
        $sections = $this->_customdata['sectionnames'];
        $rearrange_avail = $this->_customdata['rearrange'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            self::associated_block));

        // Adding needed parameters if being redirected or adding amodule
        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->addElement('hidden', 'course', $course->id);

        $mform->addElement('hidden', 'type', $type);
        $mform->addElement('hidden', 'modulename', $this->get_coursemodule());

        // TODO Force download always
        $mform->addElement('hidden', 'display', false); 

        // Configure what it is you exactly are adding
        $this->specification();

        if ($this->allow_renaming) {
            $renametitle = 'dialog_rename_' . $type;
            $mform->addElement('header', '', get_string($renametitle,
                self::associated_block));

            $mform->addElement('text', 'name', get_string('name'),
                array('size' => 40));
            $mform->addRule('name', null, 'required');

            $mform->addElement('textarea', 'intro', 
                get_string('description'), array('rows' => 9, 'cols' => 40));
            
            $mform->addElement('hidden', 'introformat', FORMAT_HTML);
        } else {
            debugging('Renaming not allowed');
        }

        // Section selection.
        $mform->addElement('header', '', get_string('select_section',
            self::associated_block));

        // End code that probably needs to go somewhere else

        if (class_exists('PublicPrivate_Site')) {
            if (PublicPrivate_Site::is_enabled()) {
                
            }
        }

        // Show the section selector
        $mform->addElement('select', 'section',
            get_string('select_section', self::associated_block), 
            $sections);

        // If needed, add the section rearranges.
        // This part appears to be a part of 'add to section'
        if ($rearrange_avail && $this->allow_js_select) {
            global $PAGE;

            // TODO Validate interconnection with rearrange
            $mform->addElement('hidden', 'serialized', null, 
                array('id' => 'serialized'));

            $mform->addElement('html', html_writer::tag('div', 
                    html_writer::tag('ul', get_string('rearrangejsrequired',
                        self::associated_block), array('id' => 'thelist')),
                array('id' => 'reorder-container'))
            );

            // This is a violation of encapsulation (technically)
            $PAGE->requires->js_init_code('initiateSortableContent()');
        }

        $this->add_action_buttons();
    }

    /** 
     *  Called within the form, to specify what it is the form is specifying
     *  from the user.
     **/
    abstract function specification();

    /**
     *  Called when attempting to figure out what module to add.
     *  This is simply an enforcement protocol, this function is
     *  actually called within definition() and added as a value
     *  to a hidden field within the form.
     *
     *  @return String
     **/
    abstract function get_coursemodule();
}

// Gotta run this code or else we get some interesting errors.
MoodleQuickForm::registerElementType('uclafile', 
    dirname(__FILE__) . '/quickform_file.php', 'MoodleQuickForm_uclafile');

// End of file
