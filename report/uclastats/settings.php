<?php
/**
 * Settings file to add link to admin menu
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

// add link to admin report
$ADMIN->add('reports', new admin_externalpage('reportuclastats', 
        get_string('pluginname', 'report_uclastats'),
        "$CFG->wwwroot/report/uclastats/index.php", 'report/uclastats:view'));

// no report settings
$settings = null;
