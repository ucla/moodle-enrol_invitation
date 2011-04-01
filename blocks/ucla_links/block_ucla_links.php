<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class block_ucla_links extends block_base {
    function init() {
        $this->title = get_string('ucla_links', 'block_ucla_links');
        //version is now stored in version.php
    }
    
    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $course_id = $this->page->course->id; // course id
        $options = array('course_id'=>$course_id);


        $address = new moodle_url('/course/ucla_links.php', $options);
        $this->content          = new stdClass;
        $this->content->text    = "<p><a href=\"$address\">Useful UCLA Links</a></p>";

        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'course-format-week' => true,
            'my' => true,
            'blocks-ucla_links' => false // this option prevents the block from being shown
            );
    }
}
?>
