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
        
        // Convert section srs into main course srs
        global $CFG;
        require_once($CFG->dirroot . '/local/ucla/registrar/registrar_ccle_get_primary_srs.class.php');
        
        $main_srs = $ci['srs'];
        $t1 = registrar_query::run_registrar_query(
                'ccle_get_primary_srs', array($ci['term'], $ci['srs']), true);
        if (!empty($t1)) {
            $t2 = array_shift($t1);
            $main_srs = array_pop($t2);
        }
        
        $hc = get_request_info($ci['term'], $main_srs);

        if ($hc === false) {
            return $hc;
        } else if ($hc) {
            $set = get_crosslist_set_for_host($hc);
        } else {
            return array();
        }

        return array($set);
    }
}

// EOF
