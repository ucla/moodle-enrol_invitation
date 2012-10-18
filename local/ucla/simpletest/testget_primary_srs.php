<?php

/**
 *  Unit tests for /local/ucla/registrar/registrar_ccle_get_primary_srs.class.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/registrar/registrar_ccle_get_primary_srs.class.php'); // Include the code to test

class primary_srs_test extends UnitTestCase {
    function test_get_primary_srs() {
        ucla_require_registrar();
        
        $term = '12F';
        $srs = '187093203';
        $result = registrar_query::run_registrar_query('ccle_get_primary_srs', array($term, $srs));
        $result = array_shift($result);
        $result = array_pop($result);
        $this->assertEqual($result, '187093200');
        
        $term = '12W';
        $srs = '104340202';
        $result = registrar_query::run_registrar_query('ccle_get_primary_srs', array($term, $srs));
        $result = array_shift($result);
        $result = array_pop($result);
        $this->assertEqual($result, '104340200');
        
        $term = '12S';
        $srs = '262268212';
        $result = registrar_query::run_registrar_query('ccle_get_primary_srs', array($term, $srs));
        $result = array_shift($result);
        $result = array_pop($result);
        $this->assertEqual($result, '262268210');
    }
}

//EOF