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

    
require_once("../../../config.php");
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/configmanagement/configmanagementlib.php');

    
    
require_login();
global $USER;
global $ME;
    
if (!is_siteadmin($USER->id)) {
    error(get_string('adminsonlybanner'));
}

// Prepare and load Moodle Admin interface
$adminroot = admin_get_root();
admin_externalpage_setup('configmanagement');
admin_externalpage_print_header($adminroot);

//NOTE: If file.php doesn't have security fixes, don't pick course 1
//      In standard Moodle installations, non-admins can access those files 
$dir = $CFG->dataroot.'/1/';

// SSC MODIFICATION #1161 deleted confirmation page prompt

if (isset($_POST['save']) && empty($_POST['load'])) {
    //Save Configuration Settings
    if (!empty($_POST['savefile'])) {
        $dumpfile = $_POST['savefile'];
    }
    else {
        // CCLE-164
        // Name format that we want: type_configdump_date_time.txt
        $dumpfile = optional_param('configoptions')."_configdump_";
        $dumpfile .= date('m.d.y_a.g.i').".txt";
    }

    // Do not write anything if all fields are missing
    if(!optional_param('config', 0, PARAM_RAW) && !optional_param('plugins', 0, PARAM_RAW) && !optional_param('roles', 0, PARAM_RAW)
            && !optional_param('role_allow_assign', 0, PARAM_RAW) && !optional_param('role_allow_override', 0, PARAM_RAW)
            && !optional_param('role_assignments', 0, PARAM_RAW) && !optional_param('role_capabilities', 0, PARAM_RAW)
            && !optional_param('role_names', 0, PARAM_RAW) && !optional_param('role_sortorder', 0, PARAM_RAW)
            && !optional_param('blocks', 0, PARAM_RAW) && !optional_param('mdodules', 0, PARAM_RAW)
            && !optional_param('user', 0, PARAM_RAW) && !optional_param('configphp', 0, PARAM_RAW) ) {
        error(get_string('configerrornofilemsg', 'tool_configmanagement'), $ME);
    }

    $dumpfile = clean_param($dumpfile, PARAM_FILE);
    if (!file_exists($dir)) {        
        if (!mkdir($dir, 0777, true)) {
            error('Cannot create directory', $ME);
        }
    }

    if (strpos($dir, $CFG->dataroot) === false && file_exists($dir.$dumpfile)) {
        //If we're not saving in data diretory, then be sure to to protect code!
        if (substr($dumpfile, -4, 4) === '.php' || substr($dumpfile, -4, 4) === '.html') { 
            error("$dumpfile is already in use.  Cannot overwrite.", $ME);
        }
    }
    $divider = get_string('configdivider', 'tool_configmanagement');
    // Use this time to keep time fields constant for diff
    $difftime = 1234567890;
    $dodiff = (optional_param('configoptions') == 'diff') ? true : false;
    $diffexclude = ($dodiff && optional_param('exc_id_time',0)) ? true : false;

    print_heading(get_string('configsaveconfiguration', 'tool_configmanagement'));

    // CCLE-164 - show filename and location
    echo "<p class=\"mdl-align\">Settings saved to file: ".$dir.$dumpfile."<p/>";

    //Open file for write
    if($fp = fopen($dir.$dumpfile,'w')){
        //Write config
        $configlist = NULL;
        if(optional_param('config')) {
            fwrite($fp, $divider.'Config'.$divider."\n");
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $configlist = get_records('config','','',$sort);
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

            foreach($configlist as $configentry){
                // Exclude ID's if doing a diff
                if($diffexclude) {
                    $configentry->id = "";
                }
                if(!in_array($configentry->name,$excludeconfiglist)){
                    fwrite($fp, json_encode($configentry)."\n");
                }
            }
            echo "<p class=\"mdl-align\">".get_string('configconfigtable', 'tool_configmanagement')." written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configconfigtable', 'tool_configmanagement')." skipped.</p>\n";
        }
    
        // SSC MODIFICATION #1161 changed fwrite for "===<table name>===" to only write after get_records is returned true
        // diff_configdumps now do not include table names if that table was not selected or empty.
        
        //Write plugins
        $configpluginlist = NULL;
        if(optional_param('plugins')) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $configpluginlist = get_records('config_plugins','','',$sort);
        }
        if ($configpluginlist) {
            fwrite($fp, $divider.'Plugins'.$divider."\n");
            //Don't include these fields due to security reasons
            $excludepluginlist = array('openssl');
            foreach($configpluginlist as $configentry){
                // Exclude ID's if doing a diff
                if($diffexclude) {
                    $configentry->id = "";
                }
                if(!in_array($configentry->name,$excludepluginlist)){
                    fwrite($fp, json_encode($configentry)."\n");
                }
            }		  
            echo "<p class=\"mdl-align\">".get_string('configconfigpluginstable', 'tool_configmanagement')." written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configconfigpluginstable', 'tool_configmanagement')." skipped.</p>\n";
        }
    
        //Write Roles, role capabilities, and related tables
        //Roles
        $records = NULL;
        if(optional_param('roles')) {
            // Sort by shortname if doing diff
            $sort = ($dodiff) ? 'shortname' : 'id';
            $records = get_records('role', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Roles'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configroletable', 'tool_configmanagement')." written.</p>";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configroletable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;
    
        //Role Allow Assign
        if(optional_param('role_allow_assign')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = get_records('role_allow_assign', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Role_Allow_Assign'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configroleallowassigntable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configroleallowassigntable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;
    
        //Role Allow Override
        
        if(optional_param('role_allow_override')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = get_records('role_allow_override', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Role_Allow_Override'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configroleallowoverridetable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configroleallowoverridetable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;
    
        //Role Assignments
        $rs = NULL;
        
        if(optional_param('role_assignments')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            // We only want to pick up role_assignments from users with manual authentication
            $query = "SELECT rol.* FROM mdl_role_assignments rol
                    INNER JOIN mdl_user usr ON usr.id = rol.userid
                    WHERE usr.auth = 'manual'
                    ORDER BY $sort";

            //Use get_recordset because role_assignments could be large
            $rs = get_recordset_sql($query);
        }
        if ($rs) {
            fwrite($fp, $divider.'Role_Assignments'.$divider."\n");
            while($record = rs_fetch_next_record($rs)) {
                // Exclude ID's if doing a diff -- also exclude timestart, timemodified
                if($diffexclude) {
                    $record->id = "";
                    $record->timestart = $difftime;
                    $record->timemodified = $difftime;
                }
                fwrite($fp, json_encode($record)."\n");
            }
            echo "<p class=\"mdl-align\">".get_string('configroleassignmentstable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configroleassignmentstable', 'tool_configmanagement')." skipped</p>\n";
        }
        unset($rs); //Clean-up
    
        //Role Capabilities
        if(optional_param('role_capabilities')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = get_records('role_capabilities', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Role_Capabilities'.$divider."\n");
            if($dodiff) {
                // If we're doing a diff, we're going to sort first by
                // roleid, then we're going to sort by capabilities.

                $roleid = 1;
                $allrecs = array();
                // Expect these to be sorted by roleid
                foreach($records as $record){
                    if($record->roleid == $roleid) {
                        $allrecs[$roleid][] = $record;
                    } else {
                        // Increment the ID, so that we start a new array entry
                        $roleid++;
                        // Move back pointer...
                        prev($records);
                    }
                }
                // Now sort $allrecs entries by capabilities
                foreach($allrecs as $recs) {
                    usort($recs, "rolecap_cmp");
                    foreach($recs as $entry) {
                        if($diffexclude) {
                            $entry->id = "";
                            $entry->timemodified = $difftime;
                        }
                        if (isset($entry->modifierid)) {
                            $entry->modifierid = null;
                        }
                        fwrite($fp, json_encode($entry)."\n");
                    }
                }
            } else {
                //Set modifierid to null, it doesn't make sense in other servers
                foreach ($records as $entry) {
                    // Exclude ID's if doing a diff
                    if($diffexclude) {
                        $entry->id = "";
                        $entry->timemodified = $difftime;
                    }
                    if (isset($entry->modifierid)) {
                        $entry->modifierid = null;
                    }
                    fwrite($fp, json_encode($entry)."\n");
                }
            }
            echo "<p class=\"mdl-align\">".get_string('configrolecapabilitiestable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configrolecapabilitiestable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;
    
        //Role Names
        if(optional_param('role_names')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = get_records('role_names', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Role_Names'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configrolenamestable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configrolenamestable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;

        //Role Sort Order
        if(optional_param('role_sortorder')) {
            // Sort by roleid if doing diff
            $sort = ($dodiff) ? 'roleid' : 'id';
            $records = get_records('role_sortorder', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Role_SortOrder'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configrolesortordertable', 'tool_configmanagement')." written</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configrolesortordertable', 'tool_configmanagement')." skipped</p>\n";
        }
        $records = NULL;
        
        //Write a list of installed blocks
        if(optional_param('blocks')) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $records = get_records('block', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Blocks'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configblocktable', 'tool_configmanagement')." written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configblocktable', 'tool_configmanagement')." skipped.</p>\n";
        }
        $records = NULL;

        
        if(optional_param('modules')) {
            // Sort by name if doing diff
            $sort = ($dodiff) ? 'name' : 'id';
            $records = get_records('modules', '', '', $sort);
        }
        if ($records) {
            fwrite($fp, $divider.'Modules'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                }
            }
            write_records_to_file($fp, $records);
            echo "<p class=\"mdl-align\">".get_string('configmodulestable', 'tool_configmanagement')." written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configmodulestable', 'tool_configmanagement')." skipped.</p>\n";
        }
        $records = NULL;

        
        if(optional_param('user')) {
            // Sort by username if doing diff
            $sort = ($dodiff) ? 'username' : 'id';
            $records = get_records('user', 'auth', 'manual', $sort); //Get only manual accounts (normal Moodle logins)
            //$records = get_records('user', '', '', 'id'); //Get all users
        }
        if ($records) {
            fwrite($fp, $divider.'Users'.$divider."\n");
            // Exclude ID's if doing a diff
            if($diffexclude) {
                foreach($records as $record ) {
                    $record->id = "";
                    $record->firstaccess = $difftime;
                    $record->lastaccess = $difftime;
                    $record->lastlogin = $difftime;
                    $record->currentlogin = $difftime;
                }
            }
            write_records_to_file($fp, $records);
            //write_admin_users_to_file($fp, $records);   //Write only admins
            echo "<p class=\"mdl-align\">".get_string('configusertable', 'tool_configmanagement')." written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">".get_string('configusertable', 'tool_configmanagement')."  skipped.</p>\n";
        }

        // write the values from config.php
        if (optional_param('configphp')) {
            fwrite($fp, $divider.'config.PHP'.$divider."\n");
            write_configphp($fp);
            echo "<p class=\"mdl-align\">Config.php written.</p>\n";
        }
        else {
            echo "<p class=\"mdl-align redfont\">Config.php skipped.</p>\n";
        }
        print_continue($ME);
        fclose($fp);

    } else {
        error(get_string('configfileopenerror', 'tool_configmanagement', $dumpfile), $ME);
    }

}
else if (isset($_POST['load']) && empty($_POST['save'])) {
    //Load Configuration Settings
    if (!empty($_POST['loadfile'])) {
        $dumpfile = $_POST['loadfile'];
    }
    else {
        error(get_string('confignofile', 'tool_configmanagement'), $ME);
    }
    $dumpfile = clean_param($dumpfile, PARAM_PATH);

    if(strpos($dumpfile,'diff')!== false) {
        error(get_string('configdifferror', 'tool_configmanagement'));
    }

    global $divider;
    global $dividerlen;
    global $fp;
    
    $divider = get_string('configdivider', 'tool_configmanagement');
    $dividerlen = strlen($divider);

    print_heading(get_string('configloadconfiguration', 'tool_configmanagement'));

    //Open file
    if(file_exists($dir.$dumpfile) && $fp = fopen($dir.$dumpfile,'r')){
        while (!feof($fp)) {
            $line = fgets($fp);
            
            if (stripos($line, $divider.'Config'.$divider) !== false) {
                update_config();
                echo "<p class=\"mdl-align\">".get_string('configconfigtable', 'tool_configmanagement')." completed</p>";
            }

            if (stripos($line, $divider.'Plugins'.$divider) !== false) {
                update_config_plugins();                
                echo "<p class=\"mdl-align\">".get_string('configconfigpluginstable', 'tool_configmanagement')." completed</p>";
            }

            if (stripos($line, $divider.'Roles'.$divider) !== false) {
                update_role_tables();
                echo "<p class=\"mdl-align\">".get_string('configallroletables', 'tool_configmanagement')." completed</p>";
            }
        }
            // going to attempt to drop tables in order to fix some bugs with config

        fclose($fp);
    }
    else {
        error(get_string('configfileopenerror', 'tool_configmanagement', $dumpfile), $ME);
    } 

    print_continue($ME);
}
else {
    //User Interface
    $filedate = date('m.d.y_a.g.i');
    echo '
        <script type="text/javascript" >
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
                document.getElementById("configmsg").innerHTML = "'.get_string('configselectdiffmsg', 'tool_configmanagement').'  <a href=\"javascript:setChecked(1,allTables)\" >Set all</a> or <a href=\"javascript:setChecked(0,allTables)\" >clear all</a>";
                document.adminsettings.elements["savefile"].value = "diff_configdump_'.$filedate.'.txt";
                document.getElementById("configfilename").innerHTML = "diff_configdump_'.$filedate.'.txt";
            }
            function selectFullConfig() {
                disableCheckBoxes();
                setChecked(0,allTables);
                setChecked(1,allConfigTables);
                document.getElementById("configmsg").innerHTML = "'.get_string('configselectfullconfigmsg', 'tool_configmanagement').'";
                document.adminsettings.elements["savefile"].value = "full_configdump_'.$filedate.'.txt";
                document.getElementById("configfilename").innerHTML = "full_configdump_'.$filedate.'.txt";
            }
            function selectPartialConfig() {
                disableCheckBoxes();
                setChecked(0,allTables);
                setChecked(1,partialConfigTables);
                document.getElementById("configmsg").innerHTML = "'.get_string('configslectminconfigmsg', 'tool_configmanagement').'";
                document.adminsettings.elements["savefile"].value = "min_configdump_'.$filedate.'.txt";
                document.getElementById("configfilename").innerHTML = "min_configdump_'.$filedate.'.txt";
            }

            function enableCheckBoxes() {
                len = document.adminsettings.elements.length;
                for( i = 0; i < len; i++) {
                    if ( in_jsarray(document.adminsettings.elements[i].name, allTables) && document.adminsettings.elements[i].disabled ) {
                        document.adminsettings.elements[i].disabled = false;
                    }
                }
            }
            function disableCheckBoxes() {
                len = document.adminsettings.elements.length;
                for( i = 0; i < len; i++) {
                    if ( in_jsarray(document.adminsettings.elements[i].name, allTables) && !document.adminsettings.elements[i].disabled ) {
                        document.adminsettings.elements[i].disabled = true;
                    }
                }
            }
            function setChecked(checkVal, selectTables) {
                len = document.adminsettings.elements.length;
                if( checkVal ) {
                    for( i = 0; i < len; i++) {
                        if (in_jsarray(document.adminsettings.elements[i].name, selectTables)) {
                            document.adminsettings.elements[i].checked = checkVal;
                        }
                    }
                } else {
                    for( i = 0; i < len; i++) {
                        if ( in_jsarray(document.adminsettings.elements[i].name, selectTables) ) {
                            document.adminsettings.elements[i].checked = checkVal;
                        }
                    }
                }
            }
            function validateConfigForm(oform){
                if(!document.adminsettings.elements["configoptions"][0].checked &&
                    !document.adminsettings.elements["configoptions"][1].checked &&
                    !document.adminsettings.elements["configoptions"][2].checked ){
                    document.getElementById("formalerttext").style.color = "red";
                    return false;
                } else {
                    enableCheckBoxes();
                    return true;
                }
                //
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
    print_heading(get_string('configurationmanagement', 'tool_configmanagement'));

    //Start of form
    echo "<form class=\"mdl-align\" action=\"$ME\" method=\"post\" id=\"adminsettings\" name=\"adminsettings\" >\n";
    echo "<fieldset>\n";
    print_container('', false, 'clearer');
    echo "\n";

    ///////////////////
    //File to save to//
    ///////////////////
    print_container_start(true, 'form-item');
    echo "\n";
    
    // CCLE-164 - change filename format
    $dumpfile = "type_configdump_date_time.txt";

    print_container("<label>".get_string('configsavefile', 'tool_configmanagement')."</label>", false, 'form-label');
    print_container('&nbsp;<input type="hidden" name="savefile" size="48" />', false, 'form-file defaultsnext');
    // Print the filename (do not allow it to be modified)
    echo '<div style="float:left; margin-left:10px;">'.$dir.'<span id="configfilename" style="font-weight: bold" >file</span></div>';
    print_container(get_string('configsaveinfo', 'tool_configmanagement'), false, 'form-description');

    // CCLE-164 - filter out values for better diff
    $formcheckboxes = '
        <span id="formalerttext">Select what you want to do:
            <label><input type="radio" name="configoptions" value="diff" onClick="selectDiff();" checked="true" >'.get_string('configdiff', 'tool_configmanagement').'</label>
            <label><input type="radio" name="configoptions" value="full" onClick="selectFullConfig()" >'.get_string('configselectfull', 'tool_configmanagement').'</label>
            <label><input type="radio" name="configoptions" value="min" onClick="selectPartialConfig()" >'.get_string('configselectmin', 'tool_configmanagement').'</label>
        </span>
        <br/>
        <div style="background:#E6E6E6; padding:10px; font-size:12px; display:block; float:left;" >
        <div id="configmsg" style="width:100%; font-size:12px; padding-bottom: 10px;"></div>
        <div style="float:left; width:150px;" >
            <label><input type="checkbox" name="config" checked="true" disabled="true">config</label><br/>
            <label><input type="checkbox" name="plugins" checked="true" disabled="true">config_plugins</label><br/>
        </div>
        <div style="float:left; width:150px; " >
            <label><input type="checkbox" name="roles" checked="true" disabled="true" >role</label><br/>
            <label><input type="checkbox" name="role_allow_assign" checked="true" disabled="true">role_allow_assign</label><br/>
            <label><input type="checkbox" name="role_allow_override" checked="true" disabled="true">role_allow_override</label><br/>
            <label><input type="checkbox" name="role_assignments" disabled="true">role_assignments</label><br/>
            <label><input type="checkbox" name="role_capabilities" checked="true" disabled="true">role_capabilities</label><br/>
            <label><input type="checkbox" name="role_names" checked="true" disabled="true">role_names</label><br/>
            <label><input type="checkbox" name="role_sortorder" checked="true" disabled="true">role_sortoder</label><br/>
        </div>
        <div style="float:left; width:120px;" >
            <label><input type="checkbox" name="blocks" checked="true" disabled="true">blocks</label><br/>
            <label><input type="checkbox" name="modules" checked="true" disabled="true">modules</label><br/>
        </div>
        <div style="float:left; width:120px;" >
            <label><input type="checkbox" name="user" disabled="true">user</label><br/>
        </div>
        <div style="float:left; width:120px;" >
            <label><input type="checkbox" name="configphp" checked="true" disabled="true">config.php</label><br/>
        <div style="width:100%; font-size:10px; padding-top: 5px; color:red" >
            * '.get_string('configvalsfromconfigphpmsg', 'tool_configmanagement').'
        </div>
        </div>
        <div style="clear:both; width: 100%; padding-top: 10px;">
            <label><input type="checkbox" name="exc_id_time" checked="true" disabled="true"/> Exclude ID and TIME fields. </label><br/>
            <span style="color:red; font-size:10px;">* time will be written as: <strong>1234567890</strong>, ID fields will be left blank</span>
        </div>
        </div>

        <script>
            // Set diff as default
            selectDiff();
        </script>
        ';
    print_container($formcheckboxes, false, 'form-description');

    echo "<br />\n";
    echo '<div style="width:100%; float:left; padding-top:10px;" >';
    echo '<input type="submit" name="save" value="'.get_string('configsave', 'tool_configmanagement').'" onclick="validateConfigForm(this.form)" />'."\n";
    echo '</div>';
    echo "<br />\n";

    echo "\n";

    print_container_end();
/* CCLE-164 Hide Configuration Restore
    echo "\n";
    echo "<hr/><br/>";

    /////////////////////
    //File to load from//
    /////////////////////
    print_container_start(true, 'form-item');
    echo "\n";

    print_container("<label>".get_string('configloadfile', 'tool_configmanagement')."</label>", false, 'form-label');
    print_container_start(false, 'form-file defaultsnext');
    echo '&nbsp;<input id="id_reference_value" type="text" '.
         'onchange="validate_mod_resource_mod_form_reference[value](this)" '.
         'onblur="validate_mod_resource_mod_form_reference[value](this)" '.  //value="config_management/configdump.txt" 
         'name="loadfile" size="48" maxlength="255"/>'."\n";
    print_container_end();

    print_container_start(false, 'form-defaultinfo');
    //echo get_string('default').': '.get_string('configdefaultfile', 'tool_configmanagement');
    if (file_exists($dir)) {
        echo '<input id="id_reference_popup" type="button" onclick="return openpopup('.
             "'/files/index.php?id=1&choose=id_reference_value', 'popup', ".
             "'menubar=0,location=0,scrollbars,resizable,width=750,height=500', 0);\" ".
             'title="Choose or upload a file" value="Choose or upload a file ..." '.
             'name="reference[popup]"/>'."\n";
    }
    print_container_end();
    echo "\n";

    print_container(get_string('configloadinfo', 'tool_configmanagement'), false, 'form-description');

    echo "\n";

    echo "<br />\n";
    echo '<input type="submit" name="confirm" value="'.get_string('configload', 'tool_configmanagement').'"/>'."\n";

    print_container_end();
    echo "\n";
*/
    //End of fields
    echo "</fieldset>\n";

    //Form buttons
    echo "</form>\n";


}
print_footer();
