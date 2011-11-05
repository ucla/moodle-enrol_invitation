<?php

class ucla_reg_classinfo_cron {
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
            $t = array_change_key_case($subjarea, CASE_LOWER);
            $t['modified'] = time();

            $sa_text = $t['subjarea'];
            $checkers[] = $sa_text;
        }

        list($sql_in, $params) = $DB->get_in_or_equal($checkers);
        $sql_where = 'subjarea ' . $sql_in;

        $selected = $DB->get_records_select($ttte, $sql_where, $params,
            '', 'subjarea, id');

        $newsa = 0;
        $updsa = 0;

        $amdebugging = debugging();

        foreach ($subjareas as $sa) {
            $sa_text = $sa['subjarea'];
            if (!isset($selected[$sa_text])) {
                $DB->insert_record($ttte, $sa);
                if ($amdebugging) {
                    echo "New ";
                }

                $newsa ++;
            } else {
                $sa['id'] = $selected[$sa_text]->id;
                $DB->update_record($ttte, $sa);
                if ($amdebugging) {
                    echo "Updating ";
                }
                $updsa ++;
            }

            if ($amdebugging) {
                echo "subject area [$sa_text]\n";
            }
        }

        echo "New: $newsa. Updated: $updsa.\n";

        return true;
    }
}
