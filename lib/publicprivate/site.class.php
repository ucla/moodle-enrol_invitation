<?php

/**
 * @todo enable()
 * @todo disable()
 * @todo is_installed()
 * @todo *documentation*
 */

class PublicPrivate_Site
{
    /**
     *
     * @global object $CFG
     * @return boolean
     */
    public static function is_enabled()
    {
        global $CFG;
        return $CFG->enablepublicprivate == 1;
    }
}

?>
