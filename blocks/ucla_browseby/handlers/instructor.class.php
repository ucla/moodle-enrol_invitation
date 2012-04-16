<?php

class instructor_handler extends browseby_handler {
    function get_params() {
        return array('alpha');
    }

    /**
     *  Builds a sub-table that combines all available users, limited
     *  by local machine.
     **/
    static function combined_select_sql_helper() {
        global $CFG;

        ucla_require_db_helper();

        $sql = "(
            SELECT
                us.id       AS userid,
                us.idnumber AS idnumber,
                us.firstname,
                us.lastname,
                term,
                srs,
                profcode
            FROM {user} us
            INNER JOIN {ucla_browseall_instrinfo} ubii
                ON ubii.uid = us.idnumber
        )";

        return $sql;
    }

    static function alter_navbar() {
        global $PAGE;

        // The breadcrumb logic is kind of disorganized
        $urlobj = clone($PAGE->url);
        $urlobj->remove_params('alpha');
        $urlobj->params(array('type' => 'instructor'));
        $PAGE->navbar->add(get_string('instructorsall', 
                'block_ucla_browseby'), $urlobj);
    }

    /**
     *  Fetches a list of instructors with an alphabetized index.
     **/
    function handle($args) {
        global $PAGE;

        $s = '';

        $term = false;
        if (isset($args['term'])) {
            $term = $args['term'];
            $prettyterm = ucla_term_to_text($term);
        }

        // Used for restricting choices
        $letter = null;

        // These are the letters that are available for filtering
        $lastnamefl = array();
        for ($l = 'A'; $l <= 'Z' && strlen($l) == 1; $l++) {
            // Do we need to strtoupper, for super duper safety?
            $lastnamefl[$l] = false;
        }

        // Figure out what letters we're displaying
        if (isset($args['alpha'])) {
            $rawletter = $args['alpha'];

            $letter = strtoupper($rawletter);

            if ($term) {
                $t = get_string('instructorswith', 'block_ucla_browseby', 
                    $letter);
            }

            self::alter_navbar();
        }

        if (!isset($t)) {
            $t = get_string('instructorsall', 'block_ucla_browseby');
        }

        ucla_require_db_helper();

        // Show all users form local and browseall tables
        $sql = "
            SELECT
                CONCAT(
                    ubi.userid, '-', ubi.term, '-', ubi.srs
                ) AS rsid,
                ubi.userid,
                ubi.term,
                ubi.srs,
                ubi.firstname,
                ubi.lastname,
                ubci.catlg_no AS course_code,
                ubci.activitytype,
                ubci.subjarea
            FROM " . self::combined_select_sql_helper() . " ubi
            INNER JOIN {ucla_browseall_classinfo} ubci 
                ON  ubi.term = ubci.term 
                AND ubi.srs = ubci.srs
            ORDER BY ubi.lastname, ubi.firstname
        ";

        $users = $this->get_records_sql($sql);
        
        if (empty($users)) {
            return array(false, false);
        }

        $valid_terms = array();

        // Decide which users to have the ability to display in the 
        // chart
        // TODO It might be more efficient to just add another query
        $coursepcs = array();
        foreach ($users as $k => $user) {
            if ($this->ignore_course($user)) {
                unset($users[$k]);
                continue;
            }

            if (isset($user->profcode)) {
                $pc = $user->profcode;
                $coursepcs[$user->srs][$pc] = $pc;
            }
        }

        $rolecaps = $this->get_roles_with_capability('moodle/course:update');

        $no_display_hack = 0;
        foreach ($users as $k => $user) {
            if (isset($user->profcode) 
                    && !isset($rolecaps[$this->role_mapping(
                        intval($user->profcode), 
                        $coursepcs[$user->srs], 
                        $user->subjarea
                    )])) {
                $users[$k]->no_display = true;
                $no_display_hack++;
                continue;
            }

            $user->fullname = fullname($user);
            $lnletter = strtoupper(substr($user->lastname, 0, 1));
            $lettermatches = ($letter !== null && $lnletter == $letter);

            // If a letter is selected, then we need to limit the number
            // of terms selectable to prevent dead-end results
            // TODO optimize with another query?
            $uterm = $user->term;
            if ($letter !== null) {
                if ($lnletter == $letter) {
                    $valid_terms[$uterm] = $uterm;
                }
            } else {
                // Without a letter selected, allow all terms to be selected
                $valid_terms[$uterm] = $uterm;
            }

            // If a term is selected and we need to limit instructor last 
            // name letter choices
            if ($uterm == $term) {
                $lastnamefl[$lnletter] = true;

                if ($letter !== null && $lnletter != $letter) {
                    unset($users[$k]);
                }
            } else {
                unset($users[$k]);
                continue;
            }
        }

        $lettertable = array();
        foreach ($lastnamefl as $lnletter => $exists) {
            if ($exists) {
                $urlobj = clone($PAGE->url);
                $urlobj->params(array('alpha' => strtolower($lnletter)));
                $content = html_writer::link($urlobj, ucwords($lnletter));
            } else {
                $content = html_writer::tag('span',
                    $lnletter, array('class' => 'dimmed_text'));
            }

            $lettertable[$lnletter] = $content;
        }

        list($w, $p) = $this->render_terms_restricted_helper($valid_terms);

        $s .= block_ucla_browseby_renderer::render_terms_selector($term, 
            $w, $p);
        
        // This case can be reached if the current term has no instructors.
        if (empty($users) || count($users) == $no_display_hack) {
            if ($term) {
                $s .= get_string('noinstructorsterm', 'block_ucla_browseby',
                    $prettyterm);
            } else {
                $s .= get_string('noinstructors', 'block_ucla_browseby');
            }
        } else {
            if ($letter == null) {
                $s .= html_writer::tag('div', get_string(
                    'selectinstructorletter', 'block_ucla_browseby'));
            }

            $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                $lettertable, 0, 1, 'flattened-list');

            if ($letter !== null) {
                $table = $this->list_builder_helper($users, 'userid',
                    'fullname', 'course', 'user', $term);

                $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                    $table);
            }
        }

        return array($t, $s);
    }
}
