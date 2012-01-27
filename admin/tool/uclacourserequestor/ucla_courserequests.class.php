<?php

/**
 *  This class represents a set of course requests.
 **/
class ucla_courserequests {
    // This is the requests indexed by setid
    var $setindex = array();

    // These are abandoned course requests
    var $abandoned = array();

    // These are records to be deleted.
    var $deletes = array();

    var $unpreppedsetid = 1;

    // Success flags should be >= 100
    const savesuccess = 100;
    const deletesuccess = 101;

    // Failed flags should be < self::savesuccess
    const savefailed = 0;
    const deletefailed = 1;

    // Soft fail...
    const deletecoursefailed = 50;

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

        $deletemode = false;
        if ($context == UCLA_REQUESTOR_FETCH) {
            $d = 'build';
        } else {
            $d = 'delete';
            $deletemode = true;
        }

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

            $thechanges = false;
            if (isset($changes[$setid][$setkey])) {
                $thechanges = $changes[$setid][$setkey];
            } else if ($deletemode) {
                $courses = apply_to_set($courses, $d, 0);
                $this->setindex[$setid] = $courses;
            }

            if ($thechanges !== false) {
                if (empty($thechanges[$d])) {
                    $appval = 0;
                } else {
                    $appval = 1;
                }

                // This will cause request_ignored to handle things properly
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

                    // Sanitize inputs
                    foreach ($thechanges['crosslists'] as $cls) {
                        if (!empty($cls) && ucla_validator('srs', $cls)) {
                            $clind[$cls] = $cls;
                        }
                    }

                    // Remove no longer existing crosslists
                    foreach ($courses as $key => $c) {
                        // Ignore the host course
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

                    // New hosts...
                    foreach ($clind as $ncl) {
                        // For now just add it.
                        $nr = get_request_info($theterm, $ncl);

                        // Things fetched from the registrar should NOT
                        // have this field set...
                        if (isset($nr[$h])) {
                            $newset = get_crosslist_set_for_host($nr);
                            if ($nr[$h] == 0) {
                            // We need to force an error later
                                $this->add_set($newset);
                            } else {
                                // integrate the set into the new set
                                foreach ($newset as $newreq) {
                                    $newreq[$h] = 0;
                                    $newreq['setid'] = $setid;
                                    $nrkey = make_idnumber($newreq);
                                    if (!isset($courses[$nrkey])) {
                                        $courses[$nrkey] = $newreq;
                                    }
                                }
                            }
                        } else {
                            // This was fetched from the Registrar, just add it
                            $nr['setid'] = $setid;
                            $nr[$h] = 0;

                            // We're going to add the host, if there is one
                            $nrkey = make_idnumber($nr);

                            if (!isset($courses[$nrkey])) {
                                $courses[$nrkey] = $nr;
                            }
                        }
                    }
                } else if ($deletemode) {
                    // Save this to be deleted when commit() is called
                    $this->deletes[$setid] = true;
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
                    debugging('ignoring ' . $course['srs']);
                    $hcthere = true;
                    continue;
                }

                // Avoid affecting existing requests when fetching requests from
                // the Registrar
                if (isset($course['id']) 
                        && $context == UCLA_REQUESTOR_FETCH) {
                    $course[$errs][UCLA_REQUESTOR_EXIST] = true;
                }

                if (isset($course[$h])) {
                    $hostnum = $course[$h];
                } else {
                    $hostnum = 0;
                }

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
        $urc = 'ucla_request_classes';

        $requests = $this->validate_requests(null);

        $maxsetid = reset($DB->get_record_sql(
            'SELECT MAX(`setid`) FROM ' . '{' . $urc . '}'
        ));

        $results = array();
        $now = time();

        $i = 'instructor';
        $h = 'hostcourse';

        foreach ($requests as $setid => $set) {
            $failset = false;
            $requestentries = array();
            $thisresult = self::savesuccess;

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

            $set = apply_to_set($set, $h, 0);
            $set[$properhost][$h] = 1;

            foreach ($set as $key => $request) {
                if (request_ignored($request)) {
                    continue;
                }

                if (!empty($request[$i])) {
                    $request[$i] = implode(' / ', $request[$i]);
                } else {
                    $request[$i] = '';
                }

                try {
                    if (isset($request['id'])) {
                        $DB->update_record($urc, $request);
                    } else {
                        $request['added_at'] = $now;

                        $insertid = $DB->insert_record($urc, $request);

                        $set[$key]['id'] = $insertid;
                    }
                } catch (dml_exception $e) {
                    var_dump($e);
                    $thisresult = self::savefailed;
                } catch (coding_exception $e) {
                    var_dump($request);
                    $thisresult = self::savefailed;
                }
            }

            $results[$setid] = $thisresult;
        }

        $coursestodelete = array();
        foreach ($this->deletes as $setid => $true) {
            $coursetodelete = null;
            $requestsdeleted = false;
            $thisresult = self::deletefailed;

            foreach ($this->setindex[$setid] as $key => $course) {
                if ($coursetodelete === null 
                        || $course['courseid'] != $coursetodelete) {
                    $coursetodelete = $course['courseid'];
                } else {
                    // Inconsistent courses
                    $coursetodelete = false;
                }
            }

            if ($DB->delete_records($urc, array('setid' => $setid))) {
                $thisresult = self::deletesuccess;
                if ($coursetodelete) {
                    // Attempt to delete the courses
                    if (!delete_course($coursetodelete, false)) {
                        $thisresult = self::deletecoursefailed;
                    }
                }
            }

            $results[$setid] = $thisresult;
        }

        return $results;
    }
}

// EoF
