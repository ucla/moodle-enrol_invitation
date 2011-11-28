<?php

/**
 *  A library of functions useful for course requestor and probably
 *  course creator.
 **/

defined('MOODLE_INTERNAL') || die();

// These are the requestor flags used.
define('UCLA_COURSE_TOBUILD', 'build');
define('UCLA_COURSE_BUILT', 'built');

define('UCLA_COURSE_PENDING', 'pending');

define('UCLA_REQUESTOR_ERROR', 'errorrow');
define('UCLA_REQUESTOR_EXIST', 'existrow');

define('UCLA_REQUESTOR_FETCH', 'fetch');
define('UCLA_REQUESTOR_VIEW', 'views');

$uclalib = $CFG->dirroot . '/local/ucla/lib.php';
require_once($uclalib);

if (!function_exists('ucla_validator')) {
    function ucla_validator($t, $v) {
        if ($t == 'srs') {
            return preg_match('/[0-9]{9}/', $v);
        }

        if ($t == 'term') {
            return preg_match('/[0-9][0-9][WS1F]/', $v);
        }

        return false;
    }
}

ucla_require_registrar();

/**
 * Determines if course has already been requested or not. Also sets the global
 * variables $existingcourse and $existingaliascourse if they are not already
 * set.
 * 
 * @global mixed $DB
 * 
 * @param string $term
 * @param int $srs 
 * 
 * @return object   Representing the request table, with its crosslists array 
 *                  placed into $object->crosslists.
 */
function get_course_request($term, $srs) {
    $r = get_course_requests(array(array('term' => $term, 'srs' => $srs)));

    if (!empty($r)) {
        return reset($r);
    }

    return false;
}

function get_child_host_requests($children) {
    return array();
}

/**
 *  Fetches requests either by term or by term-srs.
 *  You can currently only fetch by host requests.
 *  @param $inputs
 *      This can either be an Array of terms or an
 *      Array of Array('term' => term, 'srs' => srs)
 **/
function get_course_requests($inputs=array()) {
    global $DB;

    if (empty($inputs)) {
        return array();
    }

    $where = '';
    $params = array();

    $indexedsrses = array();

    // For crosslists
    $clwhere = '';
    $clparams = array();

    $rsid = 'rsid';

    // Build parameters and SQL
    if (!empty($inputs)) {
        $first_one = reset($inputs);
        if (!is_array($first_one)) {
            // This means a set of terms
            $sql = '';
            list($sql, $params)  = $DB->get_in_or_equal($terms);
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

                $clparams[] = $term;
                $clparams[] = $srs;
                $clparams[] = $srs;
                $clwheres[] = '`term` = ? AND (`srs` = ? OR `aliassrs` = ?)';

            }

            $where = implode(' OR ', $wheres);

            $clwhere = implode(' OR ', $clwheres);
        }
    }

    // Fetch none of them (on the safe side)
    if (empty($params)) {
        return array();
    }

    $results = $DB->get_records_select('ucla_request_classes', $where,
        $params, '', "CONCAT(`term`, '-', `srs`) AS $rsid,"
            . "{ucla_request_classes}.* ");

    $clresults = $DB->get_records_select('ucla_request_crosslist', $clwhere,
        $clparams, '', "CONCAT(`term`, '-', `aliassrs`) AS $rsid, "
            . "{ucla_request_crosslist}.* ");

    $clindexedr = array();
    foreach ($clresults as $clresult) {
        unset($clresult->{$rsid});

        $key = $clresult->term . '-' . $clresult->srs;

        if (!isset($clindexedr[$key])) {
            $clindexedr[$key] = array();
        }

        $clindexedr[$key][] = $clresult->aliassrs;
    }

    // Maybe turn this into a shitty half-functional function?
    foreach ($results as $k => $result) {
        unset($result->{$rsid});

        $result->crosslists = array();
        if (!empty($clindexedr[$k])) {
            foreach ($clindexedr[$k] as $cls) {
                $result->crosslists[] = $cls;
            }
        }

        $results[$k] = $result;
    }

    return $results;
}

/**
 *  Convenience function returns either the request info from the local DB
 *  or automatically queries the Registrar.
 **/
function get_request_info($term, $srs) {
    $prev = get_course_request($term, $srs);
    if ($prev) {
        return array($prev);
    }

    $courseinfo = get_course_info_from_registrar($term, $srs);

    if (!$courseinfo || requestor_ignore_entry($courseinfo)) {
        return false;
    }

    $instinfo = get_instructor_info_from_registrar($term, $srs);

    return array(prep_registrar_entry($courseinfo, $instinfo));
}

