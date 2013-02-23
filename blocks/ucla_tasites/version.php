<?php

$plugin->version = 2013021900;

// Add dependency for meta courses, local_ucla (for flash),
// and site indicator (for tasite type)
$plugin->dependencies = array(
    'enrol_meta'  => ANY_VERSION,
    'local_ucla'  => 2012112800,
    'tool_uclasiteindicator' => 2013021900
);
