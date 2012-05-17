<?php  

// This file keeps track of upgrades to 
// the lesson module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php


function xmldb_liveclassroom_upgrade($oldversion=0) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = true;
    if($oldversion < 20080011001)
    {
        $table = new xmldb_table('liveclassroom');

        $field = new xmldb_field('isfirst');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1);

        if (!$dbman->field_exists($table, $field))
            $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('fromid');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', false, XMLDB_NOTNULL, null);

        if (!$dbman->field_exists($table, $field))
            $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('copy_content');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field))
            $result = $result && $dbman->add_field($table, $field);
    }
    return $result;
}

?>
