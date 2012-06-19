<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/requestor_view_form.php');

class requestor_hidden_srs_view_form extends requestor_view_form {
    var $type = 'viewrequest';

    function specification() {
        if (!isset($this->_customdata['srs'])) {
            $this->_customdata['srs'] = '';
        }

        $group = array();
        $group[] =& $this->_form->createElement('hidden', 'srs',
            $this->_customdata['srs']);
        $group[] =& $this->_form->createElement('hidden', 'term',
            $this->_customdata['selterm']);

        return $group;
    }

    function respond($data) {
        // Override which fields to check
        $this->_customdata['prefields'] = array(
                'srs' => array(),
                'term' => array() 
            );

        return parent::respond($data);
    }

    // Don't print yourself, but just return yourself
    function display() {
        return $this->_form->toHtml();
    }
}
