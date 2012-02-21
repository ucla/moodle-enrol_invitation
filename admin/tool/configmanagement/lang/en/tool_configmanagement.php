<?php 
// START UCLA MODIFICATIONS
// Jeffrey Su - CCLE-164 - Configuration Management (backup and restore settings)

$string['pluginname'] = 'Config Management';    
$string['configmanagement'] = 'Config Management'; 
$string['configurationmanagement'] = 'Configuration Management'; 
$string['configsaveconfiguration'] = 'Save Configuration Settings';
$string['configloadconfiguration'] = 'Load Configuration Settings';
$string['configsavefile'] = 'Saving a configuration to: ';
$string['configsaveinfo'] = 'The file where a copy of your server configuration settings will be stored to. '.
    'You can perform diffs on these files to find differences between different times and servers. '.
    'Or, you can load these settings in the future or onto another server to restore it to the current state of this server. '.
    'The tables which are stored are Config, Config Plugins, all role-related tables, Block, Modules, and parts of the User table.  '.
    'Only manual acounts in User tables are selected.  Only the role_assignments linked to users in the User tables are saved.  '.
    'The config.php file is read and values are saved, but this is not used in the restore process.';
$string['configloadfile'] = 'Restoring a configuration';
$string['configloadinfo'] =  'The file to use to restore server configuration settings. '.
    'All tables will be completely overwritten with some values in Config being selectively unchanged. '.
    'Only manual accounts will be added to the existing Users table.  The original admin account wil retain it\'s password.  '.
    'Blocks and Modules are skipped.  '.
    'Make sure you have a backup of the current configuration should you want to restore it later.  ';
$string['configsave'] = 'Save configuration to file';
$string['configload'] = 'Load new configuration';
$string['configconfigtable'] = 'Config table';
$string['configconfigpluginstable'] = 'Config Plugins table';
$string['configblocktable'] = 'Block table';
$string['configmodulestable'] = 'Modules table';
$string['configallroletables'] = 'All Role tables';
$string['configroletable'] = 'Role table';
$string['configroleallowassigntable'] = 'Role Allow Assign table';
$string['configroleallowoverridetable'] = 'Role Allow Override table';
$string['configroleassignmentstable'] = 'Role Assignments table';
$string['configrolecapabilitiestable'] = 'Role Capabilities table';
$string['configrolenamestable'] = 'Role Names table';
$string['configrolesortordertable'] = 'Role Sort Order table';
$string['configusertable'] = 'Special Case Logins (User table)';
//$string['configusertable'] = 'Manual Accounts (User table)';
$string['configdefaultfile'] = 'configdump.txt';
$string['configdivider'] = '===';
$string['configfileopenerror'] = 'Failed to open file: $a';

$string['configrollbackerrormsg'] = 'Rolling back after failure in';
$string['configusertablewrittenmsg'] = 'User table was writen... Now going to match Role Assignments to users.';
$string['configroleassignmenttablemsg'] = 'Role Assignment table was successfully written.';
$string['configerrornofilemsg'] = 'There is nothing to write.  You probably didn\'t specify \'diff\' or \'full config backup\' or \'minimal config backup\'';
$string['configerrorcannotopenfile'] = 'Error.  Could not open config.php';

$string['configselectdiffmsg'] = 'Select which tables to save for diff.';
$string['configselectfullconfigmsg'] = 'All tables will be saved.  The config.php settings will be saved so that you can match password salt for users';
$string['configslectminconfigmsg'] = 'Configuration will be saved, but users, role_assignments, blocks and modules will not be saved.';
$string['configdiff'] = 'diff';
$string['configselectfull'] = 'full config backup';
$string['configselectmin'] = 'minimal config backup';
$string['configvalsfromconfigphpmsg'] = 'values saved from config.php are never restored.';

$string['congierrordeletingrecord'] = 'There was a problem deleting records.';
$string['configerrorinsertingrecord'] = 'There was a problem inserting records.';
$string['confignofile'] = 'No file was specified!';
$string['configdifferror'] = 'Restoring from a diff file is not allowed.  Diff files do not contain all the data needed for a successful restore.';

$string['configprmpt_warning'] = 'Warning';
$string['configprmpt_cancel'] = 'Cancel';
$string['configprmpt_restore'] = 'You are about to restore site-wide settings, permissions, and roles for ';
$string['configprmpt_ffile'] = 'from this file';
$string['configprmpt_prod'] = 'This is a production server.  Excercise extreme caution!';
$string['configprmpt_msg'] = 'This will alter your entire moodle setup and possibly break it if you are not using the correct configuration files.
    Please make sure this is what you want to do.  If you are unsure or are confused by this message, then click <strong>Cancel</strong>!';
// END UCLA MODIFICATIONS
