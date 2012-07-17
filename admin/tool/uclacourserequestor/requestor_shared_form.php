<?php

require_once($CFG->libdir.'/formslib.php');

class requestor_shared_form extends moodleform {
    /** Determines which string to use as the submit button **/
    var $type = null;

    /** hack for conveniently not displaying terms for which there are
        no requests **/
    var $noterm = false;

    /** The name of the group in the quickform **/
    var $groupname = 'requestgroup';

    function definition() {
        $mform =& $this->_form;

        $term = $this->_customdata['selterm'];
        $terms = $this->_customdata['terms'];

        $requestline = array();

        $ucr = 'tool_uclacourserequestor';
        $gn = $this->groupname;
       
        if (!$this->noterm) {
            $requestline[] =& $mform->createElement('select', 'term', null,
                $terms);
        }

        $specline = $this->specification();
        if (is_array($specline)) {
            $requestline = array_merge($requestline, $specline);
        }

        $requestline[] =& $mform->createElement('submit', 'submit',
             get_string($this->type, $ucr));

        $mform->addGroup($requestline, $gn, null, ' ', true);
        $mform->setDefaults(
            array(
                $gn => array(
                    'term' => $term
                    )
                )
        );
        
        $this->post_specification();
    }

    /**
     *  Returns an array of mForm elements to attach into the group.
     *  Please override.
     **/
    function specification() {
        return false;
    }

    /** 
     *  Adds additional functionality after the group has been added to the
     *  quick form.
     *  Please override.
     **/
    function post_specification() {
        return false;
    }

    /**
     *  Returns the set of courses that should respond to the request method
     *  and parameters.
     *  Called after all the data has been verified.
     *  This function probably breaks a lot of OO-boundaries.
     *  @param $data responses from the fetch form.
     *  @return Array Sets of course-term-srs sets
     **/
    function respond($data) {
        return array();
    }
}

// EOF
