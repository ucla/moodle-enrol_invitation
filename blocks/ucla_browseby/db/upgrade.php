<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/db/install.php');
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG, $PAGE, $SITE;

    $result = true;

    if ($result && $oldversion < 2012032703) {
        xmldb_block_ucla_browseby_install();
    }

    // This adds an instance of this block to the site page if it
    // doesn't already exist
    $fakepage = new moodle_page();
    $fakepage->set_course($SITE);
    $fakepage->set_pagelayout('frontpage');
    $fakepage->set_pagetype('site-index');
    $bm =& $fakepage->blocks;
    $bm->load_blocks();
    $bm->create_all_block_instances();
    if (!$bm->is_block_present('ucla_browseby')) {
        $bm->add_block('ucla_browseby', BLOCK_POS_LEFT, 0, false); 
        // There's no API to guarantee that this was successful :D
    }

    return $result;
}
