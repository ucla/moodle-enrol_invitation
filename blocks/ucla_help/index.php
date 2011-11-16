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

echo html_writer::start_tag('div', array('id' => 'block_ucla_help_boxtext'));
echo html_writer::tag('h3', get_string('helpbox_header', 'block_ucla_help'), 
        array('id' => 'block_ucla_help_boxtext_header'));

// show specific text for helpbox (should be set in admin settings)
$boxtext = get_config('block_ucla_help', 'boxtext');
if (empty($boxtext)) {
    // no text set, so use default text
    echo get_string('helpbox_text_default', 'block_ucla_help');
} else {
    echo format_text($boxtext, FORMAT_HTML);
}
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('id' => 'block_ucla_help_formbox'));
echo html_writer::tag('h3', get_string('helpform_header', 'block_ucla_help'), 
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

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

/**
 * Constructs body of email that will be sent when user submits help form.
 * 
 * @param mixed $fromform   Form data submitted by user. Passed by reference. 
 * 
 * @return string           Returns 
 */
function create_help_message(&$fromform)
{
    global $COURSE, $CFG, $DB, $SESSION, $USER;
    
    // If user is not logged in, then a majority of these values will raise PHP 
    // notices, so supress them with @    
    
    // setup description array
    $description['maildisplay'][0] = '0 - '.get_string('emaildisplayno');
    $description['maildisplay'][1] = '1 - '.get_string('emaildisplayyes');
    $description['maildisplay'][2] = '2 - '.get_string('emaildisplaycourse');
    $description['autosubscribe'][0] = '0 - '.get_string('autosubscribeno');
    $description['autosubscribe'][1] = '1 - '.get_string('autosubscribeyes');
    $description['emailstop'][0] = '0 - '.get_string('emailenable');
    $description['emailstop'][1] = '1 - '.get_string('emaildisable');
    $description['htmleditor'][0] = '0 - '.get_string('texteditor');
    $description['htmleditor'][1] = '1 - '.get_string('htmleditor');
    $description['trackforums'][0] = '0 - '.get_string('trackforumsno');
    $description['trackforums'][1] = '1 - '.get_string('trackforumsyes');
    $description['screenreader'][0] = '0 - '.get_string('screenreaderno');
    $description['screenreader'][1] = '1 - '.get_string('screenreaderyes');
    $description['ajax'][0] = '0 - '.get_string('ajaxno');
    $description['ajax'][1] = '1 - '.get_string('ajaxyes');
    
    if (isset($USER->currentcourseaccess[$COURSE->id])) {
        $accesstime = date('r' , $USER->currentcourseaccess[$COURSE->id]);
    } else {
        @$accesstime = date('r' , $USER->lastaccess);
    }

    // Needs stripslashes after obtaining information that has been escaped for security reasons    
    $body = stripslashes($fromform->ucla_help_name) . " wrote: \n\n" . 
            stripslashes($fromform->ucla_help_description) . "\n
    Name: " . stripslashes($fromform->ucla_help_name) . "
    UCLA ID: " . @$USER->idnumber . "
    Email: " . stripslashes($fromform->ucla_help_email) . "
    Server: $_SERVER[SERVER_NAME]
    User_Agent: $_SERVER[HTTP_USER_AGENT]
    Host: $_SERVER[REMOTE_ADDR]
    Referer: $_SERVER[HTTP_REFERER]
    Course Shortname: $COURSE->shortname
    Access Time: $accesstime
    User Profile: $CFG->wwwroot/user/view.php?id=$USER->id
    SESSION_fromdiscussion   = " . @$SESSION->fromdiscussion . "
    USER_id                  = $USER->id
    USER_auth                = " . @$USER->auth . "
    USER_username            = " . @$USER->username . "
    USER_institution         = " . @$USER->institution . "
    USER_firstname           = " . @$USER->firstname . "
    USER_lastname            = " . @$USER->lastname . "
    USER_email               = " . @$USER->email . "
    USER_emailstop           = " . @$description['emailstop'][$USER->emailstop] . "
    USER_lastaccess          = " . @date('r' , $USER->lastaccess) . "
    USER_lastlogin           = " . @date('r' , $USER->lastlogin) . "
    USER_lastip              = " . @$USER->lastip . "
    USER_maildisplay         = " . @$description['maildisplay'][$USER->maildisplay] . "
    USER_htmleditor          = " . @$description['htmleditor'][$USER->htmleditor] . "
    USER_ajax (AJAX and Javascript) = " . @$description['ajax'][$USER->ajax] . "
    USER_autosubscribe       = " . @$description['autosubscribe'][$USER->autosubscribe] . "
    USER_trackforums         = " . @$description['trackforums'][$USER->trackforums] . "
    USER_timemodified        = " . @date('r' , $USER->timemodified) . "
    USER_screenreader        = " . @$description['screenreader'][$USER->screenreader];
    $body .= "\n";
    
    // get logging records
    $log_records = $DB->get_records('log', array('userid' => $USER->id), 'time DESC', '*', 0, 10);        
    if (empty($log_records)) {
        $body .= "No log entries\n";
    } else {
        $body .= print_ascii_table($log_records);
    }
        
    $body .= 'This message was generated by ' . __FILE__;    
    
    return $body;
}

/**
 * Returns the jira user or email address to provide support given a context
 * level. Uses support_contacts_manager to get list of support contacts.
 * 
 * @see support_contacts_manager
 * 
 * @param object $context_id        Current context object
 * 
 * @return string                   Returns support contact matching most 
 *                                  specific context first, else returns null. 
 */
function get_support_contact($cur_context)
{
    $ret_val = null;

    // get support contacts
    $manager = get_support_contacts_manager();        
    $support_contacts = $manager->get_support_contacts();    

   // get list of contexts to check
    $context_ids = array_merge((array) $cur_context->id, 
            (array) get_parent_contexts($cur_context));     
    
    foreach ((array) $context_ids as $context_id) {
        $context = get_context_instance_by_id($context_id);
        $context_name = print_context_name($context, false, true);
        
        // see if context matches something in support_contacts list
        if (!empty($support_contacts[$context_name])) {
            $ret_val = $support_contacts[$context_name];
            break;
        }        
    }       
    
    return $ret_val;
}

/**
 * Copied from CCLE 1.9 feedback code.
 * @param type $stuff
 * @return string 
 */
function print_ascii_table($stuff)
{
    $formatted_table = array();
    $formatted_string = "";

    // Parse through once to get proper formatting length
    $line_count = 0;
    foreach ($stuff as $line) {
        $line_count++;
        foreach (get_object_vars($line) as $key => $data) {
            unset($test_string);

            // Make the testing string
            $test_string = ' ';
            if ($key == 'time') {
                $test_string .= date('r', $data);
            } else {
                $test_string .= $data;
            }
            $test_string .= ' ';

            // Get length
            $string_length = strlen($test_string);

            // Get max length
            if (!isset($formatted_table[$key])) {
                $formatted_table[$key] = $string_length;
            } else if ($formatted_table[$key] < $string_length) {
                $formatted_table[$key] = $string_length;
            }

            if ($formatted_table[$key] < strlen(" " . $key . " ")) {
                $formatted_table[$key] = strlen(" " . $key . " ");
            }
        }
    }

    $formatted_table['KINDEX'] = 0;
    while ($line_count >= 1) {
        $line_count = $line_count / 10;
        $formatted_table['KINDEX']++;
    }

    $line_count = 0;
    $formatted_string .= "\n";

    // Print field names
    $formatted_line = "| ";
    while (strlen($formatted_line) - 2 < $formatted_table['KINDEX']) {
        $formatted_line .= "-";
    }
    $formatted_line .= " |";
    $formatted_set = strlen($formatted_line);

    $sampleline = $stuff[array_rand($stuff)];
    foreach (get_object_vars($sampleline) as $key => $data) {
        $formatted_line .= " ";
        $formatted_line .= $key;

        while (strlen($formatted_line) - $formatted_set < $formatted_table[$key]) {
            $formatted_line .= " ";
        }
        $formatted_line .= "|";
        $formatted_set = strlen($formatted_line);
    }
    $formatted_string .= $formatted_line . "\n";

    for ($i = 0; $i < $formatted_set; $i++) {
        $formatted_string .= "-";
    }
    $formatted_string .= "\n";

    foreach ($stuff as $line) {
        $line_count++;
        $formatted_line = "| " . $line_count;
        while (strlen($formatted_line) - 3 < $formatted_table['KINDEX']) {
            $formatted_line .= " ";
        }
        $formatted_line .= "|";
        $formatted_set = strlen($formatted_line);

        foreach (get_object_vars($line) as $key => $data) {
            $formatted_line .= " ";
            if ($key == 'time') {
                $formatted_line .= date('r', $data);
            } else {
                $formatted_line .= $data;
            }

            while (strlen($formatted_line) - $formatted_set < $formatted_table[$key]) {
                $formatted_line .= " ";
            }
            $formatted_line .= "|";
            $formatted_set = strlen($formatted_line);
        }
        $formatted_string .= $formatted_line . "\n";
    }
    return $formatted_string;
}