<?php
/**
 * Unit tests for methods related to restricting past course access for
 * students.
 *
 * See: CCLE-3786 - Preventing past course access for students
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/local/ucla/eventslib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla/tests/generator/lib.php');

class past_course_access_test extends advanced_testcase {
    private $generator = null;

    /**
     * Make sure that if we set the 'student_access_ends_week' to 3, that only
     * when it is the 3rd week that previous term courses are hidden.
     */
    public function test_third_week_config() {
        global $DB;
        // make sure config setting is set
        set_config('student_access_ends_week', 3, 'local_ucla');

        // make sure no courses are hidden
        $any_hidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($any_hidden);

        // make sure that week 0, 1, 2
        $weeks = array(0, 1, 2);
        foreach ($weeks as $week) {
            hide_past_courses($week);
        }

        // make sure no courses are hidden
        $any_hidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($any_hidden);

        // now try week 3 and make sure that only Summer 2013 courses are hidden
        hide_past_courses(3);
        $summer_courses = ucla_get_courses_by_terms('131');
        foreach ($summer_courses as $courseid => $course) {
            $is_hidden = $DB->record_exists('course',
                    array('id' => $courseid, 'visible' => 0));
            $this->assertTrue($is_hidden);
        }

        $other_terms = array('13S', '13F', '14W');
        foreach ($other_terms as $term) {
            $courses = ucla_get_courses_by_terms($term);
            foreach ($courses as $courseid => $course) {
                $is_hidden = $DB->record_exists('course',
                        array('id' => $courseid, 'visible' => 0));
                $this->assertFalse($is_hidden);
            }
        }

        // now unhide one summer course and try week 4, make sure that unhidden
        // course is not rehidden
        $unhide_course = array_pop($summer_courses);
        list($unhide_course, $courseid) =
                array(end($summer_courses), key($summer_courses));
        $DB->set_field('course', 'visible', 1, array('id' => $courseid));

        hide_past_courses(4);
        $is_hidden = $DB->record_exists('course',
                array('id' => $courseid, 'visible' => 0));
        $this->assertFalse($is_hidden);           
    }

    /**
     * Make sure that no courses are hidden if
     * 'local_ucla'|'student_access_ends_week' is not set.
     */
    public function test_not_set() {
        global $DB;
        // make sure config setting is not set
        set_config('student_access_ends_week', null, 'local_ucla');

        // make sure no courses are hidden
        $any_hidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($any_hidden);

        // call method to auto hide courses for every week possible
        $weeks = range(0, 11);
        foreach ($weeks as $week) {
            hide_past_courses($week);
        }

        // now make sure there are still no hidden courses
        $any_hidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($any_hidden);
    }

    protected function setUp() {
        $this->resetAfterTest(true);
        $this->generator = new local_ucla_generator();
        
        // set current term
        set_config('currentterm', '13F');

        // create some courses for several terms 13S/131/13F/14W
        $terms = array('13S', '131', '13F', '14W');
        foreach ($terms as $term) {
            $this->generator->create_class(array('term' => $term));
            $this->generator->create_class(array('term' => $term));
            $this->generator->create_class(array('term' => $term));
        }

        // make sure no email is sent (Moodle's PHPunit already does this, but
        // make sure)
        // @todo Once we upgrade to Moodle 2.4+, change noemailever to false
        // and use assertDebuggingCalled() to make sure that emails are send
        // and they are properly formatted.
        set_config('noemailever', 1);
   } 
}
