<?php

class ucla_cp_module_assign_roles extends ucla_cp_module {
    function __construct($course, $home=false) {
        global $CFG;

        $this->action = new moodle_url($CFG->wwwroot . '/enrol/users.php',
            array('id' => $course->id));

        $this->home = $home;

        $this->shortname = $course->shortname;

        parent::__construct();
        
        if ($home) {
            $this->item_name .= '_master';
        }
    }

    function autotag() {
        return array('ucla_cp_mod_advanced');
    }

    function autocap() {
        return 'moodle/course:update';
    }
    
    function get_key() {
        if ($this->home) {
            $namer = 'assign_roles_0_' . $this->shortname;
        } else {
            $namer = 'assign_roles_1_' . $this->shortname;
        }

        return $namer;
    }
}
