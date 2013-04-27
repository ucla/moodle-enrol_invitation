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
require_once($CFG->dirroot . '/enrol/invitation/invitation_forms.php');
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
     */
    public function test_enroluser_withdaysexpire() {
        $invite = $this->create_invite();

        $daystotest = invitations_form::$daysexpire_options;
        
        $invite->daysexpire = 3;        
    }

    /**
     * Helper method to create standard invite object. Can be customized later.
     */
    private function create_invite() {
        $invitation = new stdClass();
        $invitation->token = '517b12e81e212';
        $invitation->email = $this->testuser->email;
        $invitation->userid = $this->testinvitee->id;
        $invitation->courseid = $this->testcourse->id;
        $invitation->tokenused = 0;
        $invitation->timesent = time();
        $invitation->timesent = strtotime('+2 weeks');
        $invitation->inviterid = $this->testinviteer->id;
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