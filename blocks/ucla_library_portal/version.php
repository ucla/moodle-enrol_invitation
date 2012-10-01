<?php

/**
 * Defines the version of UCLA Library Research Portal 
 *
 * @package    block
 * @subpackage ucla_library_portal
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2012093000;               
$plugin->dependencies = array(
    'local_ucla' => ANY_VERSION,
);
$plugin->component = 'block_ucla_library_portal';