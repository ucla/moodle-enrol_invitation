<?php

require_once(dirname(__FILE__) . '/ucla_cp_module.php');

class ucla_cp_mod_other extends ucla_cp_module {
    var $handler = 'general_descriptive_link';
    var $orientation = 'col';

    function get_content_array($course) {
        global $CFG;

        $others = array(
            array(
                'add_activity' => 'Add Activity!',
                'add_resource' => 'Add Resource!!',
                'add_subheading' => 'Add Subheading!!!',
                'add_text' => 'Add TEXT!!! OMG!!!1111!!'
            ), array (
                'import_classweb' => 'Importing classweb....',
                'import_moodle' => 'Import from course....',
                'create_tasite' => null,
                'view_roster' => new moodle_url(
                    $CFG->wwwroot . '/user/index.php',
                    array('id' => $course->id))
            )
        );

        return $others;
    }

    function handler_create_tasite($course, $self) {
        return $this->{$this->handler}($self, 'stuff');
    }
}
