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

    function run($terms) {
        global $DB;

        if (empty($terms)) {
            return true;
        }

        $reg = registrar_query::get_registrar_query('ccle_getclasses');

        $courses = ucla_get_courses_by_terms($terms, false);

        echo "\nGot " . count($courses) . "courses to update.\n";

        if (empty($courses)) {
            return true;
        }

        $get_from_registrar = array();
        foreach ($courses as $setid => $reqset) {
            foreach ($reqset as $reqkey => $req) {
                $get_from_registrar[] = array(
                    'term' => $req->term,
                    'srs' => $req->srs
                );
            }
        }

        $regs = $reg->retrieve_registrar_info($get_from_registrar);

        $termsrses = array();
        $sqls = array();
        $params = array();

        $regind = array();
        foreach ($regs as $rege) {
            $sql = 'term = ? AND srs = ?';
            $param = array($rege->term, $rege->srs);

            $sqls[] = $sql;
            $params = array_merge($params, $param);

            $regind[make_idnumber($rege)] = $rege;
        }

        $where = implode(' OR ', $sqls);

        $records = $DB->get_records_select(self::table, $where, $params);

        $reind = array();
        foreach ($records as $record) {
            $reind[make_idnumber($record)] = $record;
        }

        // Updated
        $uc = 0; 
        // Failed sanity check
        $fscc = 0;
        // Inserted
        $ic = 0;
        foreach ($regind as $indk => $rege) {
            if (isset($reind[$indk])) {
                $rege->id = $reind[$indk]->id;
                if (self::sanity_check($rege, $reind[$indk])) {
                    $DB->update_record(self::table, $rege);
                    $uc++;
                } else {
                    $fscc++;
                }
            } else {
                // Insert
                $DB->insert_record(self::table, $rege);
                $ic++;
            }
        }

        echo "\nUpdated: $uc . Inserted: $ic . Failed sanity: $fscc\n";

        return true;
    }

    /**
     *  Sanity checks that prevents blind updating of entries in the table.
     *
     **/
    function sanity_check($old, $new) {
        return true;
    }
}

class ucla_reg_subjectarea_cron {
    function run($terms) {
        global $DB;

        $ttte = 'ucla_reg_subjectarea';

        if (empty($terms)) {
            return true;
        }

        $reg = registrar_query::get_registrar_query('cis_subjectareagetall');
        if (!$reg) {
            echo "No registrar module found.";
        }

        $subjareas = $reg->retrieve_registrar_info($terms);

        $checkers = array();
        foreach ($subjareas as $k => $subjarea) {
            $newrec = new stdClass();

            $t =& $subjareas[$k];
            $t = array_change_key_case(get_object_vars($subjarea), CASE_LOWER);
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

// EoF
