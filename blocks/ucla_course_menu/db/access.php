<?php

/**
 * UCLA Course Menu block caps.
 * Defined for Moodle 2.4 block capabilities requirement.
 * http://docs.moodle.org/24/en/Upgrading#Possible_issues_that_may_affect_you_in_Moodle_2.4
 *
 * Based on:
 * http://docs.moodle.org/dev/Blocks#db.2Faccess.php
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/ucla_course_menu:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);

?>
