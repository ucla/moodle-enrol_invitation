<?php

/**
 *  A library of functions useful for course requestor and probably
 *  course creator.
 *  @author Yangmun Choi
 **/

defined('MOODLE_INTERNAL') || die();

// These are the requestor flags used.
// This means to build
define('UCLA_COURSE_TOBUILD', 'build');
// This means that stuff is done
define('UCLA_COURSE_BUILT', 'built');
// This means that hopefully things are working
define('UCLA_COURSE_LOCKED', 'running');
// This means request failed tobe built
define('UCLA_COURSE_FAILED', 'failed');

// This means to skip validation for this course
define('UCLA_REQUEST_IGNORE', 'ignore');

// Meta Error
define('UCLA_REQUESTOR_ERROR', 'error');
define('UCLA_REQUESTOR_WARNING', 'warning');

// Errors
define('UCLA_REQUESTOR_EXIST', 'alreadysubmitted');
define('UCLA_REQUESTOR_BADCL', 'illegalcrosslist');
define('UCLA_REQUESTOR_GHOST', 'ghostcoursecreated');
define('UCLA_REQUESTOR_BADHOST', 'inconsistenthost');
define('UCLA_REQUESTOR_CANCELLED', 'cancelledcourse');
define('UCLA_REQUESTOR_NOCOURSE', 'nosrsfound');

define('UCLA_REQUESTOR_FETCH', 'fetch');
define('UCLA_REQUESTOR_VIEW', 'views');

$uclalib = $CFG->dirroot . '/local/ucla/lib.php';
require_once($uclalib);

ucla_require_registrar();

require_once($CFG->dirroot . '/' . $CFG->admin 
    . '/tool/uclacourserequestor/ucla_courserequests.class.php');

/**
 *  Fetches a single course from the request table.
 **/
function get_course_request($term, $srs) {
    if (empty($term) || empty($srs)) {
        return false;
    }

    $r = get_course_requests(
        array(array('term' => $term, 'srs' => $srs))
    );

    if (!empty($r)) {
        return reset($r);
    }

    return false;
}

/**
 *  Fetches requests either by term or by term-srs.
 *  You can currently only fetch by host requests.
 *  @param $inputs
 *      This can either be:
 *          Array of terms 
 *              OR
 *          Array of Array('term' => term, 'srs' => srs)
 **/
function get_course_requests($inputs=array()) {
    global $DB;

    if (empty($inputs)) {
        return array();
    }

    $where = '';
    $params = array();

    // Build parameters and SQL
    if (!empty($inputs)) {
        $first_one = reset($inputs);
        if (!is_array($first_one)) {
            // This means a set of terms
            $sql = '';
            list($sql, $params)  = $DB->get_in_or_equal($inputs);
            $where = '`term` ' . $sql;

            $clwhere = $where;
            $clparams = $params;
        } else {
            // This means a set of term-srs
            $wheres = array();
            $clwheres = array();

            foreach ($inputs as $termsrs) {
                $term = $termsrs['term'];
                $srs = $termsrs['srs'];
                
                $params[] = $term;
                $params[] = $srs;

                $wheres[] = '`term` = ? AND `srs` = ?';
            }

            $where = implode(' OR ', $wheres);
        }
    }

    // Fetch none of them (on the safe side)
    if (empty($params)) {
        debugging('get_course_request() could not figure out params!');
        return array();
    }

    $results = $DB->get_records_select('ucla_request_classes', $where,
        $params);

    $returns = array();
    if ($results) {
        foreach ($results as $k => $r) {
            $returns[make_idnumber($r)] = prep_request_from_db($r);
        }
    }

    return $returns;
}

/**
 *  Fetches a set of requests from the db.
 **/
function get_set($setid) {
    global $DB;

    $set = $DB->get_records('ucla_request_classes', 
        array('setid' => $setid));

    $iset = array();

    foreach ($set as $request) {
        $k = make_idnumber($request);

        $iset[$k] = prep_request_from_db($request);
    }
    
    return $iset;
}

