<?php

/**
 * Capabilities
 *
 * @package     ucla
 * @subpackage  uclacopyrightstatusreports
 */

$capabilities = array(

    'tool/uclacopyrightstatusreports:view' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    ),
    
    'tool/uclacopyrightstatusreports:edit' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    )
);
