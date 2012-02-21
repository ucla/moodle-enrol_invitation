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
 * Command-line script for saving configuration settings
 *
 * The saved file is human-readable, so it can be used for diff,
 * in addition to restoring to another server.
 * File is saved in text format using JSON encoding. See @link http://www.json.org.
 *
 * arg[1]: Optional - A string containing the filename
 * arg[2]: Optional - A string which is placed around section headers
 *
 * @package   configmanagement
 * @copyright 2009 Jeffrey Su
 * @author John Carter and Jeffrey Su
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../moodle/config.php');
require_once("$CFG->libdir/configmanagementlib.php");

$dumpfile = get_string('configdefaultfile', 'admin');
$divider = get_string('configdivider', 'admin');

if ($argc > 1 && is_string($argv[1])) {
    //$dumpfile = $argv[1];   //Allow more access 
    $dumpfile = clean_param($argv[1], PARAM_PATH);    //Strict access
}
if ($argc > 2 && is_string($argv[2])) {
    $divider = $argv[2];
}

//NOTE: If file.php doesn't have security fixes, don't pick course 1
//      In standard Moodle installations, non-admins can access those files 
$dir = $CFG->dataroot.'/1/';
if (!file_exists($dir)) {        
    if (!mkdir($dir, 0777, true)) {
        error('Cannot create directory', $ME);
    }
}

//Don't allow script to overwrite code!
if (strpos($dir, $CFG->dataroot) === false && file_exists($dir.$dumpfile)) {
    if (substr($dumpfile, -4, 4) === '.php' || substr($dumpfile, -4, 4) === '.html') {
        echo "$dumpfile is already in use.  Cannot overwrite.\n";
        die;
    }
}

//Open file for write
if($fp = fopen($dir.$dumpfile,'w')){
    //Write config
    fwrite($fp, $divider.'Config'.$divider."\n");
    $configlist = get_records('config','','','id');
    if ($configlist) {
        //Don't include these fields due to security reasons
        $excludeconfiglist = array("enrol_dbpass",
            'resource_secretphrase',
            'recaptchapublickey',
            'recaptchaprivatekey',
            'cronremotepassword',
            'proxyuser',
            'proxypassword',
            'quiz_password',
            'quiz_fix_password',
            'smtpuser',
            'smtppass',
            'supportemail',
            'supportname',
            'supportpage');

        foreach($configlist as $configentry){
            if(!in_array($configentry->name,$excludeconfiglist)){
                fwrite($fp, json_encode($configentry)."\n");
            }
        }
        echo get_string('configconfigtable', 'admin')." written.\n";
    }
    else {
        echo get_string('configconfigtable', 'admin')." skipped.\n";
    }

    //Write plugins
    fwrite($fp, $divider.'Plugins'.$divider."\n");
    $configpluginlist = get_records('config_plugins','','','id');
    if ($configpluginlist) {
        //Don't include these fields due to security reasons
        $excludepluginlist = array('openssl');
        foreach($configpluginlist as $configentry){
            if(!in_array($configentry->name,$excludepluginlist)){
                fwrite($fp, json_encode($configentry)."\n");
            }
        }		  
        echo get_string('configconfigpluginstable', 'admin')." written.\n";
    }
    else {
        echo get_string('configconfigpluginstable', 'admin')." skipped.\n";
    }

    //Write Roles, role capabilities, and related tables
    write_roles($fp, $divider, "\n");

    //Write a list of installed blocks
    fwrite($fp, $divider.'Blocks'.$divider."\n");
    $records = get_records('block', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configblocktable', 'admin')." written.\n";
    }
    else {
        echo get_string('configblocktable', 'admin')." skipped.\n";
    }
    
    fwrite($fp, $divider.'Modules'.$divider."\n");    
    $records = get_records('modules', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configmodulestable', 'admin')." written.\n";
    }
    else {
        echo get_string('configmodulestable', 'admin')." skipped.\n";
    }

    fwrite($fp, $divider.'Users'.$divider."\n");
    $records = get_records('user', 'auth', 'manual', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configusertable', 'admin')." written.\n";
    }
    else {
        get_string('configusertable', 'admin')." skipped.\n";
    }
    fclose($fp);
} else {
    die(get_string('configfileopenerror', 'admin', $dumpfile));
}
?>
