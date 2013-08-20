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
        
        // Check if public private is active
        if ($this->ppcourse->is_activated()) {
            // Check if mod is public
            $ppmod = new PublicPrivate_Module($mod->id);
            if ($ppmod->is_public()) {
                $mod->uservisible = true;
            }
            
            // If editing, then add the edit links
            if ($this->page->user_is_editing()) {
                $editactions = get_private_public($mod);
                $editactionshtml = $this->course_section_cm_edit_actions($editactions);

                $mod->set_after_edit_icons($editactionshtml);
            }
        }
        
        return parent::course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions);
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
                $pptext = html_writer::tag('span', 
                        get_string('publicprivategroupingname','local_publicprivate'));
                $mod->set_after_link($pptext);
            }
        }
        
        
        return parent::course_section_cm_name($mod, $displayoptions);
    }
    
}