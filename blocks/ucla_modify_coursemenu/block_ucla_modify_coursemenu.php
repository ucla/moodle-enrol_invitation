<?php
class block_ucla_modify_course_menu extends block_base {
    public function init() {
        $this->title = get_string('ucla_modify_course_menu', 'block_ucla_modify_course_menu');
    }
    
    public function get_content() {
    if ($this->content !== null) {
      return $this->content;
    }
 
    $this->content         =  new stdClass;
    $this->content->text   = 'The content of our SimpleHTML block!';
    $this->content->footer = 'Footer here...';
 
    return $this->content;
  }
}   //