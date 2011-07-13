<?php

class PublicPrivate_Site
{
    /**
     * Check if public/private is already enabled at the site level.
     *
     * @global object $CFG
     * @return boolean
     */
    public static function is_enabled()
    {
        global $CFG;
        return $CFG->enablepublicprivate == 1;
    }

    /**
     * Checks to make sure that the necessary requisites are enabled
     * ($CFG->enablegroupmembersonly).
     *
     * @global object $CFG
     * @return boolean
     */
    public static function can_enable()
    {
        global $CFG;
        return $CFG->enablegroupmembersonly == 1;
    }

    public static function is_installed()
    {
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
}

?>
