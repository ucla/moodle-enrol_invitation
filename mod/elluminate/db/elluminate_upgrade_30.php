<?php
// ************* START DB UPDATES *****************/
if (!$dbman->table_exists('elluminate_recording_files')) {
   $dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/install.xml', 'elluminate_recording_files');
}

if (!$dbman->table_exists('elluminate_cache')) {
   $dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/install.xml', 'elluminate_cache');
}

if (!$dbman->table_exists('elluminate_option_licenses')) {
   $dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/install.xml', 'elluminate_option_licenses');
}
 
//Recording Table Updates
$recordingTable = new xmldb_table('elluminate_recordings');
$versionMajorField = new xmldb_field('versionmajor');
$versionMajorField -> set_attributes(XMLDB_TYPE_CHAR, '5', null, null, null, null,'recordingsize');
if (!$dbman->field_exists($recordingTable, $versionMajorField)) {
   $dbman->add_field($recordingTable, $versionMajorField);
}

$versionMinorField = new xmldb_field('versionminor');
$versionMinorField -> set_attributes(XMLDB_TYPE_CHAR, '5', null, null, null, null,'versionmajor');
if (!$dbman->field_exists($recordingTable, $versionMinorField)) {
   $dbman->add_field($recordingTable, $versionMinorField);
}

$versionPatchField = new xmldb_field('versionpatch');
$versionPatchField -> set_attributes(XMLDB_TYPE_CHAR, '5', null, null, null, null,'versionminor');
if (!$dbman->field_exists($recordingTable, $versionPatchField)) {
   $dbman->add_field($recordingTable, $versionPatchField);
}

$startdateField = new xmldb_field('startdate');
$startdateField -> set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null,'versionpatch');
if (!$dbman->field_exists($recordingTable, $startdateField)) {
   $dbman->add_field($recordingTable, $startdateField);
}

$enddateField = new xmldb_field('enddate');
$enddateField -> set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null,'startdate');
if (!$dbman->field_exists($recordingTable, $enddateField)) {
   $dbman->add_field($recordingTable, $enddateField);
}

$securesignonField = new xmldb_field('securesignon');

$securesignonField -> set_attributes(XMLDB_TYPE_INTEGER, '1', null, null, null, null,'enddate');
if (!$dbman->field_exists($recordingTable, $securesignonField)) {
   $dbman->add_field($recordingTable, $securesignonField);
}

$roomNameField = new xmldb_field('roomname');
$roomNameField -> set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null,'securesignon');
if (!$dbman->field_exists($recordingTable, $roomNameField)) {
   $dbman->add_field($recordingTable, $roomNameField);
}

//Add Telephony Field to Session Table
$sessionTable = new xmldb_table('elluminate');
$telephonyField = new xmldb_field('telephony');
$telephonyField -> set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1,'gradesession');
if (!$dbman->field_exists($sessionTable, $telephonyField)) {
   $dbman->add_field($sessionTable, $telephonyField);
}

//Fix to preload size column - see MOOD-423
$preloadTable = new xmldb_table('elluminate_preloads');
$sizeField = new xmldb_field('size');
$sizeField->set_attributes(XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null,'description');
$dbman->change_field_precision($preloadTable, $sizeField);

// ************* END DB UPDATES *****************/

/**
 * This portion of the script can be re-run as many times as required (in the case of SAS connectivity issues),
 * so it is broken out into it's own script.  This may fail, but it will NOT prevent the upgrade from succeeding.
 */
$retryMode = false;
include_once('elluminate_upgrade_30_rerun.php');

//Set Default Log Level to Error
set_config('elluminate_log_level', '4');

upgrade_mod_savepoint(true, 2013042901, 'elluminate');

