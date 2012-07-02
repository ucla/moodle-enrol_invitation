<?php

$capabilities = array(

    'report/rolescapabilities:view' => array(
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:viewreports',
    )
);
?>
