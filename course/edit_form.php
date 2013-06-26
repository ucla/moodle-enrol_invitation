<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');

class course_edit_form extends moodleform {
    protected $course;
    protected $context;

    function definition() {
        global $USER, $CFG, $DB, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
                array(array('formid' => $mform->getAttribute('id'))));

        $course        = $this->_customdata['course']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        // START UCLA MOD CCLE-2389 - override with site request category,
        // This forces the edit form to display the requested category. 
        // If the category is changed, that preference is also saved by siteindicator
        if(!empty($course->id) && $request = siteindicator_request::load($course->id)) {
            $course->category = $request->request->categoryid;
        }
        // END UCLA MOD CCLE-2389
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];

        $systemcontext   = context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;
        
        // START UCLAMOD CCLE-2389 - site indicator info display
        
        if(!empty($course->id) && ucla_map_courseid_to_termsrses($this->course->id)) {
            // is a registrar site
//            $mform->addElement('static', 'indicator', 
//                    get_string('type', 'tool_uclasiteindicator'), 
//                    get_string('site_registrar', 'tool_uclasiteindicator'));
        } else {
            // user can assign site type if they have the capability at site, 
            // category, or course level
            $can_edit_sitetype = false;
            if (has_capability('tool/uclasiteindicator:edit', $systemcontext) || 
                    has_capability('tool/uclasiteindicator:edit', $categorycontext) ||
                    (!empty($coursecontext) && has_capability('tool/uclasiteindicator:edit', $coursecontext))) {
                $can_edit_sitetype = true;
            }

            $indicator = null;
            if (!empty($course->id)) {
                $indicator = siteindicator_site::load($course->id);
            }            

            // do not allow TA site type to be changed via GUI
            if (!empty($indicator) &&
                    $indicator->property->type == siteindicator_manager::SITE_TYPE_TASITE) {
                $can_edit_sitetype = false;
            }


            // only display site type info if there is a type and user can edit
            if ($can_edit_sitetype || !empty($indicator)) {
                $mform->addElement('header','uclasiteindicator', get_string('pluginname', 'tool_uclasiteindicator'));
            }
            
            if(!empty($indicator)) {                
                $indicator_type = html_writer::tag('strong',
                        siteindicator_manager::get_types_list($indicator->property->type));
                $mform->addElement('static', 'indicator', get_string('type', 'tool_uclasiteindicator'), 
                        $indicator_type);
                
                $roles = $indicator->get_assignable_roles();
                $rolenames = array();
                foreach ($roles as $role) {
                    $rolenames[] = $role->name;
                }
                $mform->addElement('static', 'indicator_roles', get_string('roles', 'tool_uclasiteindicator'), 
                        '<strong>' . implode('</strong>, <strong>', $rolenames) . '</strong>');
            }
                                
            // Change the site type
            if($can_edit_sitetype) {
                if (empty($indicator)) {
                    // no indicator found, display ability for user to choose type
                    // if they have the capability to edit
                    $indicator_type = get_string('no_indicator_type', 'tool_uclasiteindicator');
                    $mform->addElement('static', 'indicator', get_string('type', 'tool_uclasiteindicator'), 
                            $indicator_type);                    
                }

                $types = siteindicator_manager::get_types_list();
                $radioarray = array();
                foreach($types as $type) {
                    // don't allow tasite type to be selected
                    if (siteindicator_manager::SITE_TYPE_TASITE == $type['shortname']) {
                        continue;
                    }
                    $descstring = '<strong>' . $type['fullname'] . '</strong> - ' . $type['description'];
                    $attributes = array(
                        'class' => 'indicator-form',
                        'value' => $type['shortname']
                    );
                    $radioarray[] = $mform->createElement('radio', 'indicator_change', '', $descstring, $type['shortname'], $attributes);
                }
                $mform->addGroup($radioarray, 'indicator_type_radios', get_string('change', 'tool_uclasiteindicator'), array('<br/>'), false);
                $mform->addGroupRule('indicator_type_radios', get_string('required'), 'required');
                
                if (!empty($indicator)) {
                    $mform->setDefault('indicator_change', $indicator->property->type);
                }
            }            
        }
        // END UCLA MOD CCLE-2389
/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }

        // Verify permissions to change course category or keep current.
        if (empty($course->id)) {
            if (has_capability('moodle/course:create', $categorycontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
                $mform->setDefault('category', $category->id);
            } else {
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $category->id);
            }
        } else {
            if (has_capability('moodle/course:changecategory', $coursecontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                if (!isset($displaylist[$course->category])) {
                    //always keep current
                    $displaylist[$course->category] = coursecat::get($course->category)->get_formatted_name();
                }
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
            } else {
                //keep current
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $course->category);
            }
        }

        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('visible'), $choices);
        $mform->addHelpButton('visible', 'visible');
        $mform->setDefault('visible', $courseconfig->visible);
        if (!has_capability('moodle/course:visibility', $context)) {
            $mform->hardFreeze('visible');
            if (!empty($course->id)) {
                $mform->setConstant('visible', $course->visible);
            } else {
                $mform->setConstant('visible', $courseconfig->visible);
            }
        }

        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time() + 3600 * 24);

        // START UCLA MOD: CCLE-2940 - TERM-SRS Numbers needed in Course ID Number field
        // We aren't using idnumber to put in term-srs anymore, so just query 
        // for term-srs using the cross-listing api and put in the results as
        // a constant        
