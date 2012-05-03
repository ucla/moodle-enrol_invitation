<?php 
// START UCLA MODIFICATIONS
// Jeffrey Su - CCLE-164 - Configuration Management (backup and restore settings)

$string['pluginname'] = 'Config management';    
$string['configmanagement'] = 'Config management'; 
$string['configurationmanagement'] = 'Configuration management'; 
$string['configsaveconfiguration'] = 'Save configuration settings';
$string['configsavefile'] = 'Saving a configuration to: ';
$string['configsaveinfo'] = 'The file where a copy of your server configuration settings will be stored to. '.
    'You can perform diffs on these files to find differences between different times and servers. '.
    'Or, you can load these settings in the future or onto another server to restore it to the current state of this server. '.
    'The tables which are stored are Config, Config Plugins, all role-related tables, Block, Modules, and parts of the User table.  '.
    'Only manual acounts in User tables are selected.  Only the role_assignments linked to users in the User tables are saved.  '.
    'The config.php file is read and values are saved, but this is not used in the restore process.';
$string['configsave'] = 'Save configuration to file';
$string['configload'] = 'Load new configuration';
$string['configconfigtable'] = 'Config table';
$string['configconfigpluginstable'] = 'Config plugins table';
$string['configblocktable'] = 'Block table';
$string['configmodulestable'] = 'Modules table';
$string['configallroletables'] = 'All Role tables';
$string['configroletable'] = 'Role table';
$string['configroleallowassigntable'] = 'Role allow assign table';
$string['configroleallowoverridetable'] = 'Role allow override table';
$string['configroleassignmentstable'] = 'Role assignments table';
$string['configrolecapabilitiestable'] = 'Role capabilities table';
$string['configrolenamestable'] = 'Role names table';
$string['configrolesortordertable'] = 'Role sort order table';
$string['configusertable'] = 'Special case logins (user table)';
//$string['configusertable'] = 'Manual Accounts (User table)';
$string['configdefaultfile'] = 'configdump.txt';
$string['configdivider'] = '===';
$string['configfileopenerror'] = 'Failed to open file: {$a}';

$string['configrollbackerrormsg'] = 'Rolling back after failure in';
$string['configusertablewrittenmsg'] = 'User table was writen... Now going to match Role Assignments to users.';
$string['configroleassignmenttablemsg'] = 'Role assignment table was successfully written.';
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

// SSC 1181 - Shulin Jia - added and deleted error messages (see redmine note 4/9/12)
$string['configerror_dirfail'] = 'Cannot create directory';
$string['configerror_cannotoverwrite'] = '$dumpfile is already in use.  Cannot overwrite.';
// END UCLA MODIFICATIONS

$string['config_dump_files_header'] = 'Existing configdumps files';
$string['no_config_dump_files'] = 'No configdumps files created yet';
$string['confirm_deletion'] = 'Are you sure you want to delete this config dump file?';