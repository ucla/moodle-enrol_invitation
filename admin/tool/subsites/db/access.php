<?php

$capabilities = array(
    'tool/subsites:canhavesubsite' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetype' => array(
            'teacher' => CAP_ALLOW
        ),
    )
);
