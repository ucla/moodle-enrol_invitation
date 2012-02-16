<?php

require_once($CFG->libdir.'/formslib.php');

require_once(dirname(__FILE__) . '/requestor_shared_form.php');

// submit a class to be built
class requestor_srs_form extends requestor_shared_form {
    var $type = 'buildcourse';

    function specification() {
        $mform =& $this->_form;

        $spec = array();

        $srs[] =& $mform->createElement('text', 'srs', null, 
            array('size' => '25'));

        return $srs;
    }

    function post_specification() {
        $mform =& $this->_form;

        $mform->addGroupRule($this->groupname, 
            array(
                'srs' => array(
                    array(
                        get_string('srserror', 'tool_uclacourserequestor'), 
                            'regex', '/^[0-9]{9}$/', 'client'
                    )
                )
            )
        );
    }

    function respond($data) {
        $ci = $data->{$this->groupname};

        $hc = get_request_info($ci['term'], $ci['srs']);

        if ($hc) {
            $set = get_crosslist_set_for_host($hc);
        } else {
            return array();
        }

        return array($set);
    }
}

// EOF
