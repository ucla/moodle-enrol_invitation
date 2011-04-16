<?php

if (!defined('MOODLE_INTERNAL')) {
    die ('Direct access to this script is forbidden.');
}

class MockUnitTest extends UnitTestCase {
    var $realDB;

    function setUp() {
        global $DB;
        Mock::generate(get_class($DB), 'mockDB');
        $this->realDB = $DB;
        $DB = new mockDB();
    }

    function tearDown() {
        global $DB;
        $DB = $this->realDB;
    }
}