//        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
//        $mform->addHelpButton('idnumber', 'idnumbercourse');
//        $mform->setType('idnumber', PARAM_RAW);
//        if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
//            $mform->hardFreeze('idnumber');
//            $mform->setConstants('idnumber', $course->idnumber);
//        }
        $mform->addElement('static','idnumber', get_string('idnumbercourse'));
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        if (!empty($course->id)) {
            // only query for term-srs if course exists
            require_once($CFG->dirroot . '/local/ucla/lib.php');
            $course_info = ucla_get_course_info($course->id);    
            $idnumber = '';
            if (!empty($course_info)) {
                // create string
                $first_entry = true;
                foreach ($course_info as $course_record) {
                    $first_entry ? $first_entry = false : $idnumber .= ', ';
                    $idnumber .= sprintf('%s (%s)', 
                            ucla_make_course_title($course_record), 
                            make_idnumber($course_record));
                }                    
            }
            $course->idnumber = $idnumber;     
        }

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        $summaryfields = 'summary_editor';

        if ($overviewfilesoptions = course_overviewfiles_options($course)) {
            $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles'), null, $overviewfilesoptions);
            $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
            $summaryfields .= ',overviewfiles_filemanager';
        }

        if (!empty($course->id) and !has_capability('moodle/course:changesummary', $coursecontext)) {
            // Remove the description header it does not contain anything any more.
            $mform->removeElement('descriptionhdr');
            $mform->hardFreeze($summaryfields);
        }

        // Course format.
        $mform->addElement('header', 'courseformathdr', get_string('type_format', 'plugin'));

        $courseformats = get_sorted_course_formats(true);
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
            $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
        }
        if (isset($course->format)) {
            $course->format = course_get_format($course)->get_format(); // replace with default if not found
            if (!in_array($course->format, $courseformats)) {
                // this format is disabled. Still display it in the dropdown
                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
            }
        }

        $mform->addElement('select', 'format', get_string('format'), $formcourseformats);
        $mform->addHelpButton('format', 'format');
        $mform->setDefault('format', $courseconfig->format);

        // Button to update format-specific options on format change (will be hidden by JavaScript).
        $mform->registerNoSubmitButton('updatecourseformat');
        $mform->addElement('submit', 'updatecourseformat', get_string('courseformatudpate'));

        // Just a placeholder for the course format options.
        $mform->addElement('hidden', 'addcourseformatoptionshere');
        $mform->setType('addcourseformatoptionshere', PARAM_BOOL);

        // Appearance.
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if (!empty($CFG->allowcoursethemes)) {
            $themeobjects = get_list_of_themes();
            $themes=array();
            $themes[''] = get_string('forceno');
            foreach ($themeobjects as $key=>$theme) {
                if (empty($theme->hidefromselector)) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }
            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
        }

        $languages=array();
        $languages[''] = get_string('forceno');
        $languages += get_string_manager()->get_list_of_translations();
        $mform->addElement('select', 'lang', get_string('forcelanguage'), $languages);
        $mform->setDefault('lang', $courseconfig->lang);

        $options = range(0, 10);
        $mform->addElement('select', 'newsitems', get_string('newsitemsnumber'), $options);
        $mform->addHelpButton('newsitems', 'newsitemsnumber');
        $mform->setDefault('newsitems', $courseconfig->newsitems);

        $mform->addElement('selectyesno', 'showgrades', get_string('showgrades'));
        $mform->addHelpButton('showgrades', 'showgrades');
        $mform->setDefault('showgrades', $courseconfig->showgrades);

        $mform->addElement('selectyesno', 'showreports', get_string('showreports'));
        $mform->addHelpButton('showreports', 'showreports');
        $mform->setDefault('showreports', $courseconfig->showreports);

        // Files and uploads.
        $mform->addElement('header', 'filehdr', get_string('filesanduploads'));

        if (!empty($course->legacyfiles) or !empty($CFG->legacyfilesinnewcourses)) {
            if (empty($course->legacyfiles)) {
                //0 or missing means no legacy files ever used in this course - new course or nobody turned on legacy files yet
                $choices = array('0'=>get_string('no'), '2'=>get_string('yes'));
            } else {
                $choices = array('1'=>get_string('no'), '2'=>get_string('yes'));
            }
            $mform->addElement('select', 'legacyfiles', get_string('courselegacyfiles'), $choices);
            $mform->addHelpButton('legacyfiles', 'courselegacyfiles');
            if (!isset($courseconfig->legacyfiles)) {
                // in case this was not initialised properly due to switching of $CFG->legacyfilesinnewcourses
                $courseconfig->legacyfiles = 0;
            }
            $mform->setDefault('legacyfiles', $courseconfig->legacyfiles);
        }

        // Handle non-existing $course->maxbytes on course creation.
        $coursemaxbytes = !isset($course->maxbytes) ? null : $course->maxbytes;

        // Let's prepare the maxbytes popup.
        $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, $coursemaxbytes);
        $mform->addElement('select', 'maxbytes', get_string('maximumupload'), $choices);
        $mform->addHelpButton('maxbytes', 'maximumupload');
        $mform->setDefault('maxbytes', $courseconfig->maxbytes);

        // Completion tracking.
        if (completion_info::is_enabled_for_site()) {
            $mform->addElement('header', 'completionhdr', get_string('completion', 'completion'));
            $mform->addElement('selectyesno', 'enablecompletion', get_string('enablecompletion', 'completion'));
            $mform->setDefault('enablecompletion', $courseconfig->enablecompletion);
            $mform->addHelpButton('enablecompletion', 'enablecompletion', 'completion');
        } else {
            $mform->addElement('hidden', 'enablecompletion');
            $mform->setType('enablecompletion', PARAM_INT);
            $mform->setDefault('enablecompletion', 0);
        }
        
