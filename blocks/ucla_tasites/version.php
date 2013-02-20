<?php

$plugin->version = 2013021900;

// Add dependency for meta courses and site indicator (for tasite type)
$plugin->dependencies = array(
    'enrol_meta'  => ANY_VERSION,
    'tool_uclasiteindicator' => 2013021900
);
