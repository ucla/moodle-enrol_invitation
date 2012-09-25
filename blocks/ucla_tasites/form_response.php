<?php

defined('MOODLE_INTERNAL') || die();
/**
 *  This means that the user wishes to delete the TA site.
 **/
function block_ucla_tasites_respond_delete($tainfo) {
    ob_start();
    delete_course($tainfo->ta_site->id);
    $tainfo->delete_text = ob_get_clean();
    $tainfo->course_fullname = $tainfo->ta_site->fullname;

    $r = new object();
    $r->mstr = 'deleted_tasite';
    $r->mstra = $tainfo;

    return $r;
}

/**
 *  This means that the user wishes to make a TA site.
 **/
function block_ucla_tasites_respond_build($tainfo) {
    $newcourse = block_ucla_tasites::create_tasite($tainfo);
    $courseurl = new moodle_url('/course/view.php',
        array('id' => $newcourse->id));

    $tainfo->course_url = $courseurl->out();
    $tainfo->course_shortname = $newcourse->shortname;

    $r = new object();
    $r->mstr = 'built_tasite';
    $r->mstra = $tainfo;

    return $r;
}
