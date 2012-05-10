<?php
/**
 * CCLE-1723
 * Adding 'Support Admin' capability to course requestor
 **/
$capabilities = array(
    'tool/uclacourserequestor:edit' => array(
        'riskbitmask' => RISK_SPAM | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager' => CAP_ALLOW
        ),
    )
);

