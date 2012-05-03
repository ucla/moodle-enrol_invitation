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
 * Admin Interface for saving and loading configuration settings.
 *
 * The saved file is human-readable, so it can be used for diff,
 * in addition to restoring to another server.
 * When restoring, the goal is to copy server settings exactly, except where
 * copying a setting creates server/database inconsistency.
 * File is saved in text format using JSON encoding.  See @link http://www.json.org.
 *
 * @package   configmanagement
 * @copyright 2009 Jeffrey Su
 * @author John Carter and Jeffrey Su
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/configmanagement/lib.php');

require_login();
global $USER;
global $ME;
global $DB;

// Initialize $PAGE
$PAGE->set_url($CFG->dirroot . '/admin/tool/configmanagement/index.php');
$PAGE->set_context(get_system_context());
$PAGE->set_heading(get_string('pluginname', 'tool_configmanagement'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

$redirectlink = $CFG->wwwroot . '/' . $CFG->admin . '/tool/configmanagement/index.php';

if (!is_siteadmin($USER->id)) {
    print_error('accessdenied', 'admin');
}

// Prepare and load Moodle Admin interface
$adminroot = admin_get_root();
admin_externalpage_setup('configmanagement');
echo $OUTPUT->header();

//NOTE: If file.php doesn't have security fixes, don't pick course 1
//      In standard Moodle installations, non-admins can access those files 
$dir = $CFG->dataroot . '/configmanagement/';

// SSC MODIFICATION #1161 deleted confirmation page prompt
if (optional_param('save', NULL, PARAM_TEXT) != NULL) {
    //Save Configuration Settings
    if (!($dumpfile = optional_param('savefile', NULL, PARAM_FILE))) {
        // CCLE-164
        // Name format that we want: type_configdump_date_time.txt
        $dumpfile = optional_param('configoptions', 'diff', PARAM_ALPHA) . "_configdump_";
        $dumpfile .= date('m.d.y_a.g.i') . ".txt";
    }
    $dumpfile = basename($dumpfile);

    // Do not write anything if all fields are missing
    if (!optional_param('config', 0, PARAM_BOOL) && !optional_param('plugins', 0, PARAM_BOOL) && !optional_param('roles', 0, PARAM_BOOL)
            && !optional_param('role_allow_assign', 0, PARAM_BOOL) && !optional_param('role_allow_override', 0, PARAM_BOOL)
            && !optional_param('role_assignments', 0, PARAM_BOOL) && !optional_param('role_capabilities', 0, PARAM_BOOL)
            && !optional_param('role_names', 0, PARAM_BOOL) && !optional_param('role_sortorder', 0, PARAM_BOOL)
            && !optional_param('blocks', 0, PARAM_BOOL) && !optional_param('mdodules', 0, PARAM_BOOL)
            && !optional_param('user', 0, PARAM_BOOL) && !optional_param('configphp', 0, PARAM_BOOL)) {
        print_error('configerrornofilemsg', 'tool_configmanagement', $redirectlink);
    }

    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            print_error('configerror_dirfail', 'tool_configmanagement', $redirectlink);
        }
    }

    if (strpos($dir, $CFG->dataroot) === false && file_exists($dir . $dumpfile)) {
        //If we're not saving in data diretory, then be sure to to protect code!
        if (substr($dumpfile, -4, 4) === '.php' || substr($dumpfile, -4, 4) === '.html') {
            print_error('configerror_cannotoverwrite', 'tool_configmanagement', $redirectlink);
        }
    }
    $divider = get_string('configdivider', 'tool_configmanagement');
    // Use this time to keep time fields constant for diff
    $difftime = 1234567890;
    $dodiff = (optional_param('configoptions', 0, PARAM_BOOL) == 'diff') ? true : false;
    $diffexclude = ($dodiff && optional_param('exc_id_time', 0, PARAM_BOOL)) ? true : false;

    echo $OUTPUT->heading(get_string('configsaveconfiguration', 'tool_configmanagement'));

    // CCLE-164 - show filename and location
    echo html_writer::tag('p', 'Settings saved to file: ' . "$dir.$dumpfile", array('class' => 'mdl-align'));

    //Open file for write
    if ($fp = fopen($dir . $dumpfile, 'w')) {
        //Write config
        $configlist = NULL;
        if (optional_param('config', 0, PARAM_BOOL)) {
            fwrite($fp, $divider . 'Config' . $divider . "\n");
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $configlist = $DB->get_records('config', NULL, $sort);
        }
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

            foreach ($configlist as $configentry) {
                // Exclude ID's if doing a diff
                if ($diffexclude) {
                    $configentry->id = "";
                }
                if (!in_array($configentry->name, $excludeconfiglist)) {
                    fwrite($fp, json_encode($configentry) . "\n");
                }
            }
            echo html_writer::tag('p', get_string('configconfigtable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configconfigtable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }

        // SSC MODIFICATION #1161 changed fwrite for "===<table name>===" to only write after $DB->get_records is returned true
        // diff_configdumps now do not include table names if that table was not selected or empty.

        echo html_writer::start_tag('div', array('id' => "configmanagement"));
        //Write plugins
        $configpluginlist = NULL;
        if (optional_param('plugins', 0, PARAM_BOOL)) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $configpluginlist = $DB->get_records('config_plugins', NULL, $sort);
        }
        if ($configpluginlist) {
            fwrite($fp, $divider . 'Plugins' . $divider . "\n");
            //Don't include these fields due to security reasons
            $excludepluginlist = array('openssl');
            foreach ($configpluginlist as $configentry) {
                // Exclude ID's if doing a diff
                if ($diffexclude) {
                    $configentry->id = "";
                }
                if (!in_array($configentry->name, $excludepluginlist)) {
                    fwrite($fp, json_encode($configentry) . "\n");
                }
            }
            echo html_writer::tag('p', get_string('configconfigpluginstable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configconfigpluginstable', 'tool_configmanagement') . " skipped", array('class' => 'mdl-align redfont'));
        }

        //Write Roles, role capabilities, and related tables
        //Roles
        $records = NULL;
        if (optional_param('roles', 0, PARAM_BOOL)) {
            // Sort by shortname if doing diff
            $sort = ($dodiff) ? 'shortname' : 'id';
            $records = $DB->get_records('role', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Roles' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configroletable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configroletable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Role Allow Assign
        if (optional_param('role_allow_assign', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = $DB->get_records('role_allow_assign', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Role_Allow_Assign' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configroleallowassigntable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configroleallowassigntable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Role Allow Override

        if (optional_param('role_allow_override', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = $DB->get_records('role_allow_override', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Role_Allow_Override' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configroleallowoverridetable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configroleallowoverridetable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Role Assignments
        $rs = NULL;

        if (optional_param('role_assignments', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            // We only want to pick up role_assignments from users with manual authentication
            $query = "SELECT rol.* FROM mdl_role_assignments rol
            INNER JOIN mdl_user usr ON usr.id = rol.userid
            WHERE usr.auth = 'manual'
            ORDER BY $sort";

            //Use $DB->get_recordset because role_assignments could be large
            $rs = $DB->get_recordset_sql($query);
        }

        $rs_notempty = false;
        if (!empty($rs)) {
            foreach ($rs as $record) {
                if (!$rs_notempty) {
                    fwrite($fp, $divider . 'Role_Assignments' . $divider . "\n");
                    $rs_notempty = true;
                }
                // Exclude ID's if doing a diff -- also exclude timestart, timemodified
                if ($diffexclude) {
                    $record->id = "";
                    $record->timestart = $difftime;
                    $record->timemodified = $difftime;
                }
                fwrite($fp, json_encode($record) . "\n");
            }
            $rs->close();
        }
        if ($rs_notempty) {
            echo html_writer::tag('p', get_string('configroleassignmentstable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configroleassignmentstable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        unset($rs); //Clean-up
        //Role Capabilities
        if (optional_param('role_capabilities', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = $DB->get_records('role_capabilities', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Role_Capabilities' . $divider . "\n");
            if ($dodiff) {
                // If we're doing a diff, we're going to sort first by
                // roleid, then we're going to sort by capabilities.

                $roleid = 1;
                $allrecs = array();
                // Expect these to be sorted by roleid
                foreach ($records as $record) {
                    if ($record->roleid == $roleid) {
                        $allrecs[$roleid][] = $record;
                    } else {
                        // Increment the ID, so that we start a new array entry
                        $roleid++;
                        // Move back pointer...
                        prev($records);
                    }
                }
                // Now sort $allrecs entries by capabilities
                foreach ($allrecs as $recs) {
                    usort($recs, "rolecap_cmp");
                    foreach ($recs as $entry) {
                        if ($diffexclude) {
                            $entry->id = "";
                            $entry->timemodified = $difftime;
                        }
                        if (isset($entry->modifierid)) {
                            $entry->modifierid = null;
                        }
                        fwrite($fp, json_encode($entry) . "\n");
                    }
                }
            } else {
                //Set modifierid to null, it doesn't make sense in other servers
                foreach ($records as $entry) {
                    // Exclude ID's if doing a diff
                    if ($diffexclude) {
                        $entry->id = "";
                        $entry->timemodified = $difftime;
                    }
                    if (isset($entry->modifierid)) {
                        $entry->modifierid = null;
                    }
                    fwrite($fp, json_encode($entry) . "\n");
                }
            }
            echo html_writer::tag('p', get_string('configrolecapabilitiestable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configrolecapabilitiestable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Role Names
        if (optional_param('role_names', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = $DB->get_records('role_names', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Role_Names' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configrolenamestable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configrolenamestable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Role Sort Order
        if (optional_param('role_sortorder', 0, PARAM_BOOL)) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = $DB->get_records('role_sortorder', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Role_SortOrder' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configrolesortordertable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configrolesortordertable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;

        //Write a list of installed blocks
        if (optional_param('blocks', 0, PARAM_BOOL)) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $records = $DB->get_records('block', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Blocks' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configblocktable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configblocktable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;


        if (optional_param('modules', 0, PARAM_BOOL)) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $records = $DB->get_records('modules', NULL, $sort);
        }
        if ($records) {
            fwrite($fp, $divider . 'Modules' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo html_writer::tag('p', get_string('configmodulestable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configmodulestable', 'tool_configmanagement') . " skipped.", array('class' => 'mdl-align redfont'));
        }
        $records = NULL;


        if (optional_param('user', 0, PARAM_BOOL)) {
            // Sort by username if doing diff
            $sort = ($dodiff) ? 'username' : 'id';
            $records = $DB->get_records('user', array('auth' => 'manual'), $sort); //Get only manual accounts (normal Moodle logins)
            //$records = $DB->get_records('user', '', '', 'id'); //Get all users
        }
        if ($records) {
            fwrite($fp, $divider . 'Users' . $divider . "\n");
            // Exclude ID's if doing a diff
            if ($diffexclude) {
                foreach ($records as $record) {
                    $record->id = "";
                    $record->firstaccess = $difftime;
                    $record->lastaccess = $difftime;
                    $record->lastlogin = $difftime;
                    $record->currentlogin = $difftime;
                }
            }
            write_records_to_file($fp, $records);
            //write_admin_users_to_file($fp, $records);   //Write only admins
            echo html_writer::tag('p', get_string('configusertable', 'tool_configmanagement') . " written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', get_string('configusertable', 'tool_configmanagement') . "  skipped.", array('class' => 'mdl-align redfont'));
        }

        // write the values from config.php
        if (optional_param('configphp', 0, PARAM_BOOL)) {
            fwrite($fp, $divider . 'config.PHP' . $divider . "\n");
            write_configphp($fp);
            echo html_writer::tag('p', "Config.php written.", array('class' => 'mdl-align'));
        } else {
            echo html_writer::tag('p', "Config.php skipped.", array('class' => 'mdl-align redfont'));
        }
        print_continue("index.php");
        fclose($fp);
        echo html_writer::end_tag('div');
    } else {
        print_error('configfileopenerror', 'tool_configmanagement', $redirectlink, $dumpfile);
    }
} else {
    //User Interface
    $filedate = date('m.d.y_a.g.i');
    
    // TO DO: Move all this js to outside file, so that it can be cached,
    // and maybe use YUI
    echo '
    <script type="text/javascript">
    // START SSC MODIFICATION #1161 changed the default so that roles are now unchecked
    
    var diffTables = new Array("config","plugins","blocks","modules","configphp","exc_id_time");
    var allTables = new Array("config","plugins","roles","role_allow_assign","role_allow_override","role_assignments","role_capabilities",
                              "role_names","role_sortorder","blocks","modules","user","configphp","exc_id_time");
    var partialConfigTables = new Array("config","plugins","exc_id_time");
    var allConfigTables = new Array("config","plugins","roles","role_allow_assign","role_allow_override","role_assignments","role_capabilities",
                                    "role_names","role_sortorder","blocks","modules","user","configphp");
    // END SSC MODIFICATION #1161
    
    function selectDiff(){
        enableCheckBoxes();
        setChecked(0,allTables);
        setChecked(1,diffTables);
        document.getElementById("configmsg").innerHTML = "' . get_string('configselectdiffmsg', 'tool_configmanagement') . '  <a href=\"javascript:setChecked(1,allTables)\">Set all</a> or <a href=\"javascript:setChecked(0,allTables)\">clear all</a>";
        document.configmanagement.elements["savefile"].value = "diff_configdump_' . $filedate . '.txt";
        document.getElementById("configfilename").innerHTML = "diff_configdump_' . $filedate . '.txt";
    }
    function selectFullConfig() {
        disableCheckBoxes();
        setChecked(0,allTables);
        setChecked(1,allConfigTables);
        document.getElementById("configmsg").innerHTML = "' . get_string('configselectfullconfigmsg', 'tool_configmanagement') . '";
        document.configmanagement.elements["savefile"].value = "full_configdump_' . $filedate . '.txt";
        document.getElementById("configfilename").innerHTML = "full_configdump_' . $filedate . '.txt";
    }
    function selectPartialConfig() {
        disableCheckBoxes();
        setChecked(0,allTables);
        setChecked(1,partialConfigTables);
        document.getElementById("configmsg").innerHTML = "' . get_string('configslectminconfigmsg', 'tool_configmanagement') . '";
        document.configmanagement.elements["savefile"].value = "min_configdump_' . $filedate . '.txt";
        document.getElementById("configfilename").innerHTML = "min_configdump_' . $filedate . '.txt";
    }
    
    function enableCheckBoxes() {
        len = document.configmanagement.elements.length;
        for( i = 0; i < len; i++) {
            if ( in_jsarray(document.configmanagement.elements[i].name, allTables) && document.configmanagement.elements[i].disabled ) {
                document.configmanagement.elements[i].disabled = false;
            }
        }
    }
    function disableCheckBoxes() {
        len = document.configmanagement.elements.length;
        for( i = 0; i < len; i++) {
            if ( in_jsarray(document.configmanagement.elements[i].name, allTables) && !document.configmanagement.elements[i].disabled ) {
                document.configmanagement.elements[i].disabled = true;
            }
        }
    }
    function setChecked(checkVal, selectTables) {
        len = document.configmanagement.elements.length;
        if( checkVal ) {
            for( i = 0; i < len; i++) {
                if (in_jsarray(document.configmanagement.elements[i].name, selectTables)) {
                    document.configmanagement.elements[i].checked = checkVal;
                }
            }
        } else {
            for( i = 0; i < len; i++) {
                if ( in_jsarray(document.configmanagement.elements[i].name, selectTables) ) {
                    document.configmanagement.elements[i].checked = checkVal;
                }
            }
        }
    }
    function validateConfigForm(oform){
        if(!document.configmanagement.elements["configoptions"][0].checked &&
           !document.configmanagement.elements["configoptions"][1].checked &&
           !document.configmanagement.elements["configoptions"][2].checked ){
            document.getElementById("formalerttext").style.color = "red";
            return false;
        } else {
            enableCheckBoxes();
            return true;
        }
    }
    
    function in_jsarray(val, arr) {
        if(val==null || val=="") return false;
        for(k = 0; k < arr.length; k++ ){
            if(val == arr[k])
                return true;
        }
        return false;
    }
    </script>
    ';
    echo $OUTPUT->heading(get_string('configurationmanagement', 'tool_configmanagement'));

    //Start of form
    echo html_writer::start_tag('form', array('class' => 'configmanagement', 'action' => $ME, 'method' => 'post', 'id' => 'adminsettings', 'name' => 'configmanagement'));

    echo html_writer::start_tag('fieldset');
    print_container('', false, 'clearer');
    echo "\n";

    ///////////////////
    //File to save to//
    ///////////////////
    print_container_start(true, 'form-item');
    echo "\n";

    // CCLE-164 - change filename format
    $dumpfile = "type_configdump_date_time.txt";

    echo html_writer::tag('span', get_string('configsavefile', 'tool_configmanagement'));
    // Print the filename (do not allow it to be modified)
    echo html_writer::start_tag('span');    
    echo $dir;
    echo html_writer::tag('span', 'file', array('id' => "configfilename", 'style' => "font-weight: bold"));
    echo html_writer::end_tag('span');
    print_container('&nbsp;<input type="hidden" name="savefile" size="48" />', false, 'form-file defaultsnext');

    echo html_writer::tag('div', get_string('configsaveinfo', 'tool_configmanagement'), array('class' => 'configsaveinfo'));
    
    // CCLE-164 - filter out values for better diff
    $formcheckboxes = '
        <div id="formalerttext">Select what you want to do:
            <label><input type="radio" name="configoptions" value="diff" onClick="selectDiff();" checked="true">' . get_string('configdiff', 'tool_configmanagement') . '</label>
            <label><input type="radio" name="configoptions" value="full" onClick="selectFullConfig()">' . get_string('configselectfull', 'tool_configmanagement') . '</label>
            <label><input type="radio" name="configoptions" value="min" onClick="selectPartialConfig()">' . get_string('configselectmin', 'tool_configmanagement') . '</label>
        </div>
        <div style="display:block; float:left;">
            <div id="configmsg" style="width:100%; font-size:12px; padding-bottom: 10px;"></div>
            <div style="float:left; width:150px;">
                <label><input type="checkbox" name="config" checked="true" disabled="true">config</label><br/>
                <label><input type="checkbox" name="plugins" checked="true" disabled="true">config_plugins</label>
            </div>
            <div style="float:left; width:150px; ">
                <label><input type="checkbox" name="roles" checked="true" disabled="true">role</label><br/>
                <label><input type="checkbox" name="role_allow_assign" checked="true" disabled="true">role_allow_assign</label><br/>
                <label><input type="checkbox" name="role_allow_override" checked="true" disabled="true">role_allow_override</label><br/>
                <label><input type="checkbox" name="role_assignments" disabled="true">role_assignments</label><br/>
                <label><input type="checkbox" name="role_capabilities" checked="true" disabled="true">role_capabilities</label><br/>
                <label><input type="checkbox" name="role_names" checked="true" disabled="true">role_names</label><br/>
                <label><input type="checkbox" name="role_sortorder" checked="true" disabled="true">role_sortoder</label>
            </div>
            <div style="float:left; width:120px;">
                <label><input type="checkbox" name="blocks" checked="true" disabled="true">blocks</label><br/>
                <label><input type="checkbox" name="modules" checked="true" disabled="true">modules</label>
            </div>
            <div style="float:left; width:120px;">
                <label><input type="checkbox" name="user" disabled="true">user</label>
            </div>
            <div style="float:left; width:120px;">
                <label><input type="checkbox" name="configphp" checked="true" disabled="true">config.php</label>
            <div style="width:100%; font-size:10px; padding-top: 5px; color:red">
                * ' . get_string('configvalsfromconfigphpmsg', 'tool_configmanagement') . '
            </div>
        </div>
        <div style="clear:both; width: 100%; padding-top: 10px;">
            <label><input type="checkbox" name="exc_id_time" checked="true" disabled="true"/> Exclude ID and TIME fields. </label><br/>
            <span style="color:red; font-size:10px;">* time will be written as: <strong>1234567890</strong>, ID fields will be left blank</span>
        </div>
        
        <script>
        // Set diff as default
        selectDiff();
        </script>
    ';
    
    print_container($formcheckboxes, false, 'form-description');
    echo html_writer::empty_tag('br');
    echo html_writer::start_tag('div', array('style' => 'width:100%; float:left; padding-top:10px;'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'save', 'value' => get_string('configsave', 'tool_configmanagement'), 'onclick' => "validateConfigForm(this.form)"));
    echo html_writer::end_tag('div');
    echo html_writer::empty_tag('br');
    echo "\n";
    print_container_end();

    //End of fields
    echo html_writer::end_tag('fieldset');
    
    // display existing config dump files
    echo html_writer::start_tag('fieldset');
    echo html_writer::tag('h3', get_string('config_dump_files_header', 'tool_configmanagement'));
    $diff_files = get_config_dumps($dir); 
    if (empty($diff_files)) {
        echo html_writer::tag('p', get_string('no_config_dump_files', 'tool_configmanagement'), 
                array('class' => 'redfont'));
    } else {
        // create list of 
        $baseurl = $CFG->wwwroot . '/' .$CFG->admin . '/tool/configmanagement/view.php';
        foreach ($diff_files as $index => $diff_file) {
            // give link to view
            $diff_files[$index] = html_writer::link(new moodle_url($baseurl, 
                    array('name' => $diff_file)), $diff_file);
            // then add link to delete (with very bad javascript confirm prompt)
            // TODO: use YUI or put the javascript prompt in separate js file
            $action = new action_link(
                    new moodle_url($baseurl, array('delete' => $diff_file)),
                    new pix_icon('t/delete', get_string("delete"), 'moodle', array('class' => 'iconsmall')),
                    null,
                    array('class' => 'editing_delete', 'title' => get_string("delete"),
                        'onclick' => 'return confirm("'.get_string('confirm_deletion', 'tool_configmanagement').'")')
            );            
            $diff_files[$index] .= $OUTPUT->spacer() .html_writer::tag('span', $OUTPUT->render($action), array('class' => 'commands'));
        }
        
        echo html_writer::alist($diff_files);
    }
    echo html_writer::end_tag('fieldset');
    
    //Form buttons
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();

/**
 * Returns an array of config dump files in given directory.
 * 
 * @param string $path_to_dumps 
 * 
 * @return array    Returns array of file names, if any.
 */
function get_config_dumps($path_to_dumps) {
    $result = array();
    if ($handle = opendir($path_to_dumps)) {
        /* This is the correct way to loop over the directory. */
        while (false !== ($entry = readdir($handle))) {
            if (is_file($path_to_dumps . DIRECTORY_SEPARATOR . $entry)) {
                $result[] = $entry;
            }
        }
        closedir($handle);
    }    
    return $result;
}