<?php

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/accesslib.php');

// Return the value of the ISIS/Shibboleth cookie, or false if it does not exist
function get_ucla_sso_flag() {
    global $CFG;
    return isset($_COOKIE[$CFG->ucla_sso_flag]) ? $_COOKIE[$CFG->ucla_sso_flag] : false;
}

// Check if an ISIS/Shibboleth cookie exists
function is_ucla_sso_flag_set() {
    global $CFG;
    return isset($_COOKIE[$CFG->ucla_sso_flag]);
}

// If the user is guest but an ISIS/Shibboleth cookie exists, we "click" the "login" link for them
function require_user_finish_login() {
    global $CFG, $FULLME, $SESSION;
    if ((!isloggedin() || isguestuser()) && is_ucla_sso_flag_set()) {
        // If a flag is set in $SESSION indicating that the user has chosen "Guess Access"
        // in the login page, don't redirect her back to the login page

        if (isset($SESSION->ucla_login_as_guest) && $SESSION->ucla_login_as_guest === get_ucla_sso_flag())
            return;

// Begin SSC Modification 408
        // Now using timeout value in new cookie for semi-lazy session initialization 
        // with Shibboleth cookie documented here:
// https://spaces.ais.ucla.edu/display/iamuclabetadocs/DetectingShibbolethSession
        $login_cookie_value = get_ucla_sso_flag();
        if (strtotime($login_cookie_value) < time())
            return;
// End SSC Modification 408
        // Otherwise, redirect the user to the login page and note in $SESSION->wantsurl that
        // the login page should eventually redirect back to this page

        $SESSION->wantsurl = $FULLME;
        redirect($CFG->wwwroot .'/login/index.php');
        exit();
    }
}
