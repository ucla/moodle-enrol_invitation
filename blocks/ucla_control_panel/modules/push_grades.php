<?php

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/admin_submodule.php');

class ucla_cp_module_push_grades extends ucla_cp_module_admin {
    function __construct($course) {
        $param = array('console'=>'pushgrades',
                      'courseid'=>$course->id);

        parent::__construct($param);
    }

    function get_key() {
        return 'push_grades';
    }
}