/**
 *  Wrapper function for set_field_select.
 **/
function associate_set_to_course($setid, $courseid) {
    global $DB;

    return $DB->set_field('ucla_request_classes', 'courseid', 
        $courseid, array('setid' => $setid));
}

/** 
 *  Convenience function to apply a change to a set in memory.
 **/
function apply_to_set($set, $field, $val) {
    if (empty($set)) {
        return false;
    }

    foreach ($set as $k => $rq) {
        if (is_object($set[$k])) {
            $set[$k]->$field = $val;
        } else {
            $set[$k][$field] = $val;
        }
    }

    return $set;
}

/**
 *  Inflates up the instructors.
 *  @param  $r  Array|Object
 *  @return Array(
 *      ... ,
 *      'instructor' => Array(),
 *      ... 
 *  )
 *
 **/
function prep_request_from_db($r) {
    if (is_object($r)) {
        $r = get_object_vars($r);
    }

    $f = 'instructor';
    if (is_string($r[$f])) {
        $v = $r[$f];
        $r[$f] = array($v => $v);
    }

    return $r;
}

/**
 *  Fills in instructor information from the Registrar, then preps each entry
 *      to be inserted into the DB.
 *  
 *  @param  $courses    None of these courses should be in the request tables,
 *              but they should be direct from Registrar.
 *  @return Array()     Representing ucla_request_classes row.
 **/
function registrar_to_requests($courses) {
    $infos = array();

    $returninfos = array();
    $defaults = get_requestor_defaults();

    foreach ($courses as $ak => $course) {
        if (is_object($course)) {
            $course = get_object_vars($course);
        }

        if (empty($course['term']) || empty($course['srs'])) {
            continue;
        }

        $term = $course['term'];
        $srs = $course['srs'];

        $k = make_idnumber($course);
        
        $instrs = get_instructor_info_from_registrar($term, $srs);
        $returninfos[] = prep_registrar_entry($course, $instrs, $defaults);
    }

    return $returninfos;
}

/**
 *  Convenience function returns either the request info from the local DB
 *  or automatically queries the Registrar.
 **/
function get_request_info($term, $srs) {
    $exists = get_course_request($term, $srs);

    if ($exists) {
        return $exists;
    }

    // This is expensive
    $reted = get_course_info_from_registrar($term, $srs);

    $ret = false;
    if ($reted) {
        $cos = array($reted);
        $ret = reset(registrar_to_requests($cos));
    }

    return $ret;
}

/**
 *  Wastes clock cycles and returns the crosslist checking mechanism.
 *  Takes about 0.25 seconds.
 **/
function get_crosslisted_courses($term, $srs) {
    global $CFG;
    
    $regurl = 'http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/'
        . 'getConSched?user=' . $CFG->registrar_dbuser . '&pass='
        . $CFG->registrar_dbpass . '&term=' . $term . '&SRS=' . $srs;
 
    try {
        $r = new SimpleXMLElement($regurl, 0, true);
    } catch (Exception $e) {
        throw new Exception('Could not connect to Registrar Crosslisting '
            . 'Webservice');
    }

    return $r;
}

/**
 *  Customizable ignoring stuff.
 **/
function requestor_ignore_entry($data) {
    if (is_array($data)) {
        $data = (object) $data;
    }

    if (!isset($data->subj_area)) {
        debugging('cannot check to ignore entry: ' 
            . print_r($data, true));
        return false;
    }

    $subj = $data->subj_area;
    $num = $data->coursenum;

    if ($num > 495) {
        return true;
    }

    if ($subj == 'PHYSICS' && $num > 295) {
        return true;
    }

    if ($subj == 'ASTR' && in_array($num, array('277B', '296', '375'))) {
        return true;
    }    
    
    return false;
}

/**
 *  Strips and simplifies data from the registrar to be ready for placement 
 *  in the request classes tables.
 **/
