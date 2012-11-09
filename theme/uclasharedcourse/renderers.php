<?php

require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

/**
 * Theme renderer for uclasharedcourse 
 */
class theme_uclasharedcourse_core_renderer extends theme_uclashared_core_renderer {

    public $coursetheme = true;
    
    private $theme = 'theme';
    private $component = 'theme_uclasharedcourse';
    private $filearea = 'course_logos';
    
    /**
     * Display a custom category level logo + course logos, this overrides
     * the standard CCLE logo
     * 
     * @param string $pix
     * @param type $pix_loc
     * @param moodle_url $address
     * @return type 
     */
    function logo($pix, $pix_loc, $address=null) {
        global $CFG, $COURSE, $DB;

        $category = $DB->get_record('course_categories', array('id' => $COURSE->category));
        $category->name = strtolower(str_replace(' ', '_', trim($category->name)));

        $img = $CFG->dirroot . '/theme/uclasharedcourse/pix/' . $category->name . '/logo.png';
        
        // Override theme logo
        $alternative_logo = '';
        if(file_exists($img)) {
            $pix = $category->name . '/logo';
            $address = new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
            
            $pix_url = $this->pix_url($pix, $this->theme);
            $logo_alt = $COURSE->fullname; //get_string('UCLA_CCLE_text', 'theme_uclashared');
            $logo_img = html_writer::empty_tag('img', array('src' => $pix_url, 'alt' => $logo_alt));
            $alternative_logo = html_writer::link($address, $logo_img);
        }

        // If a site is 'private', then we only display logos to enrolled users
        $collabsite = siteindicator_site::load($COURSE->id);
        $is_private = false;
        if(!empty($collabsite) && $collabsite->property->type == siteindicator_manager::SITE_TYPE_PRIVATE) {
            $is_private = true;
        }

        // Now look for additional logos
        $additional_logos = '';
        if (($is_private && $this->is_enrolled_user()) || !$is_private) {
            $additional_logos = $this->course_logo_html($COURSE->id);
        }

        // if main logo is overridden, then return that html
        if (!empty($alternative_logo)) {
            return $alternative_logo . $additional_logos;
        } else if (!empty($additional_logos)) {
            // maybe we just have alternative sublogos, but keep main logo
            $main_logo = parent::logo($pix, $pix_loc);
            return $main_logo . $additional_logos;
        } else {
            // Use default logo as a fallback
            return parent::logo($pix, $pix_loc);
        }
    }
    
    /**
     * Checks if a user is enrolled in the course
     * 
     * @return type 
     */
    function is_enrolled_user() {
        global $USER, $COURSE;
        $context = context_course::instance($COURSE->id);
        
        // Also allow managers to view the logos
        return (is_enrolled($context, $USER) || has_capability('moodle/course:update', $context));
    }
    
    /**
     * We don't want to display week
     * 
     * @return empty string
     */
    public function weeks_display() {
        return '';
    }

    /**
     * We don't want to display sublogo
     * 
     * @return empty string 
     */
    public function sublogo() {
        return '';
    }

    /**
     * Save course logos
     * 
     * @param object $course
     * @param object $data
     */
    public function course_logo_save($data) {
        global $COURSE;
        $context = context_course::instance($COURSE->id);
        
        file_save_draft_area_files($data->logo_attachments, 
            $context->id, $this->component, $this->filearea,
            $COURSE->id, $this->course_logo_config());        
    }
    
    /**
     * Get filepicker config
     * 
     * @return int
     */
    public function course_logo_config() {
        global $COURSE;
        
        $maxbytes = get_max_upload_file_size(0, $COURSE->maxbytes);
        
        $config = array(
            'subdirs' => 0, 
            'maxbytes' => $maxbytes, 
            'maxfiles' => 2, 
            'accepted_types' => array('*.png')
        );
        
        return $config;
    }
    
    /**
     * Retrieve logo images for a course
     * 
     * @return type
     */
    private function course_logo_images() {
        global $COURSE;        
        $context = context_course::instance($COURSE->id);
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, $this->component,
                $this->filearea, $COURSE->id, '', false);
       
        return $files;
    }
    
    /**
     * Render HTML code for course logos
     * 
     * @return type
     */
    private function course_logo_html() {
        global $CFG, $COURSE;
        $logos = $this->course_logo_images($COURSE->id);
        
        $out = '';
        if(!empty($logos)) {
            $pix_url = $this->pix_url('logo_divider', $this->theme);
            $img = html_writer::empty_tag('img', array('src' => $pix_url));
            $divider = html_writer::tag('div', $img, array('class' => 'uclashared-course-logo-divider'));
            $out .= $divider;
            
            // Sort by filename
            if(count($logos) > 1) {
                $logo1 = array_shift($logos);
                $logo2 = array_shift($logos);
                
                if($logo2->get_filename() > $logo1->get_filename()) {
                    $logos[] = $logo1;
                    $logos[] = $logo2;
                } else {
                    $logos[] = $logo2;
                    $logos[] = $logo1;
                }
            }
            
            foreach($logos as $logo) {
                $url = "{$CFG->wwwroot}/pluginfile.php/{$logo->get_contextid()}/{$this->component}/{$this->filearea}";
                $fileurl = $url . $logo->get_filepath() . $logo->get_itemid() . '/' . $logo->get_filename();
                
                $img = html_writer::empty_tag('img', array(
                    'src' => $fileurl,
                ));
                
                $div = html_writer::tag('div', $img, array('class' => 'uclashared-course-logo'));
                $out .= $div;

            }
        }
        
        return $out;
    }
}
