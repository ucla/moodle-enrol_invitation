<?php

class easy_upload_activity_form extends easy_upload_form {
    
    function specification() {
        $mform = $this->_form;

        $course = $mform->getElement('course')->getValue();

        // Actually, I do not like this here
        $modinfo =& get_fast_modinfo($course);
        get_all_mods($course->id, $mods, $modnames, 
            $modnamesplural, $modnamesused);

        $mform->addElement('hidden', 'redirectme', 
            '/course/modedit.php');

        // Add the select form
        $mform->addElement('select', 'add', '');

        // Stolen from course/lib.php:1838
        $archetype = plugin_supports('mod', $modname, 
                FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);

        if ($archetype == MOD_ARCHETYPE_RESOURCE) {
            $resources[$urlbase.$modname] = $modnamestr;
        } else {
            // all other archetypes are considered activity
            $activities[$urlbase.$modname] = $modnamestr;
        }
    }

    function get_coursemodule() {
        return false;
    }
}
