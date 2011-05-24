<?php

class ucla_cp_mod_common {
    function control_panel_contents() {

        $contents = array(
            array('add_link', 'email_students'),
            array('add_file', 'office_hours'),
            array('modify_sections', 'rearrange'),
            array('turn_editing_on')
        );

        return "This is where the Icon + Link stuff should be.";
    }
}
