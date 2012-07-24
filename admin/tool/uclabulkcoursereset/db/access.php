<?php

$capabilities = array(
    'tool/uclabulkcoursereset:edit' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager' => CAP_ALLOW
        ),
    )
);
