<?php
/**
 * Script to let users view help information or send feedback. If being called
 * to serve as a modal window, will just output form field & help links.
 * 
 * Else, can be called displayed in a site or course context.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                      
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/ucla/jira.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');

// form to process help request
require_once($CFG->dirroot . '/blocks/ucla_help/help_form.php' );

// set context
$courseid = optional_param('course', 0, PARAM_INTEGER);
if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
}

// set page title/url
$struclahelp = get_string('pluginname', 'block_ucla_help');    
$PAGE->set_title($struclahelp);
$url = new moodle_url('/blocks/ucla_help/index.php');
$PAGE->set_url($url);

// need to change layout to be embedded if used in ajax call
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $is_embedded = true;
} else {
    $is_embedded = false;
}

// setup page context
if (!empty($is_embedded)) {
    // if showing up as a modal window, then don't show normal headers/footers
    $PAGE->set_pagelayout('embedded');    
    // load needed javascript to make form use ajax on submit
    // $PAGE->requires->js('/blocks/ucla_help/module.js');    
    $PAGE->requires->js_init_call('M.block_ucla_help.init', null, true);   
} else {
    // show header
    $PAGE->set_heading($struclahelp);
}

// using core renderer
echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'block_ucla_help'));

echo html_writer::start_tag('fieldset', array('id' => 'block_ucla_help_boxtext'));
echo html_writer::tag('legend', get_string('helpbox_header', 'block_ucla_help'), 
        array('id' => 'block_ucla_help_boxtext_header'));

// show specific text for helpbox (should be set in admin settings)
$boxtext = get_config('block_ucla_help', 'boxtext');
if (empty($boxtext)) {
    // no text set, so use default text
    echo get_string('helpbox_text_default', 'block_ucla_help');
} else {
    echo format_text($boxtext, FORMAT_HTML);
}
echo html_writer::end_tag('fieldset');

echo html_writer::start_tag('fieldset', array('id' => 'block_ucla_help_formbox'));
echo html_writer::tag('legend', get_string('helpform_header', 'block_ucla_help'), 
        array('id' => 'block_ucla_help_formbox_header'));

// create form object for page
$mform = new help_form();

// handle form post
if ($fromform = $mform->get_data()) {

    echo $OUTPUT->box_start('generalbox', 'notice');
    
    // get email address from form submitter (if any)
    if(!empty($fromform->ucla_help_email)) {
        $from_address = $fromform->ucla_help_email;
    } else if (!empty($USER->email)) {
        $from_address = $USER->email;
    } else {
        $from_address = $CFG->noreplyaddress;
    }             
        
    // get message header
    $header = get_string('message_header', 'block_ucla_help', $from_address);
    
    // get message body
    $body = create_help_message($fromform);
    
    // get support contact
    $support_contact = get_support_contact($context);
        
    $send_to = get_config('block_ucla_help', 'send_to');    
    if ('email' == $send_to) {
        // send message via email        
        $mail = get_mailer();
        
        $mail->From = $from_address;          
       
        // always add configured email address
        // @todo: log error if AddAddress returns false  
        $mail->AddAddress(get_config('block_ucla_help', 'email'));      
        
        // add support contact email address (if nill, phpmailer will ignore it)
        // @todo: log error if AddAddress returns false        
        $mail->AddAddress($support_contact);
        
        $mail->Subject = $header;
        $mail->Body = $body;
        
        // just going to use php's built-in email functionality. Moodle provides
        // a function called "email_to_user", but it requires a user in the 
        // database to exist
        $result = $mail->Send();
        
    } elseif ('jira' == $send_to) {
        // send message via JIRA
        
        // if no support contact is assigned, then send to default jira assignee
        if (empty($support_contact)) {
            $support_contact = get_config('block_ucla_help', 'jira_default_assignee');
        }
        
        $params = array(
            'pid' => get_config('block_ucla_help', 'jira_pid'),
            'issuetype' => 1,
            'os_username' => get_config('block_ucla_help', 'jira_user'),
            'os_password' => get_config('block_ucla_help', 'jira_password'),
            'summary' => $header,
            'assignee' => $support_contact,
            'reporter' => $support_contact,
            'description' => $body,
        );        

        // try to create the issue
        // returns null if unable to send request
        // @todo: throw or log error
        $result = do_request(get_config('block_ucla_help', 'jira_endpoint'), $params, 'POST');        
                
    } else {
        // block has been misconfigured, so give error
        // @todo: throw or log error
        $result = false;
    }

    if (!empty($result)) {
        echo $OUTPUT->notification(get_string('success_sending_message', 'block_ucla_help'), 'notifysuccess');
    } else {
        echo $OUTPUT->error_text(get_string('error_sending_message', 'block_ucla_help'));
        // @todo: log error Send fails
    }    
    
    if ($is_embedded) {
        // if embedding help form don't have anything, since I don't know how
        // to hide overlay that script is embedded            
    } else {
        // else give continue link to return to course or front page
        if ($COURSE->id == 1) {
            $url = $CFG->wwwroot;
        } else {
            $url = $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id;
        }
        echo $OUTPUT->single_button($url, get_string('continue'), 'get');
    }    
    
    echo $OUTPUT->box_end();    
    
} else {
    // else display form and header text
    echo get_string('helpform_text', 'block_ucla_help');    
    $mform->display();    
}
echo html_writer::end_tag('fieldset');

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
