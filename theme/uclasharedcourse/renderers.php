<?php

/**
 * Theme renderer for uclasharedcourse 
 */
class theme_uclasharedcourse_core_renderer extends theme_uclashared_core_renderer {

    private $theme = 'theme';
    
    /**
     * Display a custom category level logo + course logos
     * 
     * @param string $pix
     * @param type $pix_loc
     * @param moodle_url $address
     * @return type 
     */
    function logo($pix, $pix_loc, $address=null) {
        global $CFG, $COURSE, $DB;

        // @todo: make single query
        $course = $DB->get_record('course', array('id' => $COURSE->id));
        $category = $DB->get_record('course_categories', array('id' => $course->category));
        
        $img = $CFG->dirroot . '/theme/uclasharedcourse/pix/' . strtolower($category->name) . '/logo.png';
        
        // Override theme logo
        if(file_exists($img)) {

            $pix_loc = $this->theme;
            $pix = strtolower($category->name) . '/logo';
        
            if ($address == null) {
                $address = new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $COURSE->id);
            }
            
            $pix_url = $this->pix_url($pix, $pix_loc);
            $logo_alt = 'Anderson';//get_string('UCLA_CCLE_text', 'theme_uclashared');
            $logo_img = html_writer::empty_tag('img', array('src' => $pix_url, 'alt' => $logo_alt));
            $link = html_writer::link($address, $logo_img);

            return $link;
        } 
        
        // Use default logo as a fallback
        return parent::logo($pix, $pix_loc);
    }
}

// Not sure if this is needed
//class theme_uclasharedcourse_core_enrol_renderer extends theme_uclashared_core_enrol_renderer {
//
//    
//}