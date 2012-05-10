<?php

/**
 *  Handles event for course creator finished.
 **/
function browseby_sync_courses($data) {
    return browseby_extractor_callback('completed_requests', $data);
}

/**
 *  Handles event for course requests deleted.
 **/
function browseby_sync_deleted($data) {
    return browseby_extractor_callback('deleted_requests', $data);
}

function browseby_extractor_callback($field, $data) {
    if (empty($data->{$field})) {
        return true;
    }

    $r = browseby_extract_term_subjareas($data->{$field});

    if (!$r) {
        return true;
    }

    list($t, $s) = $r;
    return run_browseby_sync($t, $s);
}

/**
 *  Extracts distinct term and subjectareas from request sets.
 **/
function browseby_extract_term_subjareas($requests) {
    $subjareas = array();
    $terms = array();
    foreach ($requests as $request) {
        if (is_object($request)) {
            $request = get_object_vars($request);
        }

        if (!empty($request['term'])) {
            $t = $request['term'];

            $terms[$t] = $t;
        }

        if (!empty($request['subj_area'])) {
            $sa = $request['subj_area'];

            $subjareas[$sa] = $sa;
        }
    }

    if (empty($terms)) {
        return false;
    }

    return array($terms, $subjareas);
}

/**
 *  Starts and runs a browseby instance-sync.
 **/
function run_browseby_sync($terms, $subjareas=null, $forceall=false) {
    if (!$forceall && empty($terms)) {
        return true;
    }

    $b = block_instance('ucla_browseby');
    if ($forceall) {
        $terms = $b->get_all_terms();
    }

    $b->sync($terms, $subjareas);

    return true;
}
