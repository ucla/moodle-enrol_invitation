<?php
/**
 * Unit tests for blocks/ucla_help/ucla_help_lib.php.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                      
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php'); // Include the code to test
require($CFG->dirroot . '/blocks/ucla_help/config.php'); // helpblock config settings

/** This class contains the test cases for the classes/functions in ucla_help_lib.php. */
class ucla_help_lib_test extends UnitTestCase {

}