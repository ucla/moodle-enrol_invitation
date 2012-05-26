<?php
/**
 * Keeps track of upgrades to the UCLA library reserves block
 *
 * @package    ucla
 * @subpackage format
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_ucla_library_reserves_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // add indexes
    if ($oldversion < 2012052500) {

        $table = new xmldb_table('ucla_library_reserves');
        $indexes[] = new xmldb_index('term_srs', XMLDB_INDEX_NOTUNIQUE, array('quarter', 'srs'));
        $indexes[] = new xmldb_index('term_course', XMLDB_INDEX_NOTUNIQUE, array('quarter', 'department_code', 'course_number'));
        
        foreach ($indexes as $index) {
            // Conditionally launch add index term_srs
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }        
        }
        
        // ucla savepoint reached
        upgrade_block_savepoint(true, 2012052500, 'ucla_library_reserves');
    }
}
