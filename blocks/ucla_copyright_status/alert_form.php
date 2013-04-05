<?php
/**
 * Syllabus alert form definition.
 *
 * @package    block
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class copyright_alert_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('html', html_writer::tag('div',
                get_string('alert_msg', 'block_ucla_copyright_status', $this->_customdata)));

        // need to use multiple submit buttons so that form is sent without the
        // need for js onclick handlers
        $alert_buttons = array();
        $alert_buttons[] = $mform->createElement('submit', 'yesbutton',
                get_string('alert_yes', 'block_ucla_copyright_status'));
        $alert_buttons[] = $mform->createElement('submit', 'nobutton',
                get_string('alert_no', 'block_ucla_copyright_status'));
        $alert_buttons[] = $mform->createElement('submit', 'laterbutton',
                get_string('alert_later', 'block_ucla_copyright_status'));
        $mform->addGroup($alert_buttons, 'alert_buttons', '', array(' '), false);
        $mform->closeHeaderBefore('alert_buttons');
    }
}