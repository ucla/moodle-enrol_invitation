<?php
/**
 * Library of interface functions and constants for public/private
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the public/private specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage publicprivate
 */

/**
 * Cron for public/private to do some sanity checks:
 *  - courses with public/private enabled should have the public/private 
 *    grouping as the default grouping
 *  - group members for public/private grouping should only be in group once 
 *    (@todo)
 *  - Make sure that enablegroupmembersonly is enabled if enablepublicprivate is
 *    enabled
 */
function local_publicprivate_cron() {
    
    
}
