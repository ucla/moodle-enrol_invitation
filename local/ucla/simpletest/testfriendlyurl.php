<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/externservercourse.php');

class friendly_url_test extends UnitTestCase {
    var $realCFG;
    var $real_SERVER;
    var $real_GET;

    public static $includecoverage = array(
        'local/ucla/lib.php',
        'course/externservercourse.php'
    );

    function setUp() {
        global $CFG, $DB;
        Mock::generate(get_class($DB), 'mockDB');
        $this->realCFG = $CFG;

        $this->real_SERVER = $_SERVER;
        $this->real_GET = $_GET;
    }

    function tearDown() {
        global $CFG, $DB;

        $CFG = $this->realCFG;

        $_SERVER = $this->real_SERVER;
        $_GET = $this->real_GET;
    }

    function testFriendlyURLGenerator() {
        global $CFG;

        if (isset($CFG->forced_plugin_settings['local_ucla']
                ['friendly_urls_enabled'])) {
            $oldcfg = $CFG->forced_plugin_settings['local_ucla']
                ['friendly_urls_enabled'];
        }

        $CFG->forced_plugin_settings['local_ucla']['friendly_urls_enabled']
            = true;

        // Hack for aliases
        $rurihack = str_replace('https://' . $_SERVER['HTTP_HOST'], '', 
            $CFG->wwwroot);

        $_SERVER['REQUEST_URI'] = $rurihack . '/course/view.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $_GET['name'] = 'TEST';

        $courseobj = (object)array(
            'shortname' => 'TEST'
        );

        $ret = extern_server_course($courseobj);

        $this->assertEqual($CFG->wwwroot . '/course/view/TEST', $ret->out());

        $CFG->forced_plugin_settings['local_ucla']['friendly_urls_enabled']
            = false;

        // Arbitrary number > 0
        $_GET['id'] = 3;
        unset($_GET['name']);

        $CFG->forcecoursegettoname = true;

        $ret = extern_server_course($courseobj);

        $this->assertEqual($CFG->wwwroot . '/course/view.php?name=TEST',
            $ret->out());

        if (isset($oldcfg)) {
            $CFG->forced_plugin_settings['local_ucla']['friendly_urls_enabled']
                = $oldcfg;
        }
    }
}
