<?php

require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot.'/local/publicprivate/lib/module.class.php');
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot.'/course/renderer.php');

class local_publicprivate_renderer extends core_course_renderer {
    
    protected $ppcourse;
    
    /**
     * Public private needs to override the display of course module links.  To 
     * achieve this with minimal core edits, it's necessary to write the core_course_renderer
     * and modify $mods
     * 
     * @param moodle_page $page
     * @param type $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        
        // Public private course?
        $this->ppcourse = new PublicPrivate_Course($page->course->id);
    }
    
    /**
     * Override the visibility of a module link to display if 'public'
     * 
     * @param type $course
     * @param type $completioninfo
     * @param cm_info $mod
     * @param type $sectionreturn
     * @param type $displayoptions
     * @return type
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        
        if ($this->ppcourse->is_activated()) {
                // Check if mod is public
            $ppmod = new PublicPrivate_Module($mod->id);
            if ($ppmod->is_public()) {
                $mod->uservisible = true;
            }
        }
        
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2a) The 'showavailability' option is not set (if that is set,
        //     we need to display the activity so we can show
        //     availability info)
        // or
        // 2b) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible &&
            (empty($mod->showavailability) || empty($mod->availableinfo))) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }
        $output .= html_writer::start_tag('div', array('class' => $indentclasses));

        // Start the div for the activity title, excluding the edit icons.
        $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));

        // Display the link to the module (or do nothing if module has no url)
        $output .= $this->course_section_cm_name($mod, $displayoptions);

        // Module can put text after the link (e.g. forum unread)
        $output .= $mod->get_after_link();

        // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
        $output .= html_writer::end_tag('div'); // .activityinstance

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->get_url();
        if (empty($url)) {
            $output .= $contentpart;
        }

        /// 
        if ($this->page->user_is_editing()) {
            
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            
            // If editing, then add the edit links
            if ($this->page->user_is_editing()) {
                $ppeditactions = get_private_public($mod, $sectionreturn);
                $editactions = array_merge($editactions, $ppeditactions);
            }

            $output .= ' '. $this->course_section_cm_edit_actions($editactions);
            $output .= $mod->get_after_edit_icons();
        }

        $output .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // show availability info (if module is not available)
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // $indentclasses
        return $output;
    }
    
    /**
     * If a module is public, do not show the 'Private course material' label
     * 
     * @param cm_info $mod
     * @param type $displayoptions
     * @return type
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
        
        if ($this->ppcourse->is_activated()) {
            
            $ppmod = new PublicPrivate_Module($mod->id);
            if ($ppmod->is_public()) {
                $mod->groupingid = null;
            }
            
            // Moodle labels are not printed, so we must add the grouping name manually
            if (strtolower($mod->modfullname) === 'label' && $ppmod->is_private()) {
                $pptext = html_writer::span(get_string('publicprivategroupingname','local_publicprivate'),
                        'groupinglabel');
                $mod->set_after_link($pptext);
            }
        }
        
        return parent::course_section_cm_name($mod, $displayoptions);
    }
    
}