<?php

// Return the value of the Shibboleth cookie, or false if it does not exist
function get_shib_logged_in_cookie() {
    global $CFG;
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]) ? $_COOKIE[$CFG->shib_logged_in_cookie] : false;
}

// Check if an Shibboleth cookie exists
function is_shib_logged_in_cookie_set() {
    global $CFG;
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]);
}

// If the user is guest but an Shibboleth cookie exists, we "click" the "login" link for them
function require_user_finish_login() {
    global $CFG, $FULLME, $SESSION;
    if ((!isloggedin() || isguestuser()) && is_shib_logged_in_cookie_set()) {
        
        // If a flag is set in $SESSION indicating that the user has chosen "Guess Access"
        // in the login page, don't redirect her back to the login page
        if (isset($SESSION->ucla_login_as_guest) && $SESSION->ucla_login_as_guest === get_shib_logged_in_cookie())
            return;

        // Now using timeout value in new cookie for semi-lazy session initialization 
        // with Shibboleth cookie documented here:
        // https://spaces.ais.ucla.edu/display/iamuclabetadocs/DetectingShibbolethSession
        $login_cookie_value = get_shib_logged_in_cookie();
        if (strtotime($login_cookie_value) < time())
            return;
        
        // Otherwise, redirect the user to the login page and note in $SESSION->wantsurl that
        // the login page should eventually redirect back to this page
        $SESSION->wantsurl = $FULLME;
        redirect($CFG->wwwroot .'/login/index.php');
        exit();
    }
}

// Auto-login if user is guest
function auto_login_as_guest() {
    if ($USER->username == 'guest') {
        $flag = get_shib_logged_in_cookie();
        if ($flag === false) {
            unset($SESSION->ucla_login_as_guest);
        }
        else {
            $SESSION->ucla_login_as_guest = $flag;
        }
    }
}