//--------------------------------------------------------------------------------
        enrol_course_edit_form($mform, $course, $context);

//--------------------------------------------------------------------------------
        $mform->addElement('header','groups', get_string('groups', 'group'));

        /**
         * Flag to enable or disable public/private if it is enabled for the
         * site or if it is activated for the course.
         *
         * @author ebollens
         * @version 20110719
         */
        if(PublicPrivate_Site::is_enabled() || (PublicPrivate_Course::is_publicprivate_capable($course) 
                && PublicPrivate_Course::build($course)->is_activated())) {
            $choices = array();
            $choices[0] = get_string('disable');
            $choices[1] = get_string('enable');
            $mform->addElement('select', 'enablepublicprivate', get_string('publicprivate','local_publicprivate'), $choices);
            $mform->addHelpButton('enablepublicprivate', 'publicprivateenable', 'local_publicprivate');
            $mform->setDefault('enablepublicprivate', empty($course->enablepublicprivate) ? 1 : $course->enablepublicprivate);
        }

        $choices = array();
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
        $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $choices);
        $mform->addHelpButton('groupmode', 'groupmode', 'group');
        $mform->setDefault('groupmode', $courseconfig->groupmode);

        $mform->addElement('selectyesno', 'groupmodeforce', get_string('groupmodeforce', 'group'));
        $mform->addHelpButton('groupmodeforce', 'groupmodeforce', 'group');
        $mform->setDefault('groupmodeforce', $courseconfig->groupmodeforce);

        //default groupings selector
        $options = array();
        $options[0] = get_string('none');
        $mform->addElement('select', 'defaultgroupingid', get_string('defaultgrouping', 'group'), $options);

//--------------------------------------------------------------------------------

/// customizable role names in this course
//--------------------------------------------------------------------------------
        $mform->addElement('header','rolerenaming', get_string('rolerenaming'));
        $mform->addHelpButton('rolerenaming', 'rolerenaming');

        if ($roles = get_all_roles()) {
            $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL);
            $assignableroles = get_roles_for_contextlevels(CONTEXT_COURSE);
            foreach ($roles as $role) {
                $mform->addElement('text', 'role_'.$role->id, get_string('yourwordforx', '', $role->localname));
                $mform->setType('role_'.$role->id, PARAM_TEXT);
            }
        }
               //END UCLA MOD: CCLE-2939
//--------------------------------------------------------------------------------
        $this->add_action_buttons();
//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

/// finally set the current form data
//--------------------------------------------------------------------------------
        $this->set_data($course);
    }

    function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // add available groupings
        if ($courseid = $mform->getElementValue('id') and $mform->elementExists('defaultgroupingid')) {
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid'=>$courseid))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            $gr_el =& $mform->getElement('defaultgroupingid');
            $gr_el->load($options);
        }

        // add course format options
        $formatvalue = $mform->getElementValue('format');
        if (is_array($formatvalue) && !empty($formatvalue)) {
            $courseformat = course_get_format((object)array('format' => $formatvalue[0]));

            $elements = $courseformat->create_edit_form_elements($mform);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addcourseformatoptionshere');
            }
        }
    }

/// perform some extra moodle validation
    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        if ($foundcourses = $DB->get_records('course', array('shortname'=>$data['shortname']))) {
            if (!empty($data['id'])) {
                unset($foundcourses[$data['id']]);
            }
            if (!empty($foundcourses)) {
                foreach ($foundcourses as $foundcourse) {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
                $foundcoursenamestring = implode(',', $foundcoursenames);
                $errors['shortname']= get_string('shortnametaken', '', $foundcoursenamestring);
            }
        }

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }

        return $errors;
    }
}