function prep_registrar_entry($regdata, $instinfo, $defaults=array()) {
    $term = $regdata['term'];
    $srs = $regdata['srs'];

    // Generate a fresh php array.
    $req = array();
    $req['term'] = $term;
    $req['srs'] = $srs;

    $req['department'] = $regdata['subj_area'];
    $req['course'] = get_course_from_reginfo($regdata);

    if (!isset($regdata['instructor'])) {
        $instarr = array();

        // This is some redundant code...
        foreach ($instinfo as $inst) {
            if (is_object($inst)) {
                $inst = get_object_vars($inst);
            }

            $fn = $inst['first_name_person'];
            $ln = $inst['last_name_person'];

            if ($fn && $ln) {
                $u = new stdClass();

                $u->firstname = $fn;
                $u->lastname = $ln;

                $fullname = fullname($u);

                $instarr[$fullname] = $fullname;
            }
        }
    }

    $req['enrolstat'] = $regdata['enrolstat'];

    $req['instructor'] = $instarr;

    if (empty($defaults)) {
        $defaults = get_requestor_defaults();
    }

    foreach ($defaults as $field => $defval) {
        if (!isset($req[$field])) {
            $req[$field] = $defval;
        }
    }

    return $req;
}

/**
 *  Gets default settings for requests from the database.
 **/
function get_requestor_defaults() {
    // Determine some defaults
    $defaults = array();
    //$defaults['hidden'] = get_config('moodlecourse')->visible;

    $configs = get_config('tool_uclacourserequestor');

    $editables = ucla_courserequests::get_editables();
    $translate_tf = array('true' => 1, 'false' => 0);

    // These are options that are soft, defaults changed through UI
    foreach ($editables as $ed) {
        $varname = $ed . '_default';
        $d = false;

        if (isset($configs->$varname)) {
            $d = $configs->$varname;
        }

        $defaults[$ed] = $d;
    }

    $defaults['action'] = UCLA_COURSE_TOBUILD;
    $defaults['timerequested'] = time();

    $defaults['id'] = null;
    $defaults['courseid'] = null;

    return $defaults;
}

/**
 *  Returns the set of related courses to the host course.
 *  @param $host Array(
 *          'term' => term
 *          'srs' => srs
 *          (optional) 'setid' => setid
 *      )
 *  @return Array(
 *          request_key => Array(request) 
 *          ...
 *      ) at least one of these will have the property of 'hostcourse' = 1
 **/
function get_crosslist_set_for_host($host) {
    if (is_object($host)) {
        $host = get_object_vars($host);
    }

    if (empty($host['srs']) || empty($host['term'])) {
        return false;
    }

    if (isset($host['setid'])) {
        return get_set($host['setid']);
    }

    // Non-existing set of courses
    $h = 'hostcourse';
    $hostkey = make_idnumber($host);
    $set = array($hostkey => $host);

    // These are entries from the registrar, so they need to have their
    // crosslists checked
    $clists = get_crosslisted_courses($host['term'], $host['srs']);

    if (!empty($clists->getConSchedData)) {
        $exts = array();

        foreach ($clists->getConSchedData as $termsrs) {
            $ext = extract_term_srs_xml($termsrs);

            if (!$ext) {
                continue;
            }

            $exts[] = $ext;
        }

        $new = get_course_requests($exts);

        foreach ($exts as $ext) {
            $clkey = make_idnumber($ext);
            
            if (!empty($new[$clkey])) {
                $setter = $new[$clkey];
            } else {
                // This will get us just the single course we are looking for
                $setter = get_request_info($ext['term'], $ext['srs']);
            }

            $set[$clkey] = $setter;
        }
    }

    $set = set_host_calculate($hostkey, $set);
    return $set;
}

/**
 *  Calculates and sets the proper host numbers.
 **/
