<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2012032704;
$plugin->component = 'block_ucla_browseby';
$plugin->cron = (60 * 60 * 24);
$plugin->dependencies = array('local_ucla' => 2012020100);
