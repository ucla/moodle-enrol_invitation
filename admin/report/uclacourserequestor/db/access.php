<?php
/* 
 * CCLE-1723
 * Adding 'Support Admin' capability to course requestor
 * 
 */

$capabilities = array(
    'report/uclacourserequestor:view' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        ),
    )
)

?>