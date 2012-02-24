<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');

class block_ucla_course_menu_renderer extends block_navigation_renderer {
    /**
     *  Calls block_navigation_renderer's protected function.
     **/
    public function navigation_node($i, $a=array(), $e=null, 
            $o=array(), $d=1) {
        return parent::navigation_node($i, $a, $e, $o, $d);
    }
}

// EoF
