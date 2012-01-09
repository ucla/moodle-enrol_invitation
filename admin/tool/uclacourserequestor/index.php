<?php
/**
 *  Course Requestor 
 **/

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclacourserequestor/';
require_once($CFG->dirroot . $thisdir . 'lib.php');

global $DB, $ME, $USER;

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
$rucr = 'tool_uclacourserequestor';

// Adding 'Support Admin' capability to course requestor
if (!has_capability('tool/uclacourserequestor:view', $syscontext)) {
    print_error('adminsonlybanner');
}

$selterm = optional_param('term', false, PARAM_ALPHANUM);
$selected_term = $selterm ? $selterm : get_config(
    'tool/uclacourserequestor', 'selected_term');

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
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacourserequestor');

$subjareas = $DB->get_records('ucla_reg_subjectarea');

$prefieldsdata = get_requestor_view_fields();

$top_forms = array(
    UCLA_REQUESTOR_FETCH => array('srs', 'subjarea'),
    UCLA_REQUESTOR_VIEW => array('view')
);

$termstr = get_config('tool/uclacourserequestor', 'terms');

if (!empty($termstr)) {
    $terms = explode(',', $termstr);

    foreach ($terms as $k => $t) {
        unset($terms[$k]);
        $tt = trim($t);
        $terms[$tt] = $tt;
    }
}

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

$uclacrqs = null;

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

// None of the forms took input, so maybe the center form?
// In this situation, we are assuming all information is
// logically correct but properly sanitized
$saverequeststates = false;
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
                list($set, $term, $srs, $var, $val) = $att;

                $tr = array('term' => $term, 'srs' => $srs);

                $k = make_idnumber($tr);

                if (!empty($changes[$set][$k])) {
                    $changes[$set][$k][$var] = $val;
                } else {
                    $changes[$set][$k] = array($var => $val);
                }
            } else {
                continue;
            }
        }

        if (isset($uclacrqs)) {
            // TODO return a set of changes that occurred...
            $uclacrqs->apply_changes($changes, $groupid);
        }
    }
}

// At this point, requests are indexed by setid.
if (isset($uclacrqs)) {
    $requestswitherrors = $uclacrqs->validate_requests($groupid);

    if ($saverequeststates) {
        $successfuls = $uclacrqs->commit();

        // Reloading the 3rd form
        $nv_cd['prefields'] = get_requestor_view_fields();
        $cached_forms[UCLA_REQUESTOR_VIEW]['view'] 
            = new requestor_view_form(null, $nv_cd);

        // Take out successfuls from requests with errors
        foreach ($requestswitherrors as $setid => $set) {
            if (isset($successfuls[$setid])) {
                unset($requestswitherrors[$setid]);
            }
        }
    }

    $tabledata = prepare_requests_for_display($requestswitherrors, $groupid);

    $rowclasses = array();
    foreach ($tabledata as $key => $data) {
        if (!empty($data['errclass'])) {
            $rowclasses[$key] = $data['errclass'];

            // We do not need to display this in the table
            unset($tabledata[$key]['errclass']);
        }
    }

    // Get the values as a set
    $messages = array_keys(array_flip($rowclasses));

    $possfields = array();
    foreach ($tabledata as $request) {
        // Get the headers to display strings
        foreach ($request as $f => $v) {
            // get_string() should be cached...
            $possfields[$f] = get_string($f, $rucr);
        }
    }

    $requeststable = new html_table();
    $requeststable->head = $possfields;
    $requeststable->data = $tabledata;

    // For errors
    $requeststable->rowclasses = $rowclasses;
}

$registrar_link = new moodle_url(
    'http://www.registrar.ucla.edu/schedule/');

// TODO display relevant changes

// Start rendering
echo $OUTPUT->header();

echo $OUTPUT->box(
    $OUTPUT->heading(
        get_string('pluginname', $rucr)
    ), 

    'generalbox categorybox box'
);

echo html_writer::link(
    $registrar_link,
    get_string('srslookup', $rucr),
    array('target' => '_blank')
);

foreach ($cached_forms as $gn => $group) {
    echo $OUTPUT->box_start('generalbox');
    echo $OUTPUT->heading(get_string($gn, $rucr));

    foreach ($group as $form) {
         $form->display();
    }

    echo $OUTPUT->box_end();
}

if (!empty($requeststable->data)) {
    echo html_writer::start_tag('form', array(
        'method' => 'POST',
        'action' => $PAGE->url
    ));

    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => $groupid,
            'name' => 'formcontext'
        ));
    
    echo html_writer::tag('input', '', array(
            'type' => 'hidden',
            'value' => base64_encode(serialize($uclacrqs)),
            'name' => $uf 
        ));

    if (!empty($messages)) {
        $sm = get_string_manager();
        foreach ($messages as $message) {
            if (!empty($message)) {
                // TODO think about how to properly do this
                $contextspecificm = $message . '-' . $groupid;

                if ($sm->string_exists($contextspecificm, $rucr)) {
                    $viewstr = $contextspecificm;
                } else {
                    $viewstr = $message;
                }

                echo $OUTPUT->box(get_string($message, $rucr));
            }
        }
    }

    echo html_writer::table($requeststable);

    echo html_writer::tag('input', '', array(
            'type' => 'submit',
            'name' => 'checkrequests',
            'value' => get_string('checkchanges', $rucr),
            'class' => 'right'
        ));

    echo html_writer::tag('input', '', array(
            'type' => 'submit',
            'name' => UCLA_CR_SUBMIT,
            'value' => get_string('submit' . $groupid, $rucr),
            'class' => 'right'
        ));

    echo html_writer::end_tag('form');
} else if ($requests !== null) {
    // We got a response from a form, but no requests to display
    echo $OUTPUT->box(get_string('norequestsfound', $rucr));
}

echo $OUTPUT->footer();

// EoF
