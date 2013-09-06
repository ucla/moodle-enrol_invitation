<?php
/**
 * Copyright (c) 2009 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package iclicker
 */
/* $Id: test_gradebook.php 148 2012-06-19 00:25:08Z azeckoski@gmail.com $ */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once (dirname(__FILE__).'/../../../config.php');
global $CFG,$USER,$COURSE;
// link in external libraries
require_once ($CFG->libdir.'/gradelib.php');
//require_once ($CFG->dirroot.'/blocks/simplehtml/lib.php');
// grade perm: moodle/grade:manage

/**
 * This class contains the test cases for the functions in iclicker_service.php.
 * http://docs.moodle.org/dev/PHPUnit_integration
 *
resetAfterTest(bool)
true means reset automatically after test, false means keep changes to next test method, default null means detect changes
resetAllData()
reset global state in the middle of a test
setAdminUser()
set current $USER as admin
setGuestUser()
set current $USER as guest
setUser()
set current $USER to a specific user - use getDataGenerator() to create one
getDataGenerator()
returns data generator instance - use if you need to add new courses, users, etc.
preventResetByRollback()
terminates active transactions, useful only when test contains own database transaction handling
createXXXDataSet()
creates in memory structure of database table contents, used in loadDataSet() (eg: createXMLDataSet(), createCsvDataSet(), createFlatXMLDataSet())
loadDataSet()
bulk loading of table contents
getDebuggingMessages()
Return debugging messages from the current test. (Moodle 2.4 and upwards)
resetDebugging()
Clear all previous debugging messages in current test. (Moodle 2.4 and upwards)
assertDebuggingCalled()
Assert that exactly debugging was just called once. (Moodle 2.4 and upwards)
assertDebuggingNotCalled()
Assert no debugging happened. (Moodle 2.4 and upwards)
redirectMessages()
Captures ongoing messages for later testing (Moodle 2.4 and upwards)
 *
 * NOTE: it is not possible to modify database structure such as create new table or drop columns from advanced_testcase.
 *
 * To run the tests, please see:
 * http://docs.moodle.org/dev/PHPUnit
 *
 * To run only this test:
 * vendor/bin/phpunit iclicker_gradebook_test blocks/iclicker/tests/gradebook_test.php
 *
 * @group block_iclicker
 */
class iclicker_gradebook_test extends advanced_testcase {

    var $courseid = 1;

    var $studentid1 = 1;
    var $studentid2 = 2;

    var $cat_name = 'az_category';
    var $item_name = 'az_gradeitem';
    var $grade_score = 91;

