<?php
/**
 *  Course Requestor 
 *  This code can use some good refactoring.
 **/
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
$uccdirr = '/tool/uclacoursecreator/uclacoursecreator.class.php';
require_once($CFG->dirroot . '/' . $CFG->admin . $uccdirr);
$thisdir = '/' . $CFG->admin . '/tool/uclacourserequestor/';
require_once($CFG->dirroot . $thisdir . 'lib.php');

global $DB, $ME, $USER;

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
$rucr = 'tool_uclacourserequestor';

// Adding 'Support Admin' capability to course requestor
if (!has_capability('tool/uclacourserequestor:edit', $syscontext)) {
    print_error('accessdenied', 'admin');
}

$selterm = optional_param('term', false, PARAM_ALPHANUM);
$selected_term = $selterm ? $selterm : get_config($rucr, 'selected_term');
if (!$selected_term) {
    $selected_term = $CFG->currentterm;
}

$thisfile = $thisdir . 'index.php';

// used to determine if course is already been requested
$existingcourse = null;  

// used to determine if cross-listed course is already been requested
$existingaliascourse = null; 

// Damn, sorry for all the naming inconsistencies
define('UCLA_CR_SUBMIT', 'submitrequests');

// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', $rucr));
$PAGE->set_pagetype('admin-uclacourserequestor');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacourserequestor');

$subjareas = $DB->get_records('ucla_reg_subjectarea', null, 'subjarea');

$prefieldsdata = get_requestor_view_fields();

$top_forms = array(
    UCLA_REQUESTOR_FETCH => array('srs', 'subjarea'),
    UCLA_REQUESTOR_VIEW => array('view', 'hidden_srs_view')
);

$terms = get_active_terms(true);
if (empty($terms)) {
    $terms[$selected_term] = $selected_term;
}

// This will be passed to each form
$nv_cd = array(
    'subjareas' => $subjareas,
    'selterm' => $selected_term,
    'terms' => $terms,
    'prefields' => $prefieldsdata
);

// We're going to display the forms, but later
$cached_forms = array();

// This is the courses we want to display.
$requests = null;

// This is to know which form type we came from :(
$groupid = null;

// This is the data that is to be displayed in the center form
$uclacrqs = null;

// This is a holder that will maintain the original data 
// (for use when clicking 'checkchanges') 
$pass_uclacrqs = null;

// This is the global requestor previous value
$requestorglobal = '';

// These are the messages that are requestor errors
$errormessages = array();

// This is the field in the postdata that should represent the state of
// data in the current database for the requestors.
$uf = 'unchangeables';

foreach ($top_forms as $gk => $group) {
    foreach ($group as $form) {
        $classname = 'requestor_' . $form . '_form';
        $filename = $CFG->dirroot . $thisdir . $classname . '.php';

        // OK, it appears we need all of them
        require_once($filename);

        $fl = new $classname(null, $nv_cd);

        $cached_forms[$gk][$form] = $fl;
       
        if ($requests === null && $recieved = $fl->get_data()) {
            $requests = $fl->respond($recieved);
            if (empty($requests)) {
                $errormessages[] = 'norequestsfound';
            }

            $groupid = $gk;

            // Place into our holder
            $uclacrqs = new ucla_courserequests();
            foreach ($requests as $setid => $set) {
                // This may get us strangeness
                $uclacrqs->add_set($set);
            }
        }
    }
}

// Special catch for our single term-srs viewer
$getsrs = optional_param('srs', false, PARAM_ALPHANUM);
if ($getsrs && $requests === null) {
    $termsrsform = $cached_forms[UCLA_REQUESTOR_VIEW]['hidden_srs_view'];
    $termsrsobj = new object();
    $termsrsobj->{$termsrsform->groupname} = array(
            'srs' => $getsrs,
            'term' => $selterm
        );

    $requests = $termsrsform->respond($termsrsobj);
    $groupid = UCLA_REQUESTOR_VIEW;
    $uclacrqs = new ucla_courserequests();
    foreach ($requests as $request) {
        $uclacrqs->add_set($request);
    }
}


