<?php

defined('MOODLE_INTERNAL') || die();

class ucla_cp_module_admin extends ucla_cp_module {
    private $link_arguments;
    private $faulty = false;
    function __construct($link_param) {
        global $CFG;
        $link_arguments = $link_param;
        
        $init_action = new moodle_url($CFG->wwwroot
                . '/admin/tool/uclasupportconsole/index.php',
                $link_arguments);
        $this->faulty = $this->test_param($link_param);
        parent::__construct($this->get_key(), $init_action);
    }

    function autotag() {
        if($this->faulty) {
            return array('');
        }
        else {
            return array('ucla_cp_mod_admin_advanced');
        }
    }
    function get_key() {
        return '';
    }
    
    static function get_term_and_srs($course) {
        global $CFG, $DB;
        $idnumber = '';
        if (!empty($course->id)) {
            // only query for term-srs if course exists
            require_once($CFG->dirroot . '/local/ucla/lib.php');
            $course_info = ucla_get_course_info($course->id);    
            if (!empty($course_info)) {
                // create string
                $first_entry = true;
                foreach ($course_info as $course_record) {
                    $first_entry ? $first_entry = false : $idnumber .= ', ';
                    $idnumber .= make_idnumber($course_record);
                }                    
            }
        }
        $idnumber = explode('-', $idnumber);
        return $idnumber;
    }
    static function test_param($param) {
        if(array_key_exists('term', $param) && ($param['term'] == false)) {
            return true;
        }
        if(array_key_exists('srs', $param) && $param['srs'] == false) {
            return true;
        }
        if(array_key_exists('courseid', $param) && $param['courseid'] == false) {
            return true;
        }
        if(array_key_exists('console', $param) && $param['console'] == false) {
            return true;
        }
        return false;
    }
}
