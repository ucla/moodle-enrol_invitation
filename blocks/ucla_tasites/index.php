<?php

require(dirname(__FILE__) . '/../../config.php');

require_oncE($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tasites_form.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/form_response.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));

require_login($courseid);
block_ucla_tasites::check_access($courseid);

if (block_ucla_tasites::is_tasite($courseid)) {
    throw new block_ucla_tasites_exception('xzibit');
}


$PAGE->set_url(new moodle_url(
        '/blocks/ucla_tasites/index.php', 
        array('courseid' => $courseid)
    ));

$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'block_ucla_tasites'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

// Get all potentional TA users and their according TA sites
// from {role_assignments}
$tas_ra = block_ucla_tasites::get_tasite_users($courseid);
if (!empty($tas_ra)) {
    // used for user_get_users_by_id
    $userids = array();

    // $tas_ra indexed-by userid
    $tas = array();

    foreach ($tas_ra as $ta_ra) {
        $userid = $ta_ra->userid;

        $userids[] = $userid;
        $tas[$userid] = $ta_ra;
    }

    // from {user}
    $users = user_get_users_by_id($userids);

    // from {enrol} indexed-by customint4
    $existing_tasites = block_ucla_tasites::get_tasites($courseid);

    //  array of pseudo class
    $tasiteinfo = array();
    foreach ($users as $userid => $user) {
        if (!empty($existing_tasites[$userid])) {
            // Associate ta to TA-site
            $ta_site = $existing_tasites[$userid];
            $user->ta_site = $ta_site;

            // These are all for display sake...
            $courseurl = new moodle_url('/course/view.php',
                array('id' => $ta_site->id));
            $user->course_url = $courseurl->out();

            $user->course_shortname = $ta_site->shortname;
        }

        // Some more shortcuts 
        $user->fullname = fullname($user);
        $user->parent_course = $course;

        $tasiteinfo[$userid] = $user;
    }

    $formdata = array(
        'courseid' => $courseid,
        'tasiteinfo' => $tasiteinfo
    );

    $tasites_form = new tasites_form(null, $formdata, 'post', '', array('class' => 'tasites_form'));
}

// process any forms, if user confirmed
if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
    foreach ($tasiteinfo as $tasite) {
        // what action is user trying to do?
        $actionname = block_ucla_tasites::action_naming($tasite);
        $action = optional_param($actionname, false, PARAM_ALPHA);
        if (empty($action)) {
            debugging('Could not find registered action for '
                . $tasite->username);
            continue;
        }

        $fn = 'block_ucla_tasites_respond_' . $action;

        // perform action
        $checkboxname = block_ucla_tasites::checkbox_naming($tasite);
        $checked = optional_param($checkboxname, false, PARAM_BOOL);
        if (!empty($checked)) {
            if (!function_exists($fn)) {
                throw new block_ucla_tasites_exception('badresponse', $fn);
            }
            $a = $fn($tasite);
            $messages[] = get_string($a->mstr, 'block_ucla_tasites', $a->mstra);
        }
    }

    // save messages in flash and redirect user
    $redirect = $url = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    // if there are many success messages, then display in list, else just
    // show one message
    if (count($messages) > 1) {
        $messages = html_writer::alist($messages);
    } else {
        $messages = array_pop($messages);
    }
    flash_redirect($redirect, $messages);

}

// Display everything else
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
if (empty($tas_ra)) {
    // user is accessing TA sites page when they cannot set one up
    $rolefullname = $DB->get_field('role', 'name', array(
            'id' => block_ucla_tasites::get_ta_role_id()
        ));

    throw new moodle_exception('no_tasites', 'block_ucla_tasites', '', 
            $rolefullname);
} else if (($params = $tasites_form->get_data()) && confirm_sesskey()) {
    // user submitted form, but first needs to confirm it
    
    // unset submit button value
    unset($params->submitbutton);

    // create confirm message, url passed needs to have all form elements. the
    // single_button renderer will make the url param array into hidden form
    // elements
    $params->sesskey = sesskey();
    $params->confirm = 1;
    $url = new moodle_url('/blocks/ucla_tasites/index.php', (array)$params);
    $button = new single_button($url, get_string('yes'), 'post');

    // Cancel button takes them back to the page the TA site page
    $return = $url = new moodle_url('/blocks/ucla_tasites/index.php',
            array('courseid' => $courseid));

    echo $OUTPUT->confirm(get_string('tasitecreateconfirm', 'block_ucla_tasites'),
            $button, $return);


} else {
    // display any messages, if any
    flash_display();
    $tasites_form->display();
}

echo $OUTPUT->footer();
