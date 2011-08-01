<?php 

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  UCLA's login page.
 *  This is essentially a clone of the Moodle login page, but without the
 *  logic to handle auth plugins.
 *
 *  @package core
 *  @copyright  2011 UCLA Regents
 **/

require_once(dirname(__FILE__) . "/../config.php");

$loginguest = optional_param('loginguest', 0, PARAM_INT);

// Modified 200706191502 by Eric Bollens to Remove Hardcoding
// Shibboleth requires HTTPS
$PAGE->https_required();
$CFG->httpswwwroot = str_replace("http://", "https://", $CFG->httpswwwroot);

/** Modified 20071214 by Jovca
 * If the user got here via standard Moodle login redirect, he'll have the 
 * string "shibboleth" as a GET parameter, thanks to the Moodle setting for 
 * alternative login URL.
 * If so, initiate Shibb login. Safety fallback: user coming to the page in 
 * an unexpected way will not have the ?shibboleth path, and login page will 
 * be displayed as usual.
 * In order for this code to work, Shibboleth module in Moodle has to be 
 * configured to use "UCLAlogin.php?shibboleth" for Alternate Login URL
 **/ 
// check for error msg during special cases login, skip redirect if there's one
if (empty($SESSION->ucla_login_error)) {
	$errormsg = false;
} else {
	$errormsg = $SESSION->ucla_login_error;
	$SESSION->ucla_login_error = NULL; 
}

$shibredir = $CFG->httpswwwroot . '/auth/shibboleth/index.php';
if (!$errormsg and optional_param('shibboleth', false, PARAM_BOOL) !== false) {
    redirect($shibredir);
    exit();
}

// In case of Moodle timeout, redirect to shibboleth login page too
if (optional_param('errorcode', 0, PARAM_INT)) {
    redirect($shibredir);
    exit();
}

// Modified on 200706201600 by Eric Bollens
// Original Modification on 200704201421 by Mike Franks and Keith Rozett
// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

if ($session_has_timed_out) {
    $errormsg = get_string('sessionerroruser', 'error');
}

// TODO see if this is where this belongs
if (get_moodle_cookie() == '') {
    set_moodle_cookie('nobody'); 
}

// Trim this I guess
if (isset($CFG->auth_instructions)) {
    $CFG->auth_instructions = trim($CFG->auth_instructions);
}

$show_instructions = false;
if (!empty($CFG->registerauth) or is_enabled_auth('none')
        || !empty($CFG->auth_instructions)) {
    $show_instructions = true;
}

// Prepare to display this thing
$site = get_site();

