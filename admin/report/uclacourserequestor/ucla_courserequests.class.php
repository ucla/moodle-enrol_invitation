<?php

/**
 *  This class represents a set of course requests.
 **/
class ucla_courserequests {
    // This is the requests indexed by setid
    var $setindex = array();
    // These are abandoned course requests
    var $abandoned = array();

    var $unpreppedsetid = 1;

    private $_validated = null;

    static function get_editables() {
        return array('mailinst', 'nourlupdate');
    }

    /**
     *  Adds a set to the bunch of course requests.
     **/
    function add_set($set) {
        $this->_validated = false;

        $setid = null;
        $f = 'setid';
        foreach ($set as $rq) {
            if (isset($rq[$f])) {
                if ($setid == null) {
                    $setid = $rq[$f];
                } else if ($setid != $rq[$f]) {
                    debugging('Mismatching setid expected ' . $setid 
                        . ' got ' . $rq[$f]);
                }
            }
        }

        if ($setid == null) {
            $setid = $this->get_unprepped_setid();
        }

        foreach ($set as $k => $r) {
            $set[$k][$f] = $setid;
        }

        $this->setindex[$setid] = $set;
    }

    /**
     *  This and the following function need to be in sync.
     *  Finds a special index for set id in cases where we need a new index
     *  that is not in the db.
     **/
    function get_unprepped_setid() {
        $upsi = 'new_' . $this->unpreppedsetid;
        $this->unpreppedsetid++;
        return $upsi;
    }

    /**
     *  Checks if a particular used set id is a non-db thing.
     **/
    function is_unprepped_setid($setid) {
        return preg_match('/new_[0-9]*/', $setid);
    }

    /**
     *  Apply changes onto our current set index.
     **/
    function apply_changes($changes, $context) {
        $this->_validated = false;

        $checkers = self::get_editables();

        if ($context == UCLA_REQUESTOR_FETCH) {
            $d = 'build';
        } else {
            $d = 'delete';
        }

        $checkers[$d] = $d;

        $h = 'hostcourse';

        foreach ($this->setindex as $setid => $courses) {
            $setkey = false;
            $theterm = false;

            // Find the key of the host
            // TODO this method may come to bite us later
            foreach ($courses as $key => $course) {
                $theterm = $course['term'];

                if (isset($changes[$setid][$key])) {
                    $setkey = $key;
                    break;
                }
            }

            // No key was found, that means everything was disabled
            if (!$setkey) {
                //$this->apply_to_set($setid, $d, 1);
            } else if (isset($changes[$setid][$setkey])) {
                $thechanges = $changes[$setid][$setkey];

                if (empty($thechanges[$d])) {
                    $appval = 0;
                    // Don't apply any other chnages if the build was unset
                } else {
                    $appval = 1;
                }

                $courses = apply_to_set($courses, $d, $appval);
                
                $testcourse = reset($courses);
                if (!request_ignored($testcourse)) {
                    // We're going to deal with checkboxes
                    foreach ($checkers as $checker) {
                        if (!empty($thechanges[$checker])) {
                            $checkapply = 1;
                        } else {
                            $checkapply = 0;
                        }

                        $courses = apply_to_set($courses, $checker, 
                            $checkapply);
                    }

                    // Now we're going to deal with the crosslists
                    $clind = array();

                    if (!isset($thechanges['crosslists'])) {
                        $thechanges['crosslists'] = array();
                    }

                    foreach ($thechanges['crosslists'] as $cls) {
                        if (!empty($cls)) {
                            $clind[$cls] = $cls;
                        }
                    }

                    foreach ($courses as $key => $c) {
                        if ($key == $setkey) {
                            continue;
                        }

                        $srs = $c['srs'];
                        if (!isset($clind[$srs])) {
                            debugging('removing ' . $srs . ' from ' . $setkey);
                            // TODO Move it as its own host?
                            $this->abandoned[$key] = $c;
                            unset($courses[$key]);
                        } else {
                            // Everything is all right
                            unset($clind[$srs]);
                        }
                    }

                    foreach ($clind as $ncl) {
                        // New hosts...
                        // For now just add it.
                        $nr = get_request_info($theterm, $ncl);

                        // Things fetched from the registrar should NOT
                        // have this field set...
                        if (isset($nr['hostcourse']) 
                                && $nr['hostcourse'] == 0) {
                            // We need to force an error later
                            $this->add_set(get_crosslist_set_for_host($nr));
                        } else {
                            $nr['hostcourse'] = 0;
                        }

                        $nr['setid'] = $setid;

                        // We're going to add the host, if there is one
                        $nrkey = request_make_key($nr);

                        if (!isset($courses[$nrkey])) {
                            $courses[$nrkey] = $nr;
                        }
                    }

                }

                $this->setindex[$setid] = $courses;
            }

        }
    }

