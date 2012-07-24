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

    // This is a hack for labels, but just in general
    // it should refer to the field that is displayed that will
    // help associate the javascript updating the rearrange
    var $default_displayname_field = 'name';

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
        $copyrights = $this->_customdata['copyrights'];
        $rearrange_avail = $this->_customdata['rearrange'];

        $defaultsection = $this->_customdata['defaultsection'];

        $addtitle = 'dialog_add_' . $type;
        $mform->addElement('header', 'general', get_string($addtitle,
            self::associated_block));

        // Adding needed parameters if being redirected or adding amodule
        $mform->addElement('hidden', 'course_id', $course->id);
        $mform->addElement('hidden', 'course', $course->id);

        $mform->addElement('hidden', 'type', $type, array('id' => 'id_type'));
        $mform->addElement('hidden', 'modulename', $this->get_coursemodule());
    
        $mform->addElement('hidden', 'default_displayname_field', 
            $this->default_displayname_field, 
            array('id' => 'id_default_displayname_field'));

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
                    get_string('select_copyright_list', self::associated_block) . 
                    ' ' . get_string('choosecopyright_helpicon', 'local_ucla'), 
                    array('for' => 'id_license')));

            // Show the copyright selector
            $mform->addElement('select', 'license','', $copyrights);
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
        $mform->setDefault('section', $defaultsection);

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

        // Stolen from /course/moodleform_mod.php:471 (Moodle 2.2.4)
        // replaced references of $COURSE with $this->course
        if (!empty($CFG->enableavailability) && $this->enable_availability) {
            // Conditional availability

            // Available from/to defaults to midnight because then the display
            // will be nicer where it tells users when they can access it (it
            // shows only the date and not time).
            $date = usergetdate(time());
            $midnight = make_timestamp($date['year'], $date['mon'], $date['mday']);

            // From/until controls
            $mform->addElement('header', 'availabilityconditionsheader',
                    get_string('availabilityconditions', 'condition'));
            $mform->addElement('date_time_selector', 'availablefrom',
                    get_string('availablefrom', 'condition'),
                    array('optional' => true, 'defaulttime' => $midnight));
            $mform->addHelpButton('availablefrom', 'availablefrom', 'condition');
            $mform->addElement('date_time_selector', 'availableuntil',
                    get_string('availableuntil', 'condition'),
                    array('optional' => true, 'defaulttime' => $midnight));

            // Conditions based on grades
            $gradeoptions = array();
            $items = grade_item::fetch_all(array('courseid'=>$this->course->id));
            $items = $items ? $items : array();
            foreach($items as $id=>$item) {
                // Do not include grades for current item
                if (!empty($this->_cm) && $this->_cm->instance == $item->iteminstance
                    && $this->_cm->modname == $item->itemmodule
                    && $item->itemtype == 'mod') {
                    continue;
                }
                $gradeoptions[$id] = $item->get_name();
            }
            asort($gradeoptions);
            $gradeoptions = array(0=>get_string('none','condition'))+$gradeoptions;

            $grouparray = array();
            $grouparray[] =& $mform->createElement('select','conditiongradeitemid','',$gradeoptions);
            $grouparray[] =& $mform->createElement('static', '', '',' '.get_string('grade_atleast','condition').' ');
            $grouparray[] =& $mform->createElement('text', 'conditiongrademin','',array('size'=>3));
            $grouparray[] =& $mform->createElement('static', '', '','% '.get_string('grade_upto','condition').' ');
            $grouparray[] =& $mform->createElement('text', 'conditiongrademax','',array('size'=>3));
            $grouparray[] =& $mform->createElement('static', '', '','%');
            $group = $mform->createElement('group','conditiongradegroup',
                get_string('gradecondition', 'condition'),$grouparray);

            // Get version with condition info and store it so we don't ask
            // twice
            if(!empty($this->_cm)) {
                $ci = new condition_info($this->_cm, CONDITION_MISSING_EXTRATABLE);
                $this->_cm = $ci->get_full_course_module();
                $count = count($this->_cm->conditionsgrade)+1;
            } else {
                $count = 1;
            }

            $this->repeat_elements(array($group), $count, array(), 'conditiongraderepeats', 'conditiongradeadds', 2,
                                   get_string('addgrades', 'condition'), true);
            $mform->addHelpButton('conditiongradegroup[0]', 'gradecondition', 'condition');

            // BEGIN UCLA MOD: CCLE-3237 - hide certain availability restrictions under "Advanced"
            // handle case in which user choose to add more fields
            $total = $mform->getElement('conditiongraderepeats')->getValue();
            for ($i=0; $i<$total; $i++) {
                $mform->setAdvanced('conditiongradegroup[' . $i . ']');                    
            }                
            $mform->setAdvanced('conditiongradeadds');                                    
            // END UCLA MOD: CCLE-3237                  
            
            // Conditions based on completion
            $completion = new completion_info($this->course);
            if ($completion->is_enabled()) {
                $completionoptions = array();
                $modinfo = get_fast_modinfo($this->course);
                foreach($modinfo->cms as $id=>$cm) {
                    // Add each course-module if it:
                    // (a) has completion turned on
                    // (b) is not the same as current course-module
                    if ($cm->completion && (empty($this->_cm) || $this->_cm->id != $id)) {
                        $completionoptions[$id]=$cm->name;
                    }
                }
                asort($completionoptions);
                $completionoptions = array(0=>get_string('none','condition'))+$completionoptions;

                $completionvalues=array(
                    COMPLETION_COMPLETE=>get_string('completion_complete','condition'),
                    COMPLETION_INCOMPLETE=>get_string('completion_incomplete','condition'),
                    COMPLETION_COMPLETE_PASS=>get_string('completion_pass','condition'),
                    COMPLETION_COMPLETE_FAIL=>get_string('completion_fail','condition'));

                $grouparray = array();
                $grouparray[] =& $mform->createElement('select','conditionsourcecmid','',$completionoptions);
                $grouparray[] =& $mform->createElement('select','conditionrequiredcompletion','',$completionvalues);
                $group = $mform->createElement('group','conditioncompletiongroup',
                    get_string('completioncondition', 'condition'),$grouparray);

                $count = empty($this->_cm) ? 1 : count($this->_cm->conditionscompletion)+1;
                $this->repeat_elements(array($group),$count,array(),
                    'conditioncompletionrepeats','conditioncompletionadds',2,
                    get_string('addcompletions','condition'),true);
                $mform->addHelpButton('conditioncompletiongroup[0]', 'completioncondition', 'condition');
                
                // BEGIN UCLA MOD: CCLE-3237 - hide certain availability restrictions under "Advanced"
                // handle case in which user choose to add more fields
                $total = $mform->getElement('conditioncompletionrepeats')->getValue();
                for ($i=0; $i<$total; $i++) {
                    $mform->setAdvanced('conditioncompletiongroup[' . $i . ']');                    
                }                
                $mform->setAdvanced('conditioncompletionadds');                                    
                // END UCLA MOD: CCLE-3237                         
            }

            // Do we display availability info to students?
            $mform->addElement('select', 'showavailability', get_string('showavailability', 'condition'),
                    array(CONDITION_STUDENTVIEW_SHOW=>get_string('showavailability_show', 'condition'),
                    CONDITION_STUDENTVIEW_HIDE=>get_string('showavailability_hide', 'condition')));
            $mform->setDefault('showavailability', CONDITION_STUDENTVIEW_SHOW);
            
            // BEGIN UCLA MOD: CCLE-3237 - hide certain availability restrictions under "Advanced"
            $mform->setAdvanced('showavailability');                                            
            // END UCLA MOD: CCLE-3237       
        }
        // END code from /course/moodleform_mod.php to display availability restrictions
        
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

    /**
     *  Validation for availability.
     **/
    function validation($data, $files) {
        // Conditions: Don't let them set dates which make no sense
        if (array_key_exists('availablefrom', $data) &&
            $data['availablefrom'] && $data['availableuntil'] &&
            $data['availablefrom'] >= $data['availableuntil']) {
            $errors['availablefrom'] = get_string('badavailabledates', 'condition');
        }
    }
}

// Gotta run this code or else we get some interesting errors.
MoodleQuickForm::registerElementType('uclafile', 
    dirname(__FILE__) . '/quickform_file.php', 'MoodleQuickForm_uclafile');

// End of file
