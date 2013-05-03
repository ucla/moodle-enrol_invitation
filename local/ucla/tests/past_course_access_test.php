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
     * Make sure that hiding a course or TA site also disables guest access.
     */
    public function test_guest_access_disabled() {
        global $DB;

        $enrol_guest_plugin = enrol_get_plugin('guest');

        /* Some edges that we need to test for:
         *  - Site with more than one guest enrollment plugin
         *  - Site with TA site
         *  - Site with no guest enrollment plugin
         *  - Regular, default site
         */

        // Need to create one more test site.
        $this->generator->create_class(array('term' => '131'));

        $summer_courses = ucla_get_courses_by_terms(array('131'));
        $this->assertEquals(count($summer_courses), 4);

        $i = 0;
        $summer_courseids = array();
        foreach ($summer_courses as $urc_record) {
            $record = array_pop($urc_record);   // Crosslists should not matter.
            $course = $DB->get_record('course', array('id' => $record->courseid));
            $summer_courseids[] = $course->id;
            ++$i;
            switch($i) {
                // Site with more than one guest enrollment plugin.
                case 1:
                    // Sites should already have guest enrollment plugin added.
                    $enrol_guest_plugin->add_instance($course);
                    $count = $DB->count_records('enrol',
                            array('enrol' => 'guest', 'courseid' => $course->id));
                    $this->assertEquals($count, 2);
                    break;
                // Site with TA site.
                case 2:
                    $tasite_generator = $this->getDataGenerator()
                            ->get_plugin_generator('block_ucla_tasites');
                    $tasite_generator->setup();
                    $tasite = $tasite_generator->create_instance($course);
                    $summer_courseids[] = $tasite->id;
                    break;
                // Site with no guest enrollment plugin.
                case 3:
                    $guest_plugin = $DB->get_record('enrol', array('enrol' => 'guest',
                                'courseid' => $course->id));
                    $enrol_guest_plugin->delete_instance($guest_plugin);
                    break;
                // Regular, default site.
                default:
                    break;
            }
        }
        
        // Verify that guest enrollment plugins are active.
        $first_entry = true;
        foreach ($summer_courseids as $courseid) {
            $guest_plugins = $DB->get_records('enrol', array('enrol' => 'guest',
                        'courseid' => $courseid));
            if (!empty($guest_plugins)) {
                foreach ($guest_plugins as $guest_plugin) {
                    $this->assertEquals($guest_plugin->status, ENROL_INSTANCE_ENABLED);
                }
            }
        }

        // Now hide summer courses.
        hide_courses('131');

        // Verify that guest enroll (if exists) is disabled.
        foreach ($summer_courseids as $courseid) {
            $guest_plugins = $DB->get_records('enrol', array('enrol' => 'guest',
                        'courseid' => $courseid));
            if (!empty($guest_plugins)) {
                foreach ($guest_plugins as $guest_plugin) {
                    $this->assertEquals($guest_plugin->status, ENROL_INSTANCE_DISABLED);
                }
            }
        }

        // Make sure that other terms were not affected.
        $fall_courses = ucla_get_courses_by_terms(array('13F'));
        foreach ($fall_courses as $courseid => $courseinfo) {
            $guest_plugin = $DB->get_record('enrol', array('enrol' => 'guest',
                        'courseid' => $courseid));
            $this->assertTrue(!empty($guest_plugin));
            $this->assertEquals($guest_plugin->status, ENROL_INSTANCE_ENABLED);
        }
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

    /**
     * Make sure that TA sites are hidden as well as course sites.
     */
    public function test_ta_site_hiding() {
        global $DB;
        // make sure config setting is set
        set_config('student_access_ends_week', 3, 'local_ucla');

        // create TA sites for courses in Summer 2013
        $summer_courses = ucla_get_courses_by_terms(array('131'));
        $this->assertFalse(empty($summer_courses));

        $tasite_generator = $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites');
        $tasite_generator->setup();
        foreach ($summer_courses as $courseid => $courseinfo) {
            $course = $DB->get_record('course', array('id' => $courseid));
            $tasite_generator->create_instance($course);
        }

        // make sure no courses are hidden
        $any_hidden = $DB->record_exists('course', array('visible' => '0'));
        $this->assertFalse($any_hidden);
        
        // now try week 3 and make sure that Summer 2013 TA sites are hidden
        hide_past_courses(3);
        $summer_courses = ucla_get_courses_by_terms('131');
        foreach ($summer_courses as $courseid => $course) {
            $existing_tasites = block_ucla_tasites::get_tasites($courseid);
            foreach ($existing_tasites as $tasite) {
                $is_hidden = $DB->record_exists('course',
                        array('id' => $tasite->id, 'visible' => 0));
                $this->assertTrue($is_hidden);
            }
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
    }

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
