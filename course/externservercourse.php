<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');

function extern_server_course($course) {
    // This is a hack for friendly urls
    global $CFG, $PAGE;
        
    // This is very sad, but this is the only way, $PAGE->url
    // does not actually represent the page's url, so we have to 
    // use a super global
    if (empty($_SERVER['REQUEST_URI']) 
            || empty($_SERVER['HTTP_HOST'])
            || empty($_SERVER['HTTPS']) 
            || empty($_SERVER['REQUEST_METHOD'])) {
        return false;
    }

    if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        return false;
    }

    $courseshortname = $course->shortname;
        
    // This is a catch to check if friendly urls was the methodology
    // to reach this location, so not to infinitely redirect
    if (preg_match('"' . make_friendly_url($course) . '"',
            $_SERVER['REQUEST_URI'])) {
        return false;
    }

    $name_param = optional_param('name', '', PARAM_RAW);
    $id_param = optional_param('id', '', PARAM_INT);

    $friendlyurlsenabled = !empty($CFG->ucla_friendlyurls_enabled);
    $forcename = !empty($CFG->forcecoursegettoname) || $friendlyurlsenabled;

    $redirect = false;

    // Save old request's GET params
    $strippedrequesturi = str_replace($CFG->wwwroot, '',
        'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    $realurl = new moodle_url($strippedrequesturi);
   
    if ($forcename && (!$name_param || $id_param)) {
        // Although right now this is guaranteed to just be an array with
        // key 'id', who knows.
        $oldparams = $PAGE->url->params();
        $oldparams['name'] = $courseshortname;

        // Most of the time, this is the default but maybe later
        // 'idnumber' needs to be filtered out
        if (isset($oldparams['id'])) {
            unset($oldparams['id']);
        }

        $PAGE->set_url('/course/view.php', $oldparams);

        // OPTIMIZATION: no need to redirect twice
        $realurl->params(array('name' => $courseshortname));
        $realurl->remove_params('id');

        $redirect = $realurl;
    }

    if ($friendlyurlsenabled) {
        // Now we're going to really friendly URL this
        $realurl->remove_params('name');
        $friendly = new moodle_url(
            make_friendly_url($course),
            $realurl->params());
        $redirect = $friendly;
    }

    return $redirect;
}
