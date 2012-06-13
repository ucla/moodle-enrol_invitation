<?php
/**
 * Command line script to parse, verify, and update Bruincast entries in the Moodle database.
 *
 * $CFG->bruincast_data, $CFG->bruincast_errornotify_email, and $CFG->quiet_mode are defined
 * in the plugin configuration at Site administration->Plugins->Blocks->Bruincast
 *
 * See CCLE-2314 for details.
 *
 */

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_bruincast/cli.php');

// Check to see if config variables are initialized
if (!isset($CFG->bruincast_data)) {
    die("\n".get_string('bcerrmsglocation','tool_ucladatasourcesync')."\n");
}

if (!isset($CFG->bruincast_errornotify_email)) {
    die("\n".get_string('bcerrmsgemail','tool_ucladatasourcesync')."\n");
}

if (!isset($CFG->quiet_mode)) {
    die("\n".get_string('bcerrmsgquiet','tool_ucladatasourcesync')."\n");
}

// Begin database update
update_bruincast_db();