/**
 *  Wastes clock cycles and returns the crosslist checking mechanism.
 *  Takes about 0.2 seconds.
 **/
function get_crosslisted_courses($term, $srs) {
    global $CFG;
    
    $regurl = 'http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/'
        . 'getConSched?user=' . $CFG->registrar_dbuser . '&pass='
        . $CFG->registrar_dbpass . '&term=' . $term . '&SRS=' . $srs;
  
    $t = microtime(true);
    $r = new SimpleXMLElement($regurl, 0, true);
    debugging(sprintf('crosslist %1.3f', microtime(true) - $t));

    return $r;
}

/**
 *  Customizable ignoring stuff.
 **/
function requestor_ignore_entry($data) {
    if (is_array($data)) {
        $data = (object) $data;
    }

    $subj = $data->subj_area;
    $num = $data->coursenum;

    if ($num > 495) {
        return true;
    }

    if ($subj == 'PHYSICS' && $num > 295) {
        return true;
    }

    return false;
}


/**
 *  Strips and simplifies data from the registrar to be ready for placement 
 *  in the request classes tables.
 **/
function prep_registrar_entry($regdata, $instinfo) {
    $req = array();

    if (is_array($regdata)) {
        $regdata = (object) $regdata;
    }

    $term = $regdata->term;
    $srs = $regdata->srs;

    $req['term'] = $term;
    $req['srs'] = $srs;

    $req['department'] = $regdata->subj_area;
    $req['course'] = get_course_from_reginfo($regdata);

    $inststr = '';

    if (!isset($regdata->instructor)) {
        $instarr = array();

        // This is some redundant code...
        foreach ($instinfo as $inst) {
            $inst = (object) $inst;
            $fn = $inst->first_name_person;
            $ln = $inst->last_name_person;

            if ($fn && $ln) {
                $u = new stdClass();

                $u->firstname = $fn;
                $u->lastname = $ln;

                $fullname = fullname($u);

                $instarr[$fullname] = $fullname;
            }
        }

        if (empty($instarr)) {
            $isntarr[] = '';
        }

        $inststr = implode(' / ', $instarr);
    } else {
        $inststr = $regdata->instructor;
    }

    $req['instructor'] = $inststr;

    // These are entries from the registrar, so they need to have their
    // crosslists checked
    $clists = get_crosslisted_courses($term, $srs);

    $req['crosslists'] = array();

    if (!empty($clists->getConSchedData)) {
        $exts = array();

        $ts = $clists->getConSchedData;

        if (is_array($ts)) {
            foreach ($ts as $termsrs) {
                $ext = extract_term_srs_xml($termsrs);

                if (!$ext) {
                    continue;
                }

                $exts[] = $ext;
            }
        } else {
            $ext = extract_term_srs_xml($ts);

            if ($ext) {
                $exts[] = $ext;
            }
        }

        foreach ($exts as $ext) {
            $clkey = request_make_key($ext);
            $req['crosslists'][$clkey] = array();
            foreach ($ext as $k => $d) {
                $req['crosslists'][$clkey][$k] = $d;
            }
        }
    }

    $req = get_request_crosslists_from_registrar($req);

    return $req;
}

function get_request_crosslists_from_registrar($req) {
    if (!empty($req['crosslists'])) {
        foreach ($req['crosslists'] as $k => $cl) {
            $regi = get_course_info_from_registrar($cl['term'], $cl['srs']);

            if ($regi) {
                $cl['course'] = $regi->subj_area . '-'
                    . get_course_from_reginfo($regi);
            }

            $req['crosslists'][$k] = $cl;
        }
    }

    return $req;

}