// None of the forms took input, so maybe the center form?
// In this situation, we are assuming all information is
// logically correct but properly sanitized
$saverequeststates = false;

$coursebuilder = new uclacoursecreator();
$forcebuild = false;

$changes = array();
if ($requests === null) {
    $prevs = data_submitted();
    if (!empty($prevs)) {
        if (!empty($prevs->formcontext)) {
            $groupid = $prevs->formcontext;
        }

        // Save all these requests
        if (!empty($prevs->{UCLA_CR_SUBMIT})) {
            $saverequeststates = true;
        }

        if (!empty($prevs->{'buildcourses'}) && 
                !$coursebuilder->lock_exists()) {
            $forcebuild = true;
        }        
        $requests = array();
        $rkeyset = array();
        // Unchangables
        if (!empty($prevs->{$uf})) {
            $uclacrqs = unserialize(base64_decode($prevs->{$uf}));
            unset($prevs->{$uf});
        } else {
            debugging('no prev data');
        }

        // overwrite with Changables
        foreach ($prevs as $key => $prev) {
            $att = request_parse_input($key, $prev);

            if ($att) {
                list($set, $var, $val) = $att;

                if (!empty($changes[$set])) {
                    $changes[$set][$var] = $val;
                } else {
                    $changes[$set] = array($var => $val);
                }
            } else {
                continue;
            }
        }
       
        // Replace entries without a requestor contact with the value for
        // the global requestor
        if (!empty($prevs->requestorglobal)) {
            $requestorglobal = $prevs->requestorglobal;
        }

        foreach ($changes as $setid => $changeset) {
            if (empty($changeset['requestoremail'])) {
                $changeset['requestoremail'] = $requestorglobal;
            }

            $changes[$setid] = $changeset;
        }
    }
}

// Save the requests before applying changes
if ($uclacrqs !== null) {
    $pass_uclacrqs = clone($uclacrqs);
}

if (!empty($changes)) {
    $changed = $uclacrqs->apply_changes($changes, $groupid);
}

// These are the options that can be applied globally
$globaloptions = array();

// These are the messages that reflect positive changes
$changemessages = array();

$processrequests = isset($uclacrqs) && !$uclacrqs->is_empty();

