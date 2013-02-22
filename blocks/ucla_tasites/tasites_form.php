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
            'view' => array(),
            'build' => array() 
        );

        foreach ($tasiteinfo as $tainfo) {
            if (empty($tainfo->ta_site)) {
                $formaction = 'build';
            } else {
                //$formaction = 'delete';
                $formaction = 'view';
            }

            $formactions[$formaction][] = $tainfo;
        }
       
        foreach ($formactions as $action => $tas) {
            if (empty($tas)) {
                continue;
            }

            $mform->addElement('header', $action . '_header', 
                get_string($action . '_tasites', 'block_ucla_tasites'));

            if ($action == 'view') {
                $mform->addElement('html', html_writer::start_tag('ul'));
            }

            foreach ($tas as $ta) {
                // view is special
                if ($action == 'view') {
                    $ta_link = html_writer::link(
                            $ta->course_url,
                            get_string(
                                $action . '_tadesc', 'block_ucla_tasites',
                                $ta
                            ));
                    $ta_grouping = get_string('view_tadesc_grouping',
                            'block_ucla_tasites',
                            $ta->ta_site->defaultgroupingname);

                    $mform->addElement('html',
                        html_writer::tag('li', $ta_link . $ta_grouping));
                } else {    
                    // This specifies whether to take the action or not
                    $mform->addElement(
                        'checkbox', 
                        block_ucla_tasites::checkbox_naming($ta),
                        '',
                        get_string($action . '_tadesc', 'block_ucla_tasites', 
                            $ta)
                    );
                }

                // This specifies what action to take for the TA
                $mform->addElement(
                    'hidden',
                    block_ucla_tasites::action_naming($ta),
                    $action
                );
            }

            if ($action == 'view') {
                $mform->addElement('html', html_writer::end_tag('ul'));
            }
        }

        $this->add_action_buttons();
    }
}
