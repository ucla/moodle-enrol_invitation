<?php

/**
 * UCLA Search block caps.
 *
 * Defined for Moodle 2.4 block capabilities requirement.
 * http://docs.moodle.org/24/en/Upgrading#Possible_issues_that_may_affect_you_in_Moodle_2.4
 *
 * Based on:
 * http://docs.moodle.org/dev/Blocks#db.2Faccess.php
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/ucla_search:myaddinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/ucla_search:addinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    )
);