if ($processrequests) {
    // This is the form data before the save
    $requestswitherrors = $uclacrqs->validate_requests($groupid);
	if ($saverequeststates) {
        $successfuls = $uclacrqs->commit();

        // figure out changes that have occurred
        foreach ($requestswitherrors as $setid => $set) {
            if (isset($successfuls[$setid])) {
                $retcode = $successfuls[$setid];
                $strid = ucla_courserequests::commit_flag_string($retcode);

                $coursedescs = array();
                foreach ($set as $course) {
                    $coursedescs[] = requestor_dept_course($course);
                }

                $coursedescstr = implode(' + ', $coursedescs);
                $retmess = get_string($strid, $rucr, $coursedescstr);

                // We care only for updates
                if ($retcode == ucla_courserequests::savesuccess) {
                    if (!empty($changed[$setid])) {
                        $fieldstr = '';
                        $fieldstrs = array();

                        // Kludge to handle crosslisting changes
                        $thechanges = $changed[$setid];
                        if (!empty($thechanges['crosslists'])) {
                            $cldelta = $thechanges['crosslists'];
                            unset($thechanges['crosslists']);

                            foreach ($cldelta as $action => $cls) {
                                $actionstr = get_string(
                                    'clchange_' . $action, $rucr);
                                foreach ($cls as $cl) {
                                    $fieldstrs[] = $actionstr 
                                    . make_idnumber($cl) . ' '
                                    . requestor_dept_course($cl);
                                }
                            }
                        }

                        foreach ($thechanges as $field => $val) {
                            $fieldstrs[] = get_string($field, $rucr)
                                . get_string('changedto', $rucr)
                                . $val;
                        }

                        $fieldstr = implode(', ', $fieldstrs);

                        $changemessages[$setid] = "$retmess -- $fieldstr";
                    }
                } else {
                    $changemessages[$setid] = $retmess; 
                }
            }
        }

        $requestswitherrors = $uclacrqs->validate_requests($groupid);
        $requeststodisplay = array();
        foreach ($requestswitherrors as $setid => $set) {
            if (!isset($successfuls[$setid])) {
                $requeststodisplay[$setid] = $set;
            }
        }

        if (empty($changed) && empty($requeststodisplay)) {
            $changemessages[] = get_string('nochanges', $rucr);
        }

        // Apply to version that best represents the database
        $pass_uclacrqs = clone($uclacrqs);

        // Reloading the 3rd form
        $nv_cd['prefields'] = get_requestor_view_fields();
        $cached_forms[UCLA_REQUESTOR_VIEW]['view'] 
            = new requestor_view_form(null, $nv_cd);
    }

    // If nobody has determined which requests to display, then disply
    // all of them
    if (!isset($requeststodisplay)) {
        $requeststodisplay = $requestswitherrors;
    }

    // Check to see if we need the requestor field
    foreach ($requestswitherrors as $set) {
        $first = reset($set);

        $oneaction = $first['action'];
        if ($oneaction == UCLA_COURSE_TOBUILD 
                || $oneaction == UCLA_COURSE_FAILED) {
            $requestor = html_writer::tag('label', get_string(
                'requestorglobal', $rucr), array(
                    'for' => 'requestorglobal'
                ));

            $requestor .= html_writer::tag('input', '', array(
                    'type' => 'text',
                    'value' => $requestorglobal,
                    'name' => 'requestorglobal'
                ));

            $globaloptions[] = $requestor;
            break;
        }
    }
    
    // user wants to build courses now
    if ($forcebuild == true) {
        $termlist = array();
        foreach ($requestswitherrors as $course) {
            foreach($course as $value) {
                if ($value['action'] == UCLA_COURSE_TOBUILD) {
                    $termlist[] = $value['term'];
                }
            }
        }

        $termlist = array_unique($termlist);
        events_trigger('build_courses_now', $termlist);
    }
   
    $tabledata = prepare_requests_for_display($requeststodisplay, $groupid);
    $rowclasses = array();
    foreach ($tabledata as $key => $data) {
        if (!empty($data['errclass'])) {
            $rowclasses[$key] = $data['errclass'];

            // We do not need to display this in the table
            unset($tabledata[$key]['errclass']);
        }
    }

    // Get the values as a set
    $errormessages = array_keys(array_flip($rowclasses));

    $possfields = array();
    foreach ($tabledata as $request) {
        // Get the headers to display strings
        foreach ($request as $f => $v) {
            // get_string() should be cached...
            $possfields[$f] = get_string($f, $rucr);
        }
    }

    $requeststable = new html_table();
    $requeststable->id = 'uclacourserequestor_requests';
    $requeststable->head = $possfields;
    $requeststable->data = $tabledata;
    // For errors
    $requeststable->rowclasses = $rowclasses;
} 

$registrar_link = new moodle_url(
    'http://www.registrar.ucla.edu/schedule/');

// Start rendering
echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $rucr));
echo $OUTPUT->heading(get_string('pluginname', $rucr), 2, 'headingblock');

// generate build schedule/notice (if any)
$build_notes = get_config($rucr, 'build_notes');
if ($coursebuilder->lock_exists()) { // if course build is in progress, let user know
    if (!empty($build_notes)) {
        $build_notes .= html_writer::empty_tag('br');        
    }
    $build_notes .= get_string('alreadybuild', $rucr);
} else if (course_build_queued()) {
    if (!empty($build_notes)) {
        $build_notes .= html_writer::empty_tag('br');        
    }
    $build_notes .= get_string('queuebuild', $rucr);
}
if (!empty($build_notes)) {
    $build_notice = html_writer::tag('div', $build_notes, 
            array('id' => 'uclacourserequestor_notice'));
    echo $OUTPUT->box($build_notice, 'noticebox');      
}

