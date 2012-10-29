<?php

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
        
        $img = $CFG->dirroot . '/theme/uclasharedcourse/pix/' . strtolower($category->name) . '/logo.png';
        
        // Override theme logo
        if(file_exists($img)) {

            $pix = strtolower($category->name) . '/logo';
            $address = new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
            
            $pix_url = $this->pix_url($pix, $this->theme);
            $logo_alt = $COURSE->fullname; //get_string('UCLA_CCLE_text', 'theme_uclashared');
            $logo_img = html_writer::empty_tag('img', array('src' => $pix_url, 'alt' => $logo_alt));
            $link = html_writer::link($address, $logo_img);

            $images = $this->course_logo_html($COURSE->id);
            return $link . $images;
        } 
        
        // Use default logo as a fallback
        return parent::logo($pix, $pix_loc);
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
    public function course_logo_save($course, $data) {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        
        file_save_draft_area_files($data->logo_attachments, 
            $coursecontext->id, $this->component, $this->filearea, 
            $course->id, $this->course_logo_config($course));        
    }
    
    /**
     * Get filepicker config
     * 
     * @param type $course
     * @return int
     */
    public function course_logo_config($course) {
        $maxbytes = get_max_upload_file_size(0, $course->maxbytes);
        
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
     * @param type $courseid
     * @return type
     */
    private function course_logo_images($courseid) {
        $coursecontext = context_course::instance($courseid);
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, $this->component, 
                $this->filearea, $courseid, '', false);
       
        return $files;
    }
    
    /**
     * Render HTML code for course logos
     * 
     * @param type $courseid
     * @return type
     */
    private function course_logo_html($courseid) {
        global $CFG;
        
        $logos = $this->course_logo_images($courseid);
        
        $out = '';
        
        if(!empty($logos)) {
            $pix_url = $this->pix_url('logo_divider', $this->theme);
            $img = html_writer::empty_tag('img', array('src' => $pix_url));
            $divider = html_writer::tag('div', $img, array('class' => 'uclashared-course-logo-divider'));
            $out .= $divider;
            
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
