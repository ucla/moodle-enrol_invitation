<?php

class block_ucla_course_menu_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG;
        $mform->addElement('header', 'configheader', 
            get_string('blockgeneralsettings', $this->block->blockname));
        
        $options = array(
            block_ucla_course_menu::TRIM_RIGHT   => 
                get_string('trimmoderight', $this->block->blockname),
            block_ucla_course_menu::TRIM_LEFT    => 
                get_string('trimmodeleft', $this->block->blockname),
            block_ucla_course_menu::TRIM_CENTER  => 
                get_string('trimmodecenter', $this->block->blockname)
        );

        $mform->addElement('select', 'config_trimmode', 
            get_string('trimmode', $this->block->blockname), $options);
        $mform->setType('config_trimmode', PARAM_INT);

        $mform->addElement('text', 'config_trimlength', 
            get_string('trimlength', $this->block->blockname));
        $mform->setDefault('config_trimlength', 
                get_config('block_ucla_course_menu', 'trimlength'));
        $mform->setType('config_trimlength', PARAM_INT);
    }
}

// EoF
