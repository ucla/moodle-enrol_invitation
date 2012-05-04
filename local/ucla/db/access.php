<?php

$capabilities = array(
    // CCLE-2531 - REGISTRAR - Hiding course listing
    'local/ucla:viewall_courselisting' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    )
);

?>
