<?php

/**
 * UCLA Site Indicator
 *
 * @package    uclasitindicator
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('uclacopyrightstatusreports', get_string('pluginname', 'tool_uclacopyrightstatusreports'), "$CFG->wwwroot/$CFG->admin/tool/uclacopyrightstatusreports/index.php"));
