<?php

$capabilities = array(
    'tool/uclabulkcoursereset:edit' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetype' => array(
            'manager' => CAP_ALLOW
        ),
    )
);