function set_host_calculate($orighost, $set) {
    $h = 'hostcourse';
    $hostexists = false;

    foreach ($set as $key => $request) {
        if (!isset($request[$h])) {
            $set[$key][$h] = 0;
            continue;
        }

        if (!$hostexists && $request[$h]) {
            $hostexists = $key;
            break;
        } 
    }

    if (!$hostexists) {
        $set[$orighost][$h] = 1;
    } else if ($hostexists != $hostkey) {
        $set[$orighost][$h] = 2;
    }

    return $set;
}

/**
 *  Returns the greatest host of the course.
 **/
function set_find_host($set) {
    $hk = false;
    $h = 'hostcourse';

    foreach ($set as $k => $c) {
        if (!isset($c[$h])) {
            
            debugging('no hostcourse: ' . print_r($set, true));
            return false;
        }

        if (!$hk || $c[$h] > $set[$hk][$h]) {
            $hk = $k;
        }
    }

    return $hk;
}

/**
 *  Convenience function to write the visual character summary for a 
 *  particular course request.
 *  @param Object with fields coursenum sectnum
 **/
function get_course_from_reginfo($regdata) {
    if (is_object($regdata)) {
        $regdata = get_object_vars($regdata);
    }

    return $regdata['coursenum'] . '-' . $regdata['sectnum'];
}

/**
 *  Convenience function to extract the term and SRS from the returned
 *  XML-parsed-node-object.
 **/
function extract_term_srs_xml($xml) {
    $t = array('term', 'srs');
    $r = array();

    foreach ($t as $k) {
        if (!isset($xml->{$k})) {
            return false;
        }

        if (!empty($xml->{$k}->{0})) {
            $r[$k] = sprintf('%s', $xml->{$k}->{0});
        }
    }

    return $r;
}

/**
 *  Flattens the requests.
 **/
function prepare_requests_for_display($requestinfos, $context) {
    // Here, we finally turn our setid-indexed flat array into
    // the crosslist heirarchy
    $c = 'crosslists';

    $displayrows = array();
    $errorrows = array();

    foreach ($requestinfos as $setid => $set) {
        $displaykey = set_find_host($set);

        $displayrow = $set[$displaykey];

        // Add crosslists
        $displayrow[$c] = array();

        foreach ($set as $key => $request) {
            if ($key == $displaykey) {
                continue;
            }

            $displayrow[$c][$key] = $request;
        }

        // Deal with fields that are displayed but not in the request
        // tables themselves
        if ($context == UCLA_REQUESTOR_FETCH) {
            $k = 'build';
            // Hack, perhaps find a better place for this...
            if (isset($displayrow[UCLA_REQUESTOR_WARNING]
                    [UCLA_REQUESTOR_CANCELLED])) {
                $default = false;
            } else {
                $default = true;
            }
        } else {
            $k = 'delete';
            $default = false;
        }

        if (!isset($displayrow[$k])) {
            $displayrow[$k] = $default;
        }

        // Make fields pretty
        $prepped = prep_request_entry($displayrow);

        if (!empty($prepped)) {
            $displayrows[$displaykey] = $prepped;
        }
    }

    return remove_empty_fields($displayrows);
}

/**
 *  Removes fields with no data for all rows.
 **/
function remove_empty_fields($table) {
    $removes = array();
    foreach ($table as $k => $r) {
        foreach ($r as $f => $d) {
            if (isset($removes[$f]) && $removes[$f] === false) {
                continue;
            }

            if (empty($d)) {
                $removes[$f] = true;
            } else {
                $removes[$f] = false;
            }
        }
    }

    foreach ($table as $k => $r) {
        $newone = array();
        foreach ($removes as $f => $t) {
            if ($t) {
                unset($r[$f]);
            }
        }

        $table[$k] = $r;
    }

    return $table;
}

/**
 *  Quick function that doesn't really need to be a function,
 *  but it parses the fields from a previously displayed 
 *  requestor contents tables.
 **/
function request_parse_input($key, $value) {
    $vals = array();
    preg_match('/^([new_0-9]*)-(.*)$/', $key, $vals);

    $x = 1;
    if ($vals && count($vals) >= 2) {
        $set = $vals[$x++];
        $var = $vals[$x++];

        return array($set, $var, $value);
    }

    return false;
}

