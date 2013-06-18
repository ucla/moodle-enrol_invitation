<?php
/**
 *  Shared UCLA-written for cron-synching functions.
 **/
class ucla_reg_classinfo_cron {

    const table = 'ucla_reg_classinfo';

    static function enrolstat_translate($char) {
        $codes = array(
            'X' => 'Cancelled',
            'O' => 'Opened',
            'C' => 'Closed',
            'W' => 'Waitlisted',
            'H' => 'Hold'
        );

        if (!isset($codes[$char])) {
            return 'Unknown Enrollment Code';
        }

        return $codes[$char];
    }

    /**
     * Compares old and new entries from Registrar. An update is really needed
     * only if the following fields are different:
     *  - acttype
     *  - coursetitle
     *  - sectiontitle
     *  - enrolstat
     *  - url
     *  - crs_desc (course description)
     *  - crs_summary (class description)
     *
     * @param object $old   Database object.
     * @param array $new    Note, $new comes in as an array, not object.
     * @return boolean      Returns true if records needs to be updated,
     *                      otherwise returns false.
     */
    function needs_updating($old, $new) {
        $checkfields = array('acttype', 'coursetitle', 'sectiontitle',
            'enrolstat', 'url', 'crs_desc', 'crs_summary');

        foreach ($checkfields as $field) {
            if ($old->$field != $new[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get courses from the ucla_request_classes table for a given term. Query 
     * the registrar for those courses. 
     * 
     * From the records that are returned from the registrar then insert or 
     * update records in the ucla_reg_classinfo table. 
     * 
     * Then for the courses that didn't have any data returned to them from
     * the registrar, then mark those courses as cancelled in the 
     * ucla_reg_classinfo table.
     * 
     * @global moodle_database $DB
     * @param array $terms
     * @return boolean 
     */
    function run($terms) {
        global $DB;

        if (empty($terms)) {
            return true;
        }
        
        echo "\n";

        $reg = registrar_query::get_registrar_query('ccle_getclasses');

        // Get courses from our request table
        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;

        // TODO use recordset?
        $records = $DB->get_records_select('ucla_request_classes',
            $where, $params);

        echo "Got " . count($records) . " requests to update in "
            . implode(', ', $terms) . ".\n";

        if (empty($records)) {
            return true;
        }

        // Get the data
        $regs = array();
        $not_found_at_registrar = array();  // for cancelled courses later

        $t = microtime(true);
        foreach ($records as $request) {
            // We can just put in $req, except it needs to be an array.
            $reginfo = $reg->retrieve_registrar_info(
                    array(
                        'term' => $request->term,
                        'srs' => $request->srs
                    )
                );
            if (!$reginfo) {
                echo "No data for {$request->term} {$request->srs}\n";
                $not_found_at_registrar[] =
                        array('term' => $request->term, 'srs' => $request->srs);
            } else {
                $regs[] = reset($reginfo);
            }
        }

        if (empty($regs)) {
            debugging('ERROR: empty regs for ucla_reg_classinfo_cron');
            return true;
        }

        $el = microtime(true) - $t;
        $tpe = $el / count($records);
        echo "Took $tpe per element.\n";

        echo "Got " . count($regs) . " requests from Registrar.\n";

        $termsrses = array();
        $sqls = array();
        $params = array();

        // We're going to index by the results of make_idnumber
        $regind = array();
        foreach ($regs as $rege) {
            // We're going to see which entries already exist in our
            // destination table
            $sql = 'term = ? AND srs = ?';
            $param = array($rege['term'], $rege['srs']);

            $sqls[] = $sql;
            $params = array_merge($params, $param);

            $regind[make_idnumber($rege)] = $rege;
        }

        $where = implode(' OR ', $sqls);

        // Get entries from our destination table to check whether to
        // insert or to update
        $records = $DB->get_records_select(self::table, $where, $params);

        /* create array in following format:
         * [<term>-<srs>] => Object (<ucla_reg_classinfo_entry>)
         */
        $reind = array();
        foreach ($records as $record) {
            $reind[make_idnumber($record)] = $record;
        }

        // Updated
        $uc = 0; 
        // No change needed
        $nc = 0;
        // Inserted
        $ic = 0;

        // Update/insert our data
        foreach ($regind as $indk => $rege) {
            if (isset($reind[$indk])) {
                // Exists in the get_records_select() we called earlier
                $rege['id'] = $reind[$indk]->id;
                if (self::needs_updating($reind[$indk], $rege)) {
                    $DB->update_record(self::table, $rege);
                    $uc++;
                } else {
                    $nc++;
                }
            } else {
                $DB->insert_record(self::table, $rege);
                $ic++;
            }
        }
        
        // mark courses that the registrar didn't have data for as "cancelled"
        $num_not_found = 0;
        if (!empty($not_found_at_registrar)) {
            foreach ($not_found_at_registrar as $term_srs) {
                // try to update entry in ucla_reg_classinfo (if exists) and
                // mark it as cancelled
                $DB->set_field('ucla_reg_classinfo', 'enrolstat', 'X', $term_srs);
                ++$num_not_found;
            }
        }
        
        echo "\nUpdated: $uc . Inserted: $ic . Not found at registrar: "
            . "$num_not_found . No update needed: $nc\n";

        return true;
    }
}

/**
 *  Fills the subject area cron table.
 **/
class ucla_reg_subjectarea_cron {
    function run($terms) {
        global $DB;

        $ttte = 'ucla_reg_subjectarea';

        if (empty($terms)) {
            debugging('NOTICE: empty $terms for ucla_reg_subjectarea_cron');
            return true;    // maybe not an error
        }

        $reg = registrar_query::get_registrar_query('cis_subjectareagetall');
        if (!$reg) {
            echo "No registrar module found.";
        }
  
        $subjareas = array();
        foreach ($terms as $term) {
            try {            
                $regdata = 
                    $reg->retrieve_registrar_info(
                            array('term' => $term)
                        );
            } catch(Exception $e) {
                // mostly likely couldn't connect to registrar
                mtrace($e->getMessage());
                return false;
            }                      

            if ($regdata) {
                $subjareas = array_merge($subjareas, $regdata);
            }
        }

        if (empty($subjareas)) {
            debugging('ERROR: empty $subjareas for ucla_reg_subjectarea_cron');
            return false;   // most likely an error
        }        
        
        $checkers = array();
        foreach ($subjareas as $k => $subjarea) {
            $newrec = new stdClass();

            $t =& $subjareas[$k];
            $t = array_change_key_case($subjarea, CASE_LOWER);
            $t['modified'] = time();

            $sa_text = $t['subjarea'];
            $checkers[] = $sa_text;
        }

        list($sql_in, $params) = $DB->get_in_or_equal($checkers);
        $sql_where = 'subjarea ' . $sql_in;

        $selected = $DB->get_records_select($ttte, $sql_where, $params,
            '', 'TRIM(subjarea), id');

        $newsa = 0;
        $updsa = 0;

        $amdebugging = debugging();

        foreach ($subjareas as $sa) {
            $sa_text = $sa['subjarea'];
            if (empty($selected[$sa_text])) {
                $DB->insert_record($ttte, $sa);

                $newsa ++;
            } else {
                $sa['id'] = $selected[$sa_text]->id;
                $DB->update_record($ttte, $sa);
                $updsa ++;
            }

        }

        echo "New: $newsa. Updated: $updsa.\n";

        return true;
    }
}

// CCLE-3739 - Do not allow "UCLA registrar" enrollment plugin to be hidden 
class ucla_reg_enrolment_plugin_cron {
    public function run($terms = null) {
        global $DB;
        
        // Find courses whose registrar (database) enrolment has been disabled
        // @note 'enrol.status' is not indexed, but it's a boolean value
        $records = $DB->get_records('enrol', array('enrol' => 'database', 
            'status' => ENROL_INSTANCE_DISABLED));
        
        // Now enable them the Moodle way
        foreach($records as $r) {
            self::update_plugin($r->courseid, $r->id);
        }
    }
    
    static public function update_plugin($courseid, $instanceid) {
        $instances = enrol_get_instances($courseid, false);
        $plugins   = enrol_get_plugins(false);

        // Straight out of enrol/instances.php
        $instance = $instances[$instanceid];
        $plugin = $plugins[$instance->enrol];
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
        }
    }
}

// EoF
