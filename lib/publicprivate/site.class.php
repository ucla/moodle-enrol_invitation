<?php

if(!PublicPrivate_Site::is_installed()) {
    PublicPrivate_Site::install();
}

class PublicPrivate_Site {
    /**
     * Check if public/private is already enabled at the site level.
     *
     * @global object $CFG
     * @return boolean
     */
    public static function is_enabled() {
        global $CFG;

        if (isset($CFG->enablepublicprivate)) {
            return ($CFG->enablepublicprivate == 1);
        } 

        return false;
    }

    /**
     * Checks to make sure that the necessary requisites are enabled
     * ($CFG->enablegroupmembersonly).
     *
     * @global object $CFG
     * @return boolean
     */
    public static function can_enable() {
        global $CFG;

        if (isset($CFG->enablegroupmembersonly)) {
            return ($CFG->enablegroupmembersonly == 1);
        }

        return false;
    }

    public static function is_installed() {
        global $DB;

        $a = false;
        $b = false;
        $c = false;

        foreach($DB->get_columns('course') as $col)
            switch($col->name)
            {
                case 'enablepublicprivate': $a = true; break;
                case 'grouppublicprivate': $b = true; break;
                case 'groupingpublicprivate': $c = true; break;
            }

        return $a && $b && $c;
    }

    public static function install() {
        global $DB;

        if(PublicPrivate_Site::is_installed()) {
            throw new PublicPrivate_Site_Exception('Cannot install as public/private is already installed.');
        }

        $dbman = $DB->get_manager();

        $table = new xmldb_table('course');

        $enablepublicprivate = new xmldb_field('enablepublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'hiddensections');
        $grouppublicprivate = new xmldb_field('grouppublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'enablepublicprivate');
        $groupingpublicprivate = new xmldb_field('groupingpublicprivate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grouppublicprivate');

        if(!$dbman->field_exists($table, $enablepublicprivate)) {
            $dbman->add_field($table, $enablepublicprivate);
        }

        if(!$dbman->field_exists($table, $grouppublicprivate)) {
            $dbman->add_field($table, $grouppublicprivate);
        }

        if(!$dbman->field_exists($table, $groupingpublicprivate)) {
            $dbman->add_field($table, $groupingpublicprivate);
        }
    }
}

?>
