<?php

require_once($CFG->libdir . '/formslib.php');

class course_requestor_view_form extends moodleform {
    function definition() {
        $mf =& $this->_form;

        $requests = $this->_customdata['requests'];

    }

}
