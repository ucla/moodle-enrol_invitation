<?php
/*
 * Upgrade code for the UCLA TA site creator block
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * Execute block upgrade from the given older version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_ucla_tasites_upgrade($oldversion) {
    global $DB;

    /**
     * New site type for TA sites. Changing from "instruction" to "tasite".
     */
    if ($oldversion < 2013021900) {
        // get list of all current TA sites by doing the following:
        // 1) Get all sites labeled as "instruction"
        // 2) For each site, call block_ucla_tasites::is_tasite
        // 3) If true, then change type to tasite

        // 1) Get all sites labeled as "instruction"
        $instruction_sites = $DB->get_recordset('ucla_siteindicator',
                array('type' => 'instruction'));

        if ($instruction_sites->valid()) {
            // 2) For each site, call block_ucla_tasites::is_tasite
            foreach ($instruction_sites as $site) {
                if (block_ucla_tasites::is_tasite($site->courseid)) {
                    // 3) If true, then change type to tasite
                    // NOTE: No need to do siteindicator_site->set_type, because
                    // the role grouping hasn't changed
                    $site->type = 'tasite';
                    $DB->update_record('ucla_siteindicator', $site, true);
                }
            }
        }

        // migration complete
        upgrade_block_savepoint(true, 2013021900, 'ucla_tasites');
    }
}