    protected function setUp() {
        // setup the test data (users and course)
        $student1 = $this->getDataGenerator()->create_user(array('email'=>'guser1@iclicker.com', 'username'=>'guser1'));
        $this->studentid1 = $student1->id;
        $student2 = $this->getDataGenerator()->create_user(array('email'=>'guser2@iclicker.com', 'username'=>'guser2'));
        $this->studentid2 = $student2->id;
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('name'=>'iclicker course', 'category'=>$category1->id));
        $this->courseid = $course1->id;
        $this->getDataGenerator()->enrol_user($this->studentid1, $this->courseid);
    }

    protected function tearDown() {
    }

    function test_assert() {
        $this->resetAfterTest(true); // reset all changes automatically after this test

        $this->assertEquals("AZ", "AZ");
    }

    function test_gradebook() {
        global $DB;
        $this->resetAfterTest(true); // reset all changes automatically after this test

        $location_str = 'manual';

        // try to get category
        $grade_category = grade_category::fetch(array(
            'courseid'=>$this->courseid,
            'fullname'=>$this->cat_name
            )
        );
        // NOTE: grade category will not be null but it will be empty
        $this->assertFalse($grade_category);

        // create a category
        $params = new stdClass();
        $params->courseid = $this->courseid;
        $params->fullname = $this->cat_name;

        $grade_category = new grade_category($params, false);
        $this->assertTrue(method_exists($grade_category, 'insert'));
        $grade_category->insert($location_str);

        // now we will really get the category that we just made
        $grade_category_fetched = grade_category::fetch(array(
            'courseid'=>$this->courseid,
            'fullname'=>$this->cat_name
            )
        );
        $this->assertTrue($grade_category_fetched !== false);
        $this->assertEquals($grade_category->id, $grade_category_fetched->id);
        $this->assertEquals($grade_category->courseid, $grade_category_fetched->courseid);
        $this->assertEquals($grade_category->path, $grade_category_fetched->path);
        $this->assertEquals($grade_category->fullname, $grade_category_fetched->fullname);
        $this->assertEquals($grade_category->parent, $grade_category_fetched->parent);

        // try to get grade item
        $grade_item = grade_item::fetch(array(
            'courseid'=>$this->courseid,
            'categoryid'=>$grade_category->id,
            'itemname'=>$this->item_name
            )
        );
        // NOTE: grade category will not be null but it will be empty
        $this->assertFalse($grade_item);

        // create a grade item
        $grade_item = new grade_item();
        $this->assertTrue(method_exists($grade_item, 'insert'));

        $grade_item->courseid = $this->courseid;
        $grade_item->categoryid = $grade_category->id;
        $grade_item->idnumber = $this->item_name; // lookup
        $grade_item->itemname = $this->item_name; // display
        $grade_item->itemtype = 'blocks';
        $grade_item->itemmodule = 'iclicker';
        $grade_item->iteminfo = 'blocks/iclicker for unit testing';
        // grademax=100, grademin=0
        $grade_item->grademax = 100.0;

        $grade_item->insert($location_str);

        // now we will really get the new item
        $grade_item_fetched = grade_item::fetch(array(
            'courseid'=>$this->courseid,
            'categoryid'=>$grade_category->id,
            'itemname'=>$this->item_name
            )
        );
        $this->assertTrue($grade_item_fetched !== false);
        $this->assertEquals($grade_item->id, $grade_item_fetched->id);
        $this->assertEquals($grade_item->courseid, $grade_item_fetched->courseid);
        $this->assertEquals($grade_item->categoryid, $grade_item_fetched->categoryid);
        $this->assertEquals($grade_item->itemname, $grade_item_fetched->itemname);

        // get empty grades list
        $all_grades = grade_grade::fetch_all(array(
            'itemid'=>$grade_item->id
            )
        );
        $this->assertFalse($all_grades);

        // add grade
        $grade_grade = new grade_grade();
        $this->assertTrue(method_exists($grade_grade, 'insert'));
        $grade_grade->itemid = $grade_item->id;
        $grade_grade->userid = $this->studentid1;
        $grade_grade->rawgrade = $this->grade_score;
        $grade_grade->insert($location_str);

        // get new grade
        $grade_grade_fetched = grade_grade::fetch(array(
            'itemid'=>$grade_item->id,
            'userid'=>$this->studentid1
            )
        );
        $this->assertTrue($grade_grade_fetched !== false);
        $this->assertEquals($grade_grade->id, $grade_grade_fetched->id);
        $this->assertEquals($grade_grade->itemid, $grade_grade_fetched->itemid);
        $this->assertEquals($grade_grade->userid, $grade_grade_fetched->userid);
        $this->assertEquals($grade_grade->rawgrade, $grade_grade_fetched->rawgrade);

        // update the grade
        $grade_grade->rawgrade = 50;
        $result = $grade_grade->update($location_str);
        $this->assertTrue($result);
        $grade_grade_fetched = grade_grade::fetch(array(
            'id'=>$grade_grade->id
            )
        );
        $this->assertTrue($grade_grade_fetched !== false);
        $this->assertEquals($grade_grade->id, $grade_grade_fetched->id);
        $this->assertEquals($grade_grade->rawgrade, $grade_grade_fetched->rawgrade);
        $this->assertEquals(50, $grade_grade_fetched->rawgrade);

        // get grades
        $all_grades = grade_grade::fetch_all(array(
            'itemid'=>$grade_item->id
            )
        );
        $this->assertTrue($all_grades !== false);
        $this->assertEquals(1, sizeof($all_grades));

        // add more grades
        $grade_grade2 = new grade_grade();
        $grade_grade2->itemid = $grade_item->id;
        $grade_grade2->userid = $this->studentid2;
        $grade_grade2->rawgrade = $this->grade_score;
        $grade_grade2->insert($location_str);

        // get grades
        $all_grades = grade_grade::fetch_all(array(
            'itemid'=>$grade_item->id
            )
        );
        $this->assertTrue($all_grades !== false);
        $this->assertEquals(2, sizeof($all_grades));

        // make sure this can run
        $result = $grade_item->regrade_final_grades();
        $this->assertTrue($result);

        // remove grades
        $this->assertTrue(method_exists($grade_grade, 'delete'));
        $result = $grade_grade->delete($location_str);
        $this->assertTrue($result);
        $result = $grade_grade2->delete($location_str);
        $this->assertTrue($result);

        // check no grades left
        $all_grades = grade_grade::fetch_all(array(
            'itemid'=>$grade_item->id
            )
        );
        $this->assertFalse($all_grades);

        // remove grade item
        $this->assertTrue(method_exists($grade_item, 'delete'));
        $result = $grade_item->delete($location_str);
        $this->assertTrue($result);

        // remove grade category
        $this->assertTrue(method_exists($grade_category, 'delete'));
        $result = $grade_category->delete($location_str);
        $this->assertTrue($result);

        $this->resetAfterTest();
    }

}
