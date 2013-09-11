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
 * Unit tests for ucla_validator.
 *
 * @package    local_ucla
 * @subpackage phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * UCLA validator test cases.
 * 
 * @package    local_ucla
 * @subpackage phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_validator_test extends basic_testcase {

    /**
     * Test valid inputs.
     */
    public function test_valid_inputs() {
        $result = ucla_validator('term', '11F');
        $this->assertEquals($result, true);
        $result = ucla_validator('term', '11W');
        $this->assertEquals($result, true);
        $result = ucla_validator('term', '11S');
        $this->assertEquals($result, true);
        $result = ucla_validator('term', '111');
        $this->assertEquals($result, true);
        $result = ucla_validator('term', '00S');
        $this->assertEquals($result, true);
        $result = ucla_validator('srs', '111111111');
        $this->assertEquals($result, true);
        $result = ucla_validator('srs', '000000000');
        $this->assertEquals($result, true);
        $result = ucla_validator('uid', '000000000');
        $this->assertEquals($result, true);
        $result = ucla_validator('uid', '123456789');
        $this->assertEquals($result, true);
    }

    /**
     * Test invalid inputs.
     */
    public function test_invalid_inputs() {
        $result = ucla_validator('srs', '00000000');
        $this->assertEquals($result, false);
        $result = ucla_validator('srs', '0000000011');
        $this->assertEquals($result, false);
        $result = ucla_validator('uid', '00000000');
        $this->assertEquals($result, false);
        $result = ucla_validator('uid', '0000000011');
        $this->assertEquals($result, false);
        $result = ucla_validator('term', '1111');
        $this->assertEquals($result, false);
        $result = ucla_validator('term', '110');
        $this->assertEquals($result, false);
        $result = ucla_validator('term', 'FF0');
        $this->assertEquals($result, false);
        $result = ucla_validator('term', '1F0');
        $this->assertEquals($result, false);
        $result = ucla_validator('term', '11FF');
        $this->assertEquals($result, false);
    }

    /**
     * Makes sure that an invalid validator type will throw an exception.
     *
     * @expectedException moodle_exception
     */
    public function test_exceptions() {
        ucla_validator('ter', '1F0');
    }

}