function get_course_from_reginfo($regdata) {
    return trim($regdata->coursenum . '-' . $regdata->sectnum);
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
 *  Validates certain fields of the request.
 *  This is run before every entry in the request form is realized.
 *  @param $request Array of the request information.
 *  @param $exisint 
 *      Array ( <term> => Array ( <srs> => true ) ) set if the request
 *      exists in the request tables.
 *  @return Array ( <field> => <ErrorArray> ) in the context of the
 *      current request.
 **/
function request_validate($request, $existing=array()) {
    $errors = array();

    $f = 'crosslists';

    if (!empty($request[$f])) {
        $tt = $request['term'];
        $ts = $request['srs'];

        $existing[$tt][$ts] = true;

        foreach ($request[$f] as $cl) {
            $k = request_make_key($cl);

            foreach ($cl as $type => $val) {
                if ($type == 'course') {
                    continue;
                }

                if (!ucla_validator($type, $val)) {
                    $errors[$f][$k] = $type . 'error';
                    continue 2;
                }
            }
            
            if (empty($cl['course'])) {
                $errors[$f][$k] = 'checktermsrs';
                continue;
            }

            $term = $cl['term'];
            $srs = $cl['srs'];

            if ($srs == $request['srs']) {
                $errors[$f][$k] = 'samesrserror';
            }

            if (isset($existing[$term][$srs])) {
                $errors[$f][$k] = 'existingerror';

                // This is redundant
                continue;
            }

            $existing[$term][$srs] = true;
        }
    }

    return $errors;
}

function request_make_key($sr) {
    if (is_object($sr)) {
        $sr = get_object_vars($sr);
    }

    if (empty($sr['term']) || empty($sr['srs'])) {
        debugging('No key from object: ' . print_r($sr, true));
        return false;
    }

    return $sr['term'] . '-' . $sr['srs'];
}

/** 
 *  Fix stuff for putting into html AND db tables.
 **/
function prep_request_entries($requestinfos, $context, $commit=false) {
    // Figure defaults
    $formatted = array();
    $specialrows = array();
    $defaults = array();

    // Determine some defaults
    $defaults['hidden'] = get_config('moodlecourse')->visible;

    $rsucr = 'report/uclacourserequestor';
    $configs = get_config('report/uclacourserequestor');

    $editable = array('mailinst', 'hidden', 'force_urlupdate', 
        'force_no_urlupdate');

    $translate_tf = array('true' => 1, 'false' => 0);

    // These are options that are soft, defaults changed through UI
    foreach ($editable as $ed) {
        $varname = $ed . '_default';
        $d = false;

        if (isset($configs->$varname)) {
            $d = $configs->$varname;
        }

        $defaults[$ed] = $d;
    }

    $defaults['action'] = UCLA_COURSE_TOBUILD;

    $exid = null;

    // This is the prototype that has all the fields
    $firstone = array();

    $usedtermsrs = array();
    $indexedri = array();
    $indexederr = array();

    // Validate each request
    foreach ($requestinfos as $rik => $ri) {
        if (is_object($ri)) {
            $ri = get_object_vars($ri);
        }

        if (isset($ri['add-crosslist'])) {
            unset($ri['add-crosslist']);
        }

        $srs = $ri['srs'];
        $term = $ri['term'];

        if (!isset($usedtermsrs[$term])) {
            $usedtermsrs[$term] = array();
        }
        
        $usedtermsrs[$term][$srs] = true;

        // Avoid repeated crosslists
        if (!empty($ri['crosslists'])) {
            foreach ($ri['crosslists'] as $n => $ci) {
                $csrs = $ci['srs'];
                $cterm = $ci['term'];

                if (empty($csrs) || empty($cterm)) {
                    unset($ri['crosslists'][$n]);
                }
               
                // Add count
                if (!isset($usedtermsrs[$cterm][$csrs])) {
                    $usedtermsrs[$cterm][$csrs] = 0;
                } 

                $usedtermsrs[$cterm][$csrs]++;
            }
        }

        // Add count copy-pasta
        if (!isset($usedtermsrs[$term][$srs])) {
            $usedtermsrs[$term][$srs] = 0;
        }

        $usedtermsrs[$term][$srs]++;

        $rkey = request_make_key($ri);

        // Fill out defaults for non-present but existing fields
        foreach ($defaults as $field => $default) {
            if (!isset($ri[$field])) {
                $ri[$field] = $default;
            } else {
                $fi = $ri[$field];
                if (isset($translate_tf[$fi])) {
                    $ri[$field] = $translate_tf[$fi];
                }
            }
        }
        
        $specialrows[$rkey] = array();

        if (isset($ri['id']) && $context == UCLA_REQUESTOR_FETCH) {
            $specialrows[$rkey][UCLA_REQUESTOR_EXIST] = true;
        }

        // Validate requests independent of other entries
        $err = request_validate($ri);
        $indexederr[$rkey] = $err;

        if (!empty($err)) {
            $specialrows[$rkey][UCLA_REQUESTOR_ERROR] = true;
        }

        $indexedri[$rkey] = $ri;
    }

    // Commit.
    foreach ($specialrows as $key => $props) {
        if (!empty($props)) {
            unset($indexedri[$key]);
        }

        $specialrows[$key] = implode(' ', array_keys($props));
    }

    if ($commit) {
        $corrects = requestor_commit_requests($indexedri);

        foreach ($corrects as $key) {
            unset($indexedri[$key]);
        }
    }

    // Prep each entry for display in tables
    foreach ($indexedri as $key => $ri) {
        // Prep everything for display
        $prepped = prep_request_entry($ri, $indexederr[$key]);

        // We're going to make sure all the keys in this array
        // will be ordered the same as the first one, since that one
        // is what is used to generate the header.
        $ordered_prep = array();

        // This one must come first
        if (!isset($prepped['id'])) {
            $exid = array('id' => get_string('newrequest', 
                'report_uclacourserequestor'));

            $prepped = array_merge($exid, $prepped);
        }

        // TODO order the fields for the first row 

        // Make all entries' keys ordered like the first one
        // Note that you should NOT get an undefined index error here
        // That means that there's something wrong way back when
        if (empty($firstone)) {
            $firstone = $prepped;
            $ordered_prep = $firstone;
        } else {
            foreach ($firstone as $k => $v) {
                $ordered_prep[$k] = $prepped[$k];
            }
        }

        $formatted[$key] = $ordered_prep;
    }

    return array($specialrows, $formatted);
}

/**
 *  Commits a bunch of requests to the database.
 *  @return Array of successful keys
 **/
function requestor_commit_requests($requests) {
    // Make a giant SQL statement?
    global $DB;

    $successes = array();
    $now = time();

    foreach ($requests as $key => $request) {
        try {
            if (isset($request['id'])) {
                $DB->update_record('ucla_request_classes',
                    $request);
            } else { 
                $request['added_at'] = $now;

                $insertid = $DB->insert_record('ucla_request_classes',
                    $request);

            }
        } catch (dml_exception $e) {
            var_dump($e);
            continue;
        }

        $successes[$key] = $key;
    }

    foreach ($successes as $skey) {

    }

    return $successes;
}

/**
 *  Quick function that doesn't really need to be a function,
 *  but it parses the fields from a previously displayed 
 *  requestor contents tables.
 **/
function request_parse_input($key, $value) {

    $vals = array();
    preg_match('/([0-9][0-9][WS1F])-([0-9]{9})-(.*)$/', $key, $vals);

    if ($vals) {
        $term = $vals[1];
        $srs = $vals[2];
        $var = $vals[3];

        return array($term, $srs, $var, $value);
    }

    return false;
}

// TODO move defaults out
/**
 *  A lot of miscellany is handled here.
 *  This function assumes that all the request info is proper and checked,
 *  and is getting prepped to be displayed in html_write::table()
 **/
function prep_request_entry($requestinfo, $errors=array()) {
    global $DB;

    if (is_object($requestinfo)) {
        $requestinfo = get_object_vars($requestinfo);
    }

    // This no longer not needs to be displayed
    unset($requestinfo['force_urlupdate']);

    $key = request_make_key($requestinfo);

    if (empty($requestinfo['instructor'])) {
        $requestinfo['instructor'] = get_string('noinst', 
            'report_uclacourserequestor');
    }
  
    // People edit these per row 
    $editable = false;
    if ($requestinfo['action'] == UCLA_COURSE_TOBUILD) {
        $editable = true;
    }
   
    // These are the fields that have been manually added input fields
    $hidden_inputs_already = array();

    if (!empty($requestinfo['added_at'])) {
        $requestinfo['added_at'] = date('Y-m-d g:i A', 
            $requestinfo['added_at']);
    }

    // Handle fields where you can use get_string()
    $translatable = array('action');

    $hidden_inputs_already = array_merge($hidden_inputs_already, 
        $translatable);

    foreach ($translatable as $tr) {
        $oldval = $requestinfo[$tr];

        $requestinfo[$tr] = 
            html_writer::tag('span', 
                requestor_statuses_translate($oldval)
                . html_writer::empty_tag(
                    'input', 
                    array(
                        'type' => 'hidden',
                        'name' => "$key-$tr",
                        'value' => $oldval
                    )
                ), array('class' => $oldval));
    }

    // Handle checkboxes
    $editables = array('mailinst', 'hidden', 'force_no_urlupdate');

    $hidden_inputs_already = array_merge($hidden_inputs_already,
        $editables);

    $sharedattr = array();
    if (!$editable) {
        $sharedattr['disabled'] = 'true';
    }

    foreach ($editables as $editme) {
        $oldval = $requestinfo[$editme];

        $requestinfo[$editme] = html_writer::checkbox("$key-$editme",
            'true', $oldval, '', $sharedattr);
    }

    // Handle Crosslists
    $clinputattr = array(
        'type' => 'text',
        'name' => "$key-new-crosslists[]"
    );

    $ocl = array();
    // Translate crosslists data into crosslists forms
    if (!empty($requestinfo['crosslists'])) {
        foreach ($requestinfo['crosslists'] as $ricl) {
            $ocl[] = $ricl;
        }
    }

    $riclstr = '';

    // So dirty
    foreach ($ocl as $origcrosslist) {
        $clsrs = $origcrosslist['srs'];

        if (empty($clsrs)) {
            continue;
        }

        $clkey = request_make_key($origcrosslist);

        $clinputattr['value'] = $clkey;

        if (!empty($errors['crosslists'][$clkey])) {
            $errstr = $errors['crosslists'][$clkey];
            $clinputattr['value'] = $clsrs;

            // There was an error, display editable field and error msg
            $clinput = html_writer::tag(
                'div',
                html_writer::tag(
                    'div', 
                    get_string($errstr, 'report_uclacourserequestor') 
                        . html_writer::tag('input', '', $clinputattr), 
                    array('class' => 'error')
                ),
                array('class' => 'mform')
            );
        } else {
            // Display check box
            $clhidden = '';

            // We're going to put these together again later
            foreach ($origcrosslist as $oclkey => $val) {
                $clhidden .= html_writer::tag('input', '',
                    array(
                        'type' => 'hidden',
                        'name' => "$key-enabled-crosslists-$oclkey" . '[]',
                        'value' => $val
                    ));
            }

            $clinput = html_writer::checkbox("$key-enabled-crosslists[]",
                'true', true, '', $clinputattr) . $clkey 
                . ' (' . $origcrosslist['course'] . ')' . $clhidden;
        }

        $riclstr .= $clinput . html_writer::empty_tag('br');
    }
    
    // Add a new thing
    unset($clinputattr['value']);     
    $riclstr .= html_writer::tag('input', '', $clinputattr);

    $riclstr .= html_writer::tag(
        'input', 
        '',
        array(
            'type' => 'submit',
            'name' => "$key-add-crosslist",
            'value' => get_string('addmorecrosslist', 
                'report_uclacourserequestor')
        )
    );

    $requestinfo['crosslists'] = $riclstr;
    $hidden_inputs_already[] = 'crosslists';

    // These should not be displayed at all
    $donotdisplay = array(
        'crosslist'
    );

    // For the rest of the values, add a hidden field
    foreach ($requestinfo as $k => $v) {
        if (!in_array($k, $hidden_inputs_already)) {
            var_dump($k, $v);
            $requestinfo[$k] .= html_writer::empty_tag(
                'input',  
                array(
                    'type' => 'hidden',
                    'name' => "$key-$k",
                    'value' => $v
                )
            );
        }

        if (in_array($k, $donotdisplay)) {
            unset($requestinfo[$k]);
        }
    }

    return $requestinfo;
}

/**
 *  Takes a status/action and translates it to a human readable form.
 **/
function requestor_statuses_translate($status) {
    $rucr = 'report_uclacourserequestor';

    if (get_string_manager()->string_exists($status, $rucr)) {
        $posstext = get_string($status, $rucr);
    } else {
        $posstext = ucwords($status);
    }

    return $posstext;
}

// Less-hacky registrar functions
function get_courses_for_subj_area($term, $subjarea) {
    $t = microtime(true);
    $result = registrar_query::run_registrar_query('cis_coursegetall',
        array(array($term, $subjarea)), true);
    debugging(sprintf('subjarea %1.3f', microtime(true) - $t));

    return $result;
}

function get_course_info_from_registrar($term, $srs) {
    $t = microtime(true);
    var_dump($term, $srs);
    $result = registrar_query::run_registrar_query('ccle_getclasses',
        array(array($term, $srs)), true);
    debugging(sprintf('single %1.3f', microtime(true) - $t));

    if ($result) {
        return array_shift($result);
    }

    return $result;
}

function get_instructor_info_from_registrar($term, $srs) {
    $t = microtime(true);
    $result = registrar_query::run_registrar_query('ccle_courseinstructorsget',
        array(array($term, $srs)), true);

    debugging(sprintf('instructors %1.3f', microtime(true) - $t));
    return $result;
}

