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

/** This class contains the test cases for the functions in iclicker_service.php. */
class iclicker_gradebook_test extends UnitTestCase {

    var $courseid = 1;

    var $studentid1 = 1;
    var $studentid2 = 2;

    var $cat_name = 'az_category';
    var $item_name = 'az_gradeitem';
    var $grade_score = 91;

    public function setUp() {
        // cleanup the cats and gradeitems and grades if any exist
        $grade_cats = grade_category::fetch_all(array(
            'courseid'=>$this->courseid,
            'fullname'=>$this->cat_name
            )
        );
        if ($grade_cats) {
            foreach ($grade_cats as $cat) {
                $grade_items = grade_item::fetch_all(array(
                    'courseid'=>$this->courseid,
                    'categoryid'=>$cat->id,
                    'itemname'=>$this->item_name
                    )
                );
                if ($grade_items) {
                    foreach ($grade_items as $item) {
                        $grades = grade_grade::fetch_all(array(
                            'itemid'=>$item->id
                            )
                        );
                        if ($grades) {
                            foreach ($grades as $grade) {
                                $grade->delete("cleanup");
                            }
                        }
                        $item->delete("cleanup");
                    }
                }
                $cat->delete("cleanup");
            }
        }
    }

    public function tearDown() {
    }

    function test_assert() {
        $this->assertEqual("AZ", "AZ");
    }

    function test_gradebook() {
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
        $this->assertTrue($grade_category_fetched);
        $this->assertEqual($grade_category->id, $grade_category_fetched->id);
        $this->assertEqual($grade_category->courseid, $grade_category_fetched->courseid);
        $this->assertEqual($grade_category->path, $grade_category_fetched->path);
        $this->assertEqual($grade_category->fullname, $grade_category_fetched->fullname);
        $this->assertEqual($grade_category->parent, $grade_category_fetched->parent);

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
        $this->assertTrue($grade_item_fetched);
        $this->assertEqual($grade_item->id, $grade_item_fetched->id);
        $this->assertEqual($grade_item->courseid, $grade_item_fetched->courseid);
        $this->assertEqual($grade_item->categoryid, $grade_item_fetched->categoryid);
        $this->assertEqual($grade_item->itemname, $grade_item_fetched->itemname);

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
        $this->assertTrue($grade_grade_fetched);
        $this->assertEqual($grade_grade->id, $grade_grade_fetched->id);
        $this->assertEqual($grade_grade->itemid, $grade_grade_fetched->itemid);
        $this->assertEqual($grade_grade->userid, $grade_grade_fetched->userid);
        $this->assertEqual($grade_grade->rawgrade, $grade_grade_fetched->rawgrade);

        // update the grade
        $grade_grade->rawgrade = 50;
        $result = $grade_grade->update($location_str);
        $this->assertTrue($result);
        $grade_grade_fetched = grade_grade::fetch(array(
            'id'=>$grade_grade->id
            )
        );
        $this->assertTrue($grade_grade_fetched);
        $this->assertEqual($grade_grade->id, $grade_grade_fetched->id);
        $this->assertEqual($grade_grade->rawgrade, $grade_grade_fetched->rawgrade);
        $this->assertEqual(50, $grade_grade_fetched->rawgrade);

        // get grades
        $all_grades = grade_grade::fetch_all(array(
            'itemid'=>$grade_item->id
            )
        );
        $this->assertTrue($all_grades);
        $this->assertEqual(1, sizeof($all_grades));

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
        $this->assertTrue($all_grades);
        $this->assertEqual(2, sizeof($all_grades));

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
    }

}
