<?php
/**
 * PHPUnit invitation_manager tests.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/invitation/invitation_form.php');
require_once($CFG->dirroot . '/enrol/invitation/locallib.php');

/**
 * PHPUnit invitation_manager tests.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 */
class invitation_manager_testcase extends advanced_testcase {
    private $invitation_manager = null;
    private $testcourse = null;
    private $testinvitee = null;
    private $testinviter = null;

    /**
     * Try to enroll a user with an invitation that has daysexpire set. Make
     * sure that the proper timeend is set.
     *
     * @dataProvider daystoexpire_provider
     */
    public function test_enroluser_withdaysexpire($daystoexpire) {
        global $DB;

        // When enrolling a user, the invitation user uses the currently logged
        // in user's id, so we need to set that to the invitee.
        $this->setUser($this->testinvitee);

        $invite = $this->create_invite();
        $invite->daysexpire = $daystoexpire;
        $this->invitation_manager->enroluser($invite);

        // Check user_enrolments table and make sure endtime is $daystoexpire
        // days ahead.
        $timeend = $DB->get_field('user_enrolments', 'timeend',
                array('userid' => $this->testinvitee->id,
                      'enrolid' => $this->invitation_manager->enrol_instance->id));
        // Do not count today as one of the days.
        $expectedexpiration = strtotime(date('Y-m-d'))+86400*($daystoexpire+1)-1;
        $this->assertEquals($expectedexpiration, intval($timeend));
    }

    public function daystoexpire_provider() {
        $ret_val = array();
        foreach (invitation_form::$daysexpire_options as $daysexpire) {
            $ret_val[] = array($daysexpire);
        }
        return $ret_val;
    }

    /**
     * Helper method to create standard invite object. Can be customized later.
     */
    private function create_invite() {
        global $DB;

        $invitation = new stdClass();
        $invitation->token = '517b12e81e212';
        $invitation->email = $this->testinvitee->email;
        $invitation->userid = 0;    // Do not have the invitee's id is yet.
        $invitation->roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $invitation->courseid = $this->testcourse->id;
        $invitation->tokenused = 0;
        $invitation->timesent = time();
        $invitation->timesent = strtotime('+2 weeks');
        $invitation->inviterid = $this->testinviter->id;
        $invitation->subject = 'Test';
        $invitation->notify_inviter = 0;
        $invitation->show_from_email = 1;
        $invitation->daysexpire = 0;

        return $invitation;
    }

    protected function setUp() {
        $this->resetAfterTest(true);
        // Create new course/users.
        $this->testcourse = $this->getDataGenerator()->create_course();
        $this->testinvitee = $this->getDataGenerator()->create_user();
        $this->testinviter = $this->getDataGenerator()->create_user();
        // Make sure that created course has the invitation enrollment plugin.
        $invitation = enrol_get_plugin('invitation');
        $invitation->add_instance($this->testcourse);
        // Create manager that we want to test.
        $this->invitation_manager = new invitation_manager($this->testcourse->id);
    }
}