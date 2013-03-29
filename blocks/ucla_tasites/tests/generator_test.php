<?php
/**
 * PHPUnit data generator tests
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
*/

defined('MOODLE_INTERNAL') || die();

// @todo Include local_ucla generator code, because "getDataGenerator" does not
// yet work for local plugins. When local plugins are support, please change
// $generator = new local_ucla_generator();
// to
// $generator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
global $CFG;
require_once($CFG->dirroot . '/local/ucla/tests/generator/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tests/generator/lib.php');

/**
 * PHPUnit data generator testcase
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 */
class block_ucla_tasites_generator_testcase extends advanced_testcase {
    private $tasite_generator = null;
    private $ucla_generator = null;

    /**
     * Try to create a tasite using the basic "create_instance" generator method
     * with no parameters.
     */
    public function test_create_instance_basic() {
        global $DB;

        // try to create tasite with generator creating everything it needs
        $tasite = $this->tasite_generator->create_instance();
        $this->assertFalse(empty($tasite));

        // make sure that someone has ta_admin role in new course
        $coursecontext = context_course::instance($tasite->id);
        $ta_admin_id = $this->tasite_generator->ta_admin_id;
        $users = get_role_users($ta_admin_id, $coursecontext);
        $this->assertFalse(empty($users));

        $is_tasite = block_ucla_tasites::is_tasite($tasite->id);
        $this->assertTrue($is_tasite);
    }

    /**
     * Try to create a tasite using a ta_admin and a UCLA course.
     *
     * @global object $DB
     */
    public function test_create_instance_ta_admin() {
        global $DB;

        // create a random UCLA course
        $param = array(array(), array());
        $class = $this->ucla_generator->create_class($param);
        $this->assertFalse(empty($class));
        $termsrs = array_pop($class);

        $courseid = ucla_map_termsrs_to_courseid($termsrs->term, $termsrs->srs);
        $course = $DB->get_record('course', array('id' => $courseid));

        // create a random user
        $ta = $this->getDataGenerator()->create_user();
        $this->assertFalse(empty($ta));

        // try to create tasite for ta role
        $tasite = $this->tasite_generator->create_instance_with_role($course,
                (array) $ta, 'ta_admin');
        $this->assertFalse(empty($tasite));

        // make sure user has proper role in newly created course (ta_admin)
        $coursecontext = context_course::instance($tasite->id);
        $ta_admin_id = $this->tasite_generator->ta_admin_id;
        $users = get_role_users($ta_admin_id, $coursecontext);
        $user = $users[$ta->id];
        $this->assertEquals($ta_admin_id, $user->roleid);
        $this->assertEquals('Teaching Assistant (admin)', $user->rolename);

        $is_tasite = block_ucla_tasites::is_tasite($tasite->id);
        $this->assertTrue($is_tasite);
    }

    protected function setUp() {
        $this->resetAfterTest(true);
        $this->tasite_generator = $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites');
        $this->tasite_generator->setup();
        $this->ucla_generator = new local_ucla_generator();
    } 
}
