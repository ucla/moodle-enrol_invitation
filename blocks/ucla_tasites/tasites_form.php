<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class tasites_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // $tasiteinfo
        extract($this->_customdata);

        $mform->addElement('hidden', 'courseid', $courseid);

        $formactions = array(
            'build' => array(), 'view' => array()
        );

        foreach ($tasiteinfo as $tainfo) {
            if (empty($tainfo->ta_site)) {
                $formactions['build'][] = $tainfo;
            } else {
                $formactions['delete'][] = $tainfo;
            }
        }
       
        foreach ($formactions as $action => $tas) {
            if (empty($tas)) {
                continue;
            }

            $mform->addElement('header', 'build', 
                get_string($action . '_tasites', 'block_ucla_tasites'));

            foreach ($tas as $ta) {
                $taid = $tainfo->id;

                $mform->addElement(
                    'checkbox', 
                    $taid . '-checkbox',
                    $ta->fullname,
                    get_string($action . '_tadesc', 'block_ucla_tasites', 
                        $ta)
                );

                $mform->addElement(
                    'hidden',
                    $taid . '-action',
                    $action
                );
            }
        }

        $this->add_action_buttons();
    }
}