$loginsite = get_string('loginsite');

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url("$CFG->httpswwwroot/login/ucla_login.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$PAGE->navbar->add($loginsite);

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

// Practically copied from /login/index.php
$frm = data_submitted();

if ($loginguest && !$frm) {
    $frm->username = 'guest';
    $frm->password = 'guest';
}

if ($frm !== false && isset($frm->username)) {
    $frm->username = trim(moodle_strtolower($frm->username));

    if ($frm->username == 'guest' && empty($CFG->guestloginbutton)) {
        $user = false;
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $user = authenticate_user_login(
                $frm->username,
                $frm->password
            );
        }
    }

    if (isset($user) && $user !== false) {
        // Intercept 'restored' users to provide them with info & reset password
        if (!$user and $frm and is_restored_user($frm->username)) {
            $PAGE->set_title(get_string('restoredaccount'));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('restoredaccount'));
            echo $OUTPUT->box(get_string('restoredaccountinfo'), 
                'generalbox boxaligncenter');
            // Use our "supplanter" login_forgot_password_form. MDL-20846
            require_once('restored_password_form.php'); 
            $form = new login_forgot_password_form('forgot_password.php', 
                array('username' => $frm->username));
            $form->display();
            echo $OUTPUT->footer();
            die;
        }

        update_login_count();

        // language setup
        if (isguestuser($user)) {
            // no predefined language for guests - use existing session or 
            // default site lang
            unset($user->lang);

        } else if (!empty($user->lang)) {
            // unset previous session language - use user preference instead
            unset($SESSION->lang);
        }

        // This account was never confirmed
        if (empty($user->confirmed)) {      
            $PAGE->set_title(get_string("mustconfirm"));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            echo $OUTPUT->box(get_string("emailconfirmsent", "", $user->email), 
                    "generalbox boxaligncenter");
            echo $OUTPUT->footer();
            die;
        }

    /// Let's get them all set up.
        add_to_log(SITEID, 'user', 'login', 
                "view.php?id=$USER->id&course=".SITEID,
                $user->id, 0, $user->id);
        complete_user_login($user, true); // sets the username cookie

    /// Prepare redirection
        if (user_not_fully_set_up($USER)) {
            $urltogo = $CFG->wwwroot.'/user/edit.php';
            // We don't delete $SESSION->wantsurl yet, so we get there later

        } else if (isset($SESSION->wantsurl) 
            && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0 
                || strpos($SESSION->wantsurl, 
                        str_replace('http://', 'https://', $CFG->wwwroot))
                            === 0)) {
            /// Because it's an address in this site
            $urltogo = $SESSION->wantsurl;  
            unset($SESSION->wantsurl);

        } else {
            // no wantsurl stored or external - go to homepage
            $urltogo = $CFG->wwwroot.'/';
            unset($SESSION->wantsurl);
        }

    /// Go to my-moodle page instead of site homepage if defaulthomepage set 
    // to homepage_my
        if (!empty($CFG->defaulthomepage) 
                && $CFG->defaulthomepage == HOMEPAGE_MY 
                && !is_siteadmin() && !isguestuser()) {
            if ($urltogo == $CFG->wwwroot 
                    || $urltogo == $CFG->wwwroot.'/' 
                    || $urltogo == $CFG->wwwroot.'/index.php') {
                $urltogo = $CFG->wwwroot.'/my/';
            }
        }


    /// check if user password has expired
    /// Currently supported only for ldap-authentication module
        $userauth = get_auth_plugin($USER->auth);
        if (!empty($userauth->config->expiration) 
                    && $userauth->config->expiration == 1) {
            if ($userauth->can_change_password()) {
                $passwordchangeurl = $userauth->change_password_url();
                if (!$passwordchangeurl) {
                    $passwordchangeurl = $CFG->httpswwwroot
                        .'/login/change_password.php';
                }
            } else {
                $passwordchangeurl = $CFG->httpswwwroot
                    .'/login/change_password.php';
            }
            $days2expire = $userauth->password_expire($USER->username);
            $PAGE->set_title("$site->fullname: $loginsite");
            $PAGE->set_heading("$site->fullname");
            if (intval($days2expire) > 0 
                        && intval($days2expire) < intval(
                            $userauth->config->expiration_warning
                        )) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordwillexpire', 
                    'auth', $days2expire), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            } elseif (intval($days2expire) < 0 ) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordisexpired', 
                    'auth'), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            }
        }

        reset_login_count();

        // test the session actually works by redirecting to self
        $SESSION->wantsurl = $urltogo;
        redirect(new moodle_url(get_login_url(), 
            array('testsession' => $USER->id)));

    } else if (empty($errormsg)) {
        $errormsg = get_string('invalidlogin');
        $errorcode = 3;
    }
}

if ($session_has_timed_out && !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

if (get_moodle_cookie() === 'nobody') {
    $frm->username = '';
    $focus = "password";
} else {
    $frm->username = get_moodle_cookie();
    $focus = "username";
}

echo $OUTPUT->header();

// Hack to get things working like new

ob_start();
include("index_form.html");
$form = ob_get_clean();

$target_form = str_replace($CFG->httpswwwroot . '/login/index.php', 
    $PAGE->url, $form);

echo $target_form;
echo $OUTPUT->footer();

