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

    // This will enable the public private stuff
    var $allow_publicprivate = true;

    // This will enable the availability sliders
    var $enable_availability = true;

    // This is the default publicprivate, if relevant,
    // @string 'public' or 'private'
    var $default_publicprivate = 'private';

    /**
     *  Called by moodleforms when we are rendering the form.
     **/
    function definition() {
        global $CFG;

        $mform = $this->_form;

        $course = $this->_customdata['course'];
        $this->course = $course;
        
        $acts = $this->_customdata['activities'];
        $this->activities = $acts;

        $reso = $this->_customdata['resources'];
        $this->resources = $reso;

        $type = $this->_customdata['type'];
        $sections = $this->_customdata['sectionnames'];
        $copyrights=$this->_customdata['copyrights'];
        $rearrange_avail = $this->_customdata['rearrange'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            self::associated_block));

        // Adding needed parameters if being redirected or adding amodule
        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->addElement('hidden', 'course', $course->id);

        $mform->addElement('hidden', 'type', $type, array('id' => 'id_type'));
        $mform->addElement('hidden', 'modulename', $this->get_coursemodule());

        // Use whatever the default display type is for the site. Can be either 
        // automatic, embed, force download, etc. Look in lib/resourcelib.php 
        // for other types
        $mform->addElement('hidden', 'display', get_config('resource', 
            'display')); 

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
        }

        // add copyright selection
       if ($type == "file"){
            $mform->addElement('header', '', get_string('select_copyright',
                self::associated_block));

            $mform->addElement('html', html_writer::tag('label', 
                    get_string('select_copyright_list', self::associated_block), 
                    array('for' => 'id_license')));

            // Show the copyright selector
            $mform->addElement('select', 'license','', $copyrights);
            $mform->addHelpButton('license', 'license', 'block_ucla_easyupload');
            $mform->addRule('license', null, 'required');
                $mform->setDefaults(
                    array(
                        'license' => array(
                            'license' => $CFG->sitedefaultlicense
                        )
                    )
                );   
       }

        if (class_exists('PublicPrivate_Site')) {
            if (PublicPrivate_Site::is_enabled() 
                    && PublicPrivate_Course::is_publicprivate_capable(
                        $this->course
                    )) {
                // Generate the public private elements
                $t = array('public', 'private');

                $pubpriels = array();

                foreach ($t as $p) {
                    $pubpriels[] = $mform->createElement('radio', 
                        'publicprivate', '', 
                        get_string('publicprivatemake' . $p),
                        $p);
                }

                $mform->addElement('header', '', get_string(
                    'publicprivateenable'));

                $mform->addGroup($pubpriels, 'publicprivateradios', 
                    get_string('publicprivate'), ' ', true);

                $mform->setDefaults(
                    array(
                        'publicprivateradios' => array(
                            'publicprivate' => $this->default_publicprivate
                        )
                    )
                );

            }
        }

        // Section selection.
        $mform->addElement('header', '', get_string('select_section',
            self::associated_block));

        // Show the section selector
        $mform->addElement('select', 'section',
            get_string('select_section', self::associated_block), 
            $sections);

        // If needed, add the section rearranges.
        // This part appears to be a part of 'add to section'
        if ($rearrange_avail && $this->allow_js_select) {
            global $PAGE;

            $mform->addElement('hidden', 'serialized', null, 
                array('id' => 'serialized'));
            
            $mform->addElement('html', html_writer::tag('div', 
                    html_writer::tag('ul', get_string('rearrangejsrequired',
                        self::associated_block), array('id' => 'thelist')),
                array('id' => 'reorder-container'))
            );

            $PAGE->requires->js_init_code(
                'M.block_ucla_easyupload.initiate_sortable_content()'
            );
        }

        // Stolen from /course/moodleform_mod.php:429
        if (!empty($CFG->enableavailability) && $this->enable_availability) {
            // Conditional availability
            $mform->addElement('header', 'availabilityconditionsheader', 
                get_string('availabilityconditions', 'condition'));

            $mform->addElement('date_selector', 'availablefrom', 
                get_string('availablefrom', 'condition'), 
                    array('optional' => true));

            $mform->addHelpButton('availablefrom', 'availablefrom', 
                'condition');

            $mform->addElement('date_selector', 'availableuntil', 
                get_string('availableuntil', 'condition'), 
                    array('optional' => true));

            // Do we display availability info to students?
           $mform->addElement('select', 'showavailability', 
                get_string('showavailability', 'condition'), array(
                    CONDITION_STUDENTVIEW_SHOW
                        => get_string('showavailability_show', 'condition'),
                    CONDITION_STUDENTVIEW_HIDE 
                        => get_string('showavailability_hide', 'condition')
                )
            );

            $mform->setDefault('showavailability', 
                CONDITION_STUDENTVIEW_SHOW);
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