/** 
 *  Checks if a request's changes should be ignored.
 *  @return boolean
 **/
function request_ignored($request) {
    $b = 'build';
    $d = 'delete';

    if (isset($request[$b])) {
        return ($request[$b] == 0);
    } else if (isset($request[$d])) {
        return ($request[$d] != 0);
    }

    return false;
}

/**
 *  This takes all the data for a request, and prepares it to be displayed
 *  as text to a user, including all errors that need to be included.
 *  This SHOULD NEVER change the values represented by $requestinfo!!
 **/
function prep_request_entry($requestinfo) {
    global $DB;

    $errs = UCLA_REQUESTOR_ERROR;
    $wars = UCLA_REQUESTOR_WARNING;
    $worstnote = null;
    $br = html_writer::empty_tag('br');

    $rucr = 'tool_uclacourserequestor';

    // This is the returned display-ready row
    $formatted = array();

    // Find the host and stuff...
    $key = $requestinfo['setid'];

    $ignored = request_ignored($requestinfo);

    $actiondefault = null;
    $addedtext = '';
    $e = UCLA_REQUESTOR_CANCELLED;
    if (isset($requestinfo[$wars][$e])) {
        $worstnote = $wars;
        $addedtext = $br . get_string($e, $rucr);
        unset($requestinfo[$errs][$e]);
    }

    $maybeexists = array('delete', 'build');
    foreach ($maybeexists as $k) {
        if (isset($requestinfo[$k])) {
            $formatted[$k] = html_writer::checkbox("$key-$k", '1', 
                $requestinfo[$k], $addedtext);
        }
    }

    // People edit these per row 
    $editable = false;

    if ($requestinfo['action'] != UCLA_COURSE_BUILT 
            && empty($requestinfo[$errs]) && !$ignored) {
        $editable = true;
    }

    // Request time
    $timestr = '';
    $f = 'timerequested';
    $dds = 'Y-m-d g:i A';
    if (!empty($requestinfo[$f])) {
        $timestr = date($dds, $requestinfo[$f]);
    } else {
        $timestr = date($dds);
    }

    $formatted[$f] = $timestr;
    unset($requestinfo[$f]);

    // Handle fields where you can use get_string()
    $tr = 'action';
    $oldval = $requestinfo[$tr];
    $inputname = "$key-$tr";

    if ($oldval == UCLA_COURSE_FAILED) {
        $options = array(
            UCLA_COURSE_TOBUILD => 
                requestor_statuses_translate(UCLA_COURSE_TOBUILD), 
            UCLA_COURSE_FAILED => 
                requestor_statuses_translate(UCLA_COURSE_FAILED)
        );

        $formatted[$tr] = html_writer::select($options, $inputname, 
            UCLA_COURSE_FAILED);
    } else {
        $formatted[$tr] = 
            html_writer::tag('span', requestor_statuses_translate($oldval),
                array('class' => $oldval)) 
            . html_writer::empty_tag('input', array(
                'name' => $inputname, 
                'type' => 'hidden', 
                'value' => $oldval
            ));
                    
    }

    unset($requestinfo[$tr]);
    

    // Handle separate empty fields with new strings
    $translatables = array('id', 'courseid');

    // Convention?
    foreach ($translatables as $tr) {
        if (!isset($requestinfo[$tr])) {
            continue;
        }

        $oldval = $requestinfo[$tr];
        $newval = '';

        if ($oldval == null) {
            $newval = get_string('newrequest' . $tr, $rucr);
        } else {
            // if on 'courseid', then make link to a course
            if ('courseid' == $tr) {                
                $newval = html_writer::link(new moodle_url(
                    '/course/view.php', 
                    array('id' => $oldval)), 
                    $oldval, 
                    array('target' => '_blank'
                ));
            } else {
                $newval = $oldval;                
            }
        }

        $formatted[$tr] = $newval;
    }

    // We could slightly more automate this
    $e = UCLA_REQUESTOR_EXIST;
    if (!empty($requestinfo[$errs][$e])) {
        $worstnote = $errs;
        $formatted['id'] = get_string($e, $rucr);
        unset($requestinfo[$errs][$e]);
    }

    // Handle checkboxes
    $editables = ucla_courserequests::get_editables();

    // This is a deprecated local variable
    $sharedattr = array();
    if (!$editable) {
        $sharedattr['disabled'] = true;
    }

    foreach ($editables as $editme) {
        if (isset($formatted[$editme])) {
            continue;
        }

        // The defaults should've been handled these a long time ago
        if (!isset($requestinfo[$editme])) {
            $oldval = false;
        } else {
            $oldval = $requestinfo[$editme];
        }

        $formatted[$editme] = html_writer::checkbox("$key-$editme",
            '1', $oldval, '', $sharedattr);

        unset($requestinfo[$editme]);
    }

    // Handle Crosslists
    $f = 'crosslists';
    $ff = "$key-crosslists[]";
    $clinputattr = array(
        'type' => 'text',
        'name' => "$ff"
    );
    
    if ($ignored) {
        $clinputattr['disabled'] = true;
    }

    $ocls = array();

    // Add self to crosslists
    $riclstr = html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => $ff,
        'value' => $requestinfo['srs']
    ));

    if (!empty($requestinfo[$f])) {
        foreach ($requestinfo[$f] as $clkey => $ocl) {
            $clsrs = $ocl['srs'];
            $moreinfo = requestor_dept_course($ocl);
            if (!empty($moreinfo)) {
                $moreinfo = '(' . $moreinfo . ')';
            } else {
                $moreinfo = '';
            }

            // Perhaps refactor this code later?
            if (!empty($ocl[$errs])) {
                // Save this for later
                $worstnote = $errs;

                $errstr = '';
                foreach ($ocl[$errs] as $error => $true) {
                    $errstr .= get_string($error, $rucr);
                    unset($ocl[$errs][$error]);
                }

                // There was an error, display editable field and error msg
                $clinputattr['value'] = $clsrs;

                $clinput = html_writer::tag(
                    'div',
                    html_writer::tag(
                        'div', 
                        $errstr . $br . html_writer::empty_tag(
                            'input', 
                            $clinputattr
                        ) . "$br $moreinfo", 
                        array('class' => 'error')
                    ),
                    array('class' => 'mform')
                );
            } else {
                $warstr = '';

                if (!empty($ocl[$wars])) {
                    if ($worstnote == null) {
                        $worstnote = $wars;
                    }

                    foreach ($ocl[$wars] as $warning => $true) {
                        $warstr .= get_string($warning, $rucr);
                    }
                }

                // Display check box
                $clinput = html_writer::checkbox(
                    $ff,
                    $clsrs, 
                    true, 
                    $clkey . $moreinfo,
                    $clinputattr
                ) . html_writer::tag(
                        'span', 
                        $warstr,
                        array('class' => 'warning')
                    );
            }

            $riclstr .= $clinput . $br;

            // Instructors merge up into host course
            foreach ($ocl['instructor'] as $k => $v) {
                $requestinfo['instructor'][$k] = $v;
            }
        }
    }
  
    if (!$ignored) {
        // Add a new thing
        unset($clinputattr['value']);     
        $riclstr .= html_writer::empty_tag('input', $clinputattr);
        $riclstr .= html_writer::empty_tag(
            'input', 
            array(
                'type' => 'submit',
                'name' => "$key-add-crosslist",
                'value' => get_string('addmorecrosslist', $rucr)
            )
        );
    }
   
    $e = UCLA_REQUESTOR_BADCL;
    if (!empty($requestinfo[$errs][$e])) {
        $riclstr .= $br . get_string('hostandchild', $rucr);
        $worstnote = $errs;
        unset($requestinfo[$errs][$e]);
    }

    $formatted['crosslists'] = $riclstr;

    // Instructors
    $instrstr = '';
    if (empty($requestinfo['instructor'])) {
        $instrstr = get_string('noinst', $rucr);
    } else {
        if (!is_array($requestinfo['instructor'])) {
            debugging('non-arr-inst');
        }
        $instrstr = implode(' / ', $requestinfo['instructor']);
    }

    $formatted['instructor'] = $instrstr;

    unset($requestinfo['instructor']);


    // Include all the non-changable but displayed data.
    foreach ($requestinfo as $k => $v) {
        if (!isset($formatted[$k])) {
            $formatted[$k] = $v;
        }
    }

    $ordfor = array();
    $notused = array();
    $ordered = array(
        'id', 'courseid', 
        'term', 'srs', 
        'department', 'course', 'instructor',
        'crosslists',
        'timerequested',
        'requestoremail', 'action',
        'mailinst', 'hidden', 'nourlupdate',
        'delete', 'build'
    );

    foreach ($ordered as $field) {
        if (!isset($formatted[$field])) {
            $ordfor[$field] = '';
            $notused[] = $field;
        } else {
            $ordfor[$field] = $formatted[$field];
        }
    }

    // add error/warn in here...
    if ($worstnote != null) {
        $ordfor['errclass'] = $worstnote;
    }

    return $ordfor;
}

