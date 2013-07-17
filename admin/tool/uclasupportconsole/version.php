<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version    = 2012062000;
$plugin->requires   = 2011022100;
$plugin->component  = 'tool_uclasupportconsole'; // Full name of the plugin (used for diagnostics)
$plugin->cron       = 86400;                     // Period for cron to check plugin (24 hours - in seconds)

$plugin->dependencies = array('local_ucla' => ANY_VERSION, 
                              'tool_uclacourserequestor' => ANY_VERSION);