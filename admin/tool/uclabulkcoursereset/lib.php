<?php

/** 
 * Returns an array of all modules that are not resettable. 
 * 
 * @return array
 */
function get_unsupported_modules() {
    global $CFG, $DB;
    $unsupported_mods = array();
    if ($allmods = $DB->get_records('modules') ) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $modfile = $CFG->dirroot."/mod/$modname/lib.php";
            $mod_reset_userdata = $modname.'_reset_userdata';
            if (file_exists($modfile)) {
                include_once($modfile);
                if (!function_exists($mod_reset_userdata)) {
                    $unsupported_mods[] = $modname;
                }
            } else {
                $unsupported_mods[] = $modname;
            }
        }
    }    
    return $unsupported_mods;
}