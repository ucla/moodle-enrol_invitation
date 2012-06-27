<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Return files for the NanoGong applet.
 *
 * @author     Ning
 * @author     Gibson
 * @package    filter
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/filelib.php');

require_login();  // CONTEXT_SYSTEM level

$contextid = required_param('contextid', PARAM_INT);
$modulename = required_param('modulename', PARAM_RAW);
$filearea = required_param('filearea', PARAM_RAW);
$itemid = required_param('itemid', PARAM_INT);
$name = required_param('filename', PARAM_RAW);

if ($itemid == 0)
    $relativepath = '/'.implode('/', array($contextid, $modulename, $filearea, $name));
else
    $relativepath = '/'.implode('/', array($contextid, $modulename, $filearea, $itemid, $name));
file_pluginfile($relativepath, false);

?>
