<?php

/**
 * UCLA Site Indicator
 *
 * @package    uclasitindicator
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('uclasiteindicator', get_string('pluginname', 'tool_uclasiteindicator'), "$CFG->wwwroot/$CFG->admin/tool/uclasiteindicator/index.php"));
