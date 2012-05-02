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
 * Command-line script for loading configuration settings
 *
 * The goal is to copy server settings exactly, except where
 * copying a setting creates server/database inconsistency.
 * See moodle/lib/configmanagementlib.php for details.
 *
 * arg[1]: Optional - A string containing the filename
 * arg[2]: Optional - A string which is placed around section headers
 *
 * @package   configmanagement
 * @copyright 2009 Jeffrey Su
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/admin/tool/configmanagement/lib.php');

// don't want this to be runnable

//global $divider;
//global $dividerlen;
//global $fp;
//    
//$dumpfile = get_string('configdefaultfile', 'tool_configmanagement');
//$divider = get_string('configdivider', 'tool_configmanagement');
//
//if ($argc > 1 && is_string($argv[1])) {
//    //$dumpfile = $argv[1];   //Allow more access 
//    $dumpfile = clean_param($argv[1], PARAM_PATH);    //Strict access
//}
//if ($argc > 2 && is_string($argv[2])) {
//    $divider = $argv[2];
//}
//$dividerlen = strlen($divider);
//
////NOTE: If file.php doesn't have security fixes, don't pick course 1
////      In standard Moodle installations, non-admins can access course 1 
//$dir = $CFG->dataroot.'/1/';
//if(file_exists($dir.$dumpfile) && $fp = fopen($dir.$dumpfile,'r')){
//    while (!feof($fp)) {
//        $line = fgets($fp);
//        
//        if (stripos($line, $divider.'Config'.$divider) !== false) {
//            update_config();
//            echo get_string('configconfigtable', 'tool_configmanagement')." completed\n";
//        }
//
//        if (stripos($line, $divider.'Plugins'.$divider) !== false) {
//            update_config_plugins();                
//            echo get_string('configconfigpluginstable', 'tool_configmanagement')." completed\n";
//        }
//
//        if (stripos($line, $divider.'Roles'.$divider) !== false) {
//            update_role_tables();                
//            echo get_string('configallroletables', 'tool_configmanagement')." completed\n";
//        }
//
//        if (stripos($line, $divider.'Users') !== false) {
//            update_special_case_logins();                
//            echo get_string('configusertable', 'tool_configmanagement')." completed\n";
//        }
//    }
//    
//    fclose($fp);
//}
//else {
//    die(get_string('configfileopenerror', 'admin', $dumpfile));
//}
?>