/**
 *  Convenience function returns the concatenation of the subject area
 *  and the course (course num and sect num).
 **/
function requestor_dept_course($request) {
    $co = 'course';
    $de = 'department';

    $moreinfo = '';
    if (!empty($request[$co]) && !empty($request[$de])) {
        $moreinfo = $request[$de] . ' ' . $request[$co];
    }

    return $moreinfo;
}

/**
 *  Takes a status/action and translates it to a human readable form.
 **/
function requestor_statuses_translate($status) {
    $rucr = 'tool_uclacourserequestor';

    if (get_string_manager()->string_exists($status, $rucr)) {
        $posstext = get_string($status, $rucr);
    } else {
        $posstext = ucwords($status);
    }

    return $posstext;
}

/**
 *  Calculates the available filters for the drop down menu for
 *  Viewing existing request entries.
 **/
function get_requestor_view_fields() {
    global $DB;

    $prefields = array('term', 'department', 'action');
    $prefieldstr = trim(implode(', ', $prefields));

    $rsid = 'CONCAT(' . $prefieldstr . ')';
    if (!$prefieldstr) {
        $prefieldstr = $rsid;
    } else {
        $prefieldstr = $rsid . ', ' . $prefieldstr;
    }

    $builtcategories = $DB->get_records('ucla_request_classes', null, 
        'department', 'DISTINCT ' . $prefieldstr);

    $prefieldsdata = array();
    foreach ($builtcategories as $builts) {
        foreach ($prefields as $prefield) {
            $varname = $prefield;

            if (!isset($prefieldsdata[$varname])) {
                $prefieldsdata[$varname] = array();
            }

            $prefieldsdata[$varname][$builts->$prefield] = $builts->$prefield;
        }
    }

    return $prefieldsdata;
}

/**
 *  Takes about 0.015 second per entry.
 **/
function get_courses_for_subj_area($term, $subjarea) {
    $t = microtime(true);
    $result = registrar_query::run_registrar_query('cis_coursegetall',
        array(array($term, $subjarea)), true);
    $e = microtime(true) - $t;
    $c = (float) count($result);

    return $result;
}

/**
 *  Takes about 0.025 second.
 **/
function get_course_info_from_registrar($term, $srs) {
    $result = registrar_query::run_registrar_query('ccle_getclasses',
        array(array($term, $srs)), true);

    if ($result) {
        return array_shift($result);
    }

    return $result;
}

/**
 *  Takes about 0.015 second.
 **/
function get_instructor_info_from_registrar($term, $srs) {
    $result = registrar_query::run_registrar_query('ccle_courseinstructorsget',
        array(array($term, $srs)), true);
    return $result;
}

// EOF
