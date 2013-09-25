<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests the format_displayname function.
 *
 * NOTE: Names for this test are notable UCLA alummni:
 * http://en.wikipedia.org/wiki/List_of_University_of_California,_Los_Angeles_people
 *
 * We are testing that names are returned all uppercase, because of:
 * CCLE-4240 - Revert name formatting to all uppercase
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test

class format_displayname_test extends basic_testcase {

    function test_last_name_weirdness() {
        // Handle "von" names.
        $testcases = array('von Dassanowsky, Robert',
                           'VON Dassanowsky, Robert',
                           'VON DASSANOWSKY, ROBERT');
        $expected = array('firstname' => 'ROBERT',
                          'lastname' => 'VON DASSANOWSKY');
        foreach ($testcases as $testcase) {
            $actual = format_displayname($testcase);
            $this->assertEquals($expected, $actual, 'Testcase: ' . $testcase);
        }
    }

    /**
     * Make sure names with middle names are formatted properly.
     */
    function test_middle_name() {
        $testcases = array('Sharpe, William Forsyth',
                           'Sharpe  , WILLIAM Forsyth  ',
                           ' SHARPE , William Forsyth');
        $expected = array('firstname' => 'WILLIAM FORSYTH',
                          'lastname' => 'SHARPE');
        foreach ($testcases as $testcase) {
            $actual = format_displayname($testcase);
            $this->assertEquals($expected, $actual, 'Testcase: ' . $testcase);
        }
    }

    /**
     * Make sure names with JR or suffix are formatted properly.
     */
    function test_suffix() {
        // JR might have period or not.
        $testcases = array('Norton, Ken, Jr.',
                           'NORTON,  Ken,   Jr.  ',
                           ' NORTON ,  Ken,   JR.  ',
                           'NORTON,  KEN,   Jr.  ');
        $expected = array('firstname' => 'KEN',
                          'lastname' => 'NORTON JR.');
        foreach ($testcases as $testcase) {
            $actual = format_displayname($testcase);
            $this->assertEquals($expected, $actual, 'Testcase: ' . $testcase);
        }

        // No periods.
        $testcases = array('Norton, Ken, Jr',
                           'NORTON,  Ken ,   Jr  ',
                           'NORTON,  Ken,   JR  ',
                           'NORTON ,  KEN,   Jr  ');
        $expected = array('firstname' => 'KEN',
                          'lastname' => 'NORTON JR');
        foreach ($testcases as $testcase) {
            $actual = format_displayname($testcase);
            $this->assertEquals($expected, $actual, 'Testcase: ' . $testcase);
        }
    }
}
