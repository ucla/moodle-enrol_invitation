<?php
/**
* Strings for the bruincast, libraryreserves, and videofurnace _dbsync scripts.
*
* See CCLE-2314 for details.
**/

$string['pluginname'] = 'UCLA data source synchronization';

/** Strings for bruincast_dbsync **/ 
// Error messages
$string['errbcmsglocation'] = "ERROR: No location set for bruincast data.";
$string['errbcmsgemail'] = "ERROR: No email set for bruincast error notification.";
$string['errbcmsgquiet'] = "ERROR: Cannot access configuration option quiet_mode.";
$string['errbcinsert'] = "ERROR: No records inserted.";

// Notication messages
$string['bcstartnoti'] = "Starting bruincast DB update:";
$string['bcsuccessnoti'] = "records successfully inserted.";

/** Strings for libraryreserves_dbsync **/
// Error messages
$string['errlrmsglocation'] = "ERROR: No location set for library reserves data.";
$string['errinvalidrowlen'] = 'ERROR: Invalid row length in line {$a}.';
$string['errlrfileopen'] = "ERROR: Problem accessing data URL";;
$string['warninvalidfields'] = 'WARNING: Found invalid field(s) {$a->fields} in parsed line {$a->line_num}: {$a->data}';

//Notification messages
$string['lrstartnoti'] = "Starting library reserves DB update:";
$string['lrsuccessnoti'] = '{$a} records successfully inserted.';


// EOF
