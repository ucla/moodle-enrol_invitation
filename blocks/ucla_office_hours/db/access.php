<?php
/*
 * Capabilities for office hours block.
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/ucla_office_hours:editothers' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )
    ),
);
