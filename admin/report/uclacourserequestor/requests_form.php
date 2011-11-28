<?php

require_once($CFG->libdir . '/formslib.php');

class requests_form extends moodleform {

    function definition() {
        $mf =& $this->_form;

        $requests = $this->customdata['requests'];

        foreach ($requests as $request) {
            $term = $request->term;
            $srs = $request->srs;

            $groupname = 'srs-request-' . $term . '-' . $srs;

            $mf->addGroup($requestline, $groupname, null, ' ', true);
        }
    }
}

