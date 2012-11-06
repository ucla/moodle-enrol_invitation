<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2012110500;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->component = 'block_ucla_weeksdisplay'; // Full name of the plugin (used for diagnostics)

$plugin->cron = 21600; // update 4x a day