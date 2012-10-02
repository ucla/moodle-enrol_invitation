<?php

require(dirname(__FILE__) . '/../../config.php');

require_oncE($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tasites_form.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/form_response.php');

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

$messages = array();

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

    $tasites_form = new tasites_form(null, $formdata);
    if ($data = $tasites_form->get_data()) {
        foreach ($tasiteinfo as $tasite) {
            $actionname = block_ucla_tasites::action_naming($tasite);

            if (empty($data->{$actionname})) {
                debugging('Could not find registered action for '   
                    . $tasite->username);

                continue;
            }
            
            $action = $data->{$actionname};
            $fn = 'block_ucla_tasites_respond_' . $action;

            // Confirm?
            // Create and Delete
            if (!empty($data->{block_ucla_tasites::checkbox_naming($tasite)})) {
                if (!function_exists($fn)) {
                    throw new block_ucla_tasites_exception('badresponse', $fn);
                }

                $messages[] = $fn($tasite);
            }
        }
    }
}

// Display everything!
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);
   
if (empty($tas_ra)) {
    $rolefullname = $DB->get_field('role', 'name', array(
            'id' => block_ucla_tasites::get_ta_role_id()
        ));

    throw new moodle_exception('no_tasites', 'block_ucla_tasites', '', 
            $rolefullname);
} else if (isset($data)) {
    foreach ($messages as $message) {
        echo $OUTPUT->box(get_string($message->mstr, 
            'block_ucla_tasites', $message->mstra));
    }

    $courseurl = new moodle_url('/course/view.php',
        array('id' => $courseid));

    $parentbutton = new single_button($courseurl, 
        get_string('returntocourse', 'block_ucla_tasites'), 'get');
    $parentbutton->class = 'continuebutton';

    echo $OUTPUT->render($parentbutton);
} else {
    $tasites_form->display();
}

echo $OUTPUT->footer();
