<?php
/* 
 * CCLE-1723
 * Adding 'Support Admin' capability to course requestor
 * 
 */

$report_courserequestor_capabilities = array(
    'report/courserequestor:view' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        ),
    )
)

?>