    /** 
     *  Validates and injects errors into each request-course.
     **/
    function validate_requests($context) {
        if ($context == null && !empty($this->_validated)) {
            return $this->_validated;
        }

        // Host courses
        $hostcourses = array();

        // Non-host courses
        $nonhostcourses = array();

        $requestinfos = $this->setindex;

        // Split the requests between host courses and non-host courses
        $h = 'hostcourse';
        $errs = UCLA_REQUESTOR_ERROR;

        $e = UCLA_REQUESTOR_BADCL;
        foreach ($requestinfos as $setid => $set) {
            $hcthere = false;
            foreach ($set as $key => $course) {
                if (request_ignored($course)) {
                    // just hack it
                    $hcthere = true;
                    continue;
                }

                // Avoid affecting existing requests when fetching requests from
                // the Registrar
                if (isset($course['id']) 
                        && $context == UCLA_REQUESTOR_FETCH) {
                    $course[$errs][UCLA_REQUESTOR_EXIST] = true;
                }

                $hostnum = $course[$h];
                if ($hostnum > 0) {
                    // We are joining host courses
                    if ($hcthere !== false) {
                        // Mark the crosslist
                        if ($course['courseid'] != null) {
                            $course[UCLA_REQUESTOR_WARNING]
                                [UCLA_REQUESTOR_GHOST] = true;
                        }
                    } else {
                        $hcthere = $key;
                    }

                    // I don't believe we need to worry about multiple
                    // host courses existing in the sets
                    $hostcourses[$key] = $setid;
                } else {
                    // But we do need to worry about this...
                    if (isset($nonhostcourses[$key])) {
                        $otherset = $nonhostcourses[$key];

                        $requestinfos[$otherset][$key][$errs][$e] = true;
                        $course[$errs][$e] = true;
                    } else {
                        $nonhostcourses[$key] = $setid;
                    }
                }

                $requestinfos[$setid][$key] = $course;
            }

            // This is a data inconsistency, this error needs to be handled
            // earlier.
            if (!$hcthere) {
                foreach ($set as $key => $course) {
                    $requestinfos[$setid][$key][$errs][UCLA_REQUESTOR_BADHOST] 
                        = true;
                }
            }
        }

        // No course in the list of hosts should be in the list of nonhosts
        foreach ($hostcourses as $key => $setid) {
            if (isset($nonhostcourses[$key])) {
                // Mark both the set of the victim host course and the 
                // culprit host course

                // This is the host course
                $requestinfos[$setid][$key][$errs][$e] = true;

                // This is the child course
                $requestinfos[$nonhostcourses[$key]][$key][$errs][$e] = true;
            }
        }

        $this->_validated = $requestinfos;
        return $requestinfos;
    }

    /** 
     *  Commits this requests representation.
     **/
    function commit() {
        // Make a giant SQL statement?
        global $DB;

        $requests = $this->validate_requests(null);
        $maxsetid = reset($DB->get_record_sql(
            'SELECT MAX(`setid`) FROM {ucla_request_classes}'
        ));

        $successes = array();
        $now = time();

        $i = 'instructor';

        foreach ($requests as $setid => $set) {
            $failset = false;
            $requestentries = array();

            foreach ($set as $k => $r) {
                if (!empty($r[UCLA_REQUESTOR_ERROR])) {
                    $failset = true;
                    break;
                }
            }

            if ($failset) {
                continue;
            }

            // Find a valid set id
            if ($this->is_unprepped_setid($setid)) {
                $maxsetid++;
                $set = apply_to_set($set, 'setid', $maxsetid);
            }

            $properhost = set_find_host($set);

            $set = apply_to_set($set, 'hostcourse', 0);
            $set[$properhost]['hostcourse'] = 1;

            foreach ($set as $key => $request) {
                if (request_ignored($request)) {
                    continue;
                }

                if (!empty($request[$i])) {
                    $request[$i] = implode(' / ', $request[$i]);
                }

                try {
                    if (isset($request['id'])) {
                        $DB->update_record('ucla_request_classes', $request);
                    } else {
                        $request['added_at'] = $now;

                        $insertid = $DB->insert_record('ucla_request_classes',
                            $request);

                        $set[$key]['id'] = $insertid;
                    }
                } catch (dml_exception $e) {
                    var_dump($e);
                } catch (coding_exception $e) {
                    var_dump($request);
                }
            }

            $successes[$setid] = $set;
        }

        return $successes;
    }
}

// EoF
