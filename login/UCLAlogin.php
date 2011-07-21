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
if (!$errormsg and optional_param('shibboleth', false, PARAM_BOOL)) {
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
$PAGE->set_url("$CFG->httpswwwroot/login/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$PAGE->navbar->add($loginsite);

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

$frm = new stdclass();
if (get_moodle_cookie() === 'nobody') {
    $frm->username = '';
} else {
    $frm->username = get_moodle_cookie();
}

if (!empty($frm->username)) {
    $focus = "password";
} else {
    $focus = "username";
}

echo $OUTPUT->header();

include("index_form.html");

echo $OUTPUT->footer();

