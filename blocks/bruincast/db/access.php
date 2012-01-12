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
            'default' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);
