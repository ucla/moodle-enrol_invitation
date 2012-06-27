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
    
    // add courseid
    if ($oldversion < 2012060300) {
        $table = new xmldb_table('ucla_library_reserves');

        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'quarter');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $dbman->add_key($table, $key);

        // ucla_library_reserves savepoint reached
        upgrade_block_savepoint(true, 2012060300, 'ucla_library_reserves');        
    }
}