foreach ($cached_forms as $gn => $group) {
    echo $OUTPUT->box_start('generalbox');
    echo $OUTPUT->heading(get_string($gn, $rucr));

    foreach ($group as $form) {
         $form->display();    
    }
    
    if ('fetch' == $gn) {
        echo html_writer::link(
            $registrar_link,
            get_string('srslookup', $rucr),
            array('target' => '_blank')
        );             
    }
    echo $OUTPUT->box_end();
}

// display notice to user regarding their requests
if (!empty($changemessages)) {
    $messagestr = implode(html_writer::empty_tag('br'), $changemessages);

    if (!empty($messagestr)) {
        echo $OUTPUT->box($messagestr, 'noticebox');
    }
}

// display error to user regarding their requests
if (!empty($errormessages)) {
    $sm = get_string_manager();
    foreach ($errormessages as $message) {
        if (!empty($message)) {
            $contextspecificm = $message . '-' . $groupid;
            
            if ($sm->string_exists($contextspecificm, $rucr)) {
                $viewstr = $contextspecificm;
            } else {
                $viewstr = $message;
            }

            echo $OUTPUT->box(get_string($viewstr, $rucr), 'errorbox');
        }
    }
}

if (!empty($requeststable->data)) {
    echo html_writer::start_tag('form', array(
        'method' => 'POST',
        'action' => $PAGE->url
    ));

    if (!empty($globaloptions)) {
        $globaloptionstable = new html_table();
        $globaloptionstable->head = array(get_string('optionsforall', $rucr));
        $globaloptionstable->data = array($globaloptions);
        echo html_writer::table($globaloptionstable);
    }

    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => $groupid,
            'name' => 'formcontext'
        ));
    
    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => base64_encode(serialize($pass_uclacrqs)),
            'name' => $uf 
        ));
    
    // only display build now button any "View existing requests" are set  to
    // "to be built"
    $showbutton = false;
    if ('views' == $groupid) {
        foreach ($requestswitherrors as $course) {
            foreach($course as $value) {
                if($value['action'] == "build") {
                    $showbutton = true;
                    break;
                }
            }
        }
    }
    
    // only display built now button for non-prod environments
    $configprod = get_config('theme_uclashared', 'running_environment');    
    if ($configprod != 'prod' && $showbutton) {
        if (!$coursebuilder->lock_exists() && !course_build_queued()) {
            echo html_writer::tag('input', '', array(
                'type' => 'submit',
                'name' => 'buildcourses',	
                'value' => get_string('buildcoursenow', $rucr),
                'class' => 'right',
                'id' => 'buildcourses'
            ));
        } else {
            $button_status = '';
            if ($coursebuilder->lock_exists()) {
                $button_status = get_string('alreadybuild', $rucr);
            } else if (course_build_queued()) {
                $button_status = get_string('queuebuild', $rucr);                
            }
            
            // if course build is happening/queued, disable button
            echo html_writer::tag('input', '', array(
                'type' => 'submit',
                'name' => 'buildcourses',	
                'value' => $button_status,
                'class' => 'right',
                'disabled' => true
            ));
        }
        echo html_writer::empty_tag('br');            
    }
    
    echo html_writer::table($requeststable);

    echo html_writer::tag('input', '', array(
            'type' => 'submit',
            'name' => UCLA_CR_SUBMIT,
            'id' => UCLA_CR_SUBMIT,            
            'value' => get_string('submit' . $groupid, $rucr),
            'class' => 'right'
        ));

    echo html_writer::end_tag('form');
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();

// script functions

/**
 * Looks in the event tables and checks if a request to build courses now has
 * been submitted.
 * 
 * @return boolean 
 */
function course_build_queued() {
    return events_pending_count('build_courses_now');
}
