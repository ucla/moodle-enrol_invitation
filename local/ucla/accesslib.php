<?php
/**
 *  Generic-scope usage functions.
 *
 *  These functions have no UCLA-related API usage.
 **/
function has_course_access($course, $user=null) {
    global $USER;
    
    if ($user === null) {
        $user = $USER;
    }

    if ($course->id == SITEID) {
        return true;
    }

    if (session_is_loggedinas()) {
        $user = session_get_realuser();
    }

    $userid = $user->id;
    
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id, 
        MUST_EXIST);

    return is_enrolled($coursecontext, $userid, '', true) 
        || is_viewing($coursecontext, $userid)
        || is_siteadmin($userid);
}

