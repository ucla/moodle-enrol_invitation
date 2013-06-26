<?php
/**
 * PHPUnit site invitation tests.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/invitation/invitation_form.php');
require_once($CFG->dirroot . '/enrol/invitation/lib.php');
require_once($CFG->dirroot . '/enrol/invitation/locallib.php');

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

    /**
     * Makes sure that someone who was granted the role of Temorary Participant
     * and has their site invitation user enrollment expired, that they are
     * unenrolled from the course.
     */
    public function test_unenrolexpiredtempparticipant() {
        global $DB;

        // Create Temporary Participant role.
        $roleid = create_role('Temporary Participant', 'tempparticipant', '', 'student');
        $this->assertGreaterThan(0, $roleid);

        // Test setup.
        $invitation = enrol_get_plugin('invitation');
        $this->setUser($this->testinvitee);
        $context = context_course::instance($this->testcourse->id);
        set_config('enabletempparticipant', 1, 'enrol_invitation');

        // Enrol user with an timeend in the past.
        $invitation->enrol_user($this->invitation_manager->enrol_instance, 
                $this->testinvitee->id, $roleid, 0, strtotime('yesterday'));
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Run the enrollment plugin cron and make sure user is unenrolled.
        $invitation->cron();
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertFalse($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertFalse($isenrolled);

        // Now do the opposite, enroll a user with a timeend in the future.
        $invitation->enrol_user($this->invitation_manager->enrol_instance,
                $this->testinvitee->id, $roleid, 0, strtotime('tomorrow'));
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Run the enrollment plugin cron and make sure user is not unenrolled.
        $invitation->cron();
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Enroll someone with a role other than Temporary Participant and
        // make sure they are not unenrolled.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $invitation->enrol_user($this->invitation_manager->enrol_instance,
                $this->testinvitee->id, $studentroleid);
        $hasrole = has_role_in_context('student', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);
        $invitation->cron();
        $hasrole = has_role_in_context('student', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);
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