<?php

/*
 * Define capability for visibility of the notification block
 */
$capabilities = array(

    'block/bruincast:viewblock' => array(
	'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_DENY,
            'student' => CAP_DENY,
            'guest' => CAP_DENY,
            'admin' => CAP_ALLOW,
            'teacher' => CAP_DENY
        )
    )
);
