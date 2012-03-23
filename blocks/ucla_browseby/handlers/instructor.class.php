<?php

class instructor_handler extends browseby_handler {
    function get_params() {
        return array('alpha');
    }

    static function combined_select_sql_helper() {
        ucla_require_db_helper();

        $sql = "
                {ucla_browseall_instrinfo}
        ";

        if (get_config('block_ucla_browseby', 'use_local_courses')) {
            $sql = "(
                SELECT
                    uid,
                    term,
                    srs,
                    firstname, 
                    lastname,
                    profcode
                FROM $sql
                UNION
                    SELECT
                        us.idnumber,
                        term,
                        srs,
                        firstname,
                        lastname,
                        NULL
                    FROM {user} us
            " . db_helper::join_role_assignments_request_classes_sql . "
                    INNER JOIN {ucla_browseall_classinfo} ubci
                        USING(term, srs)
            )";
        }

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
                CONCAT(uid, '-', term, '-', srs) AS rsid,
                uid,
                term,
                srs,
                firstname,
                lastname
            FROM " . self::combined_select_sql_helper() . " users
            ORDER BY lastname
        ";

        $users = $this->get_records_sql($sql);
        
        if (empty($users)) {
            return array(false, false);
        }

        $valid_terms = array();

        // Decide which users to have the ability to display in the 
        // chart
        foreach ($users as $k => $user) {
            // Figure out which terms to display
            if (isset($user->term)) {
                $valid_terms[$user->term] = $user->term;
            }

            if ($user->term != $term) {
                unset($users[$k]);
                continue;
            }

            $user->fullname = fullname($user);
            $lnletter = strtoupper(substr($user->lastname, 0, 1));

            // Save their last name to use later
            $lastnamefl[$lnletter] = true;
            if ($letter !== null && $lnletter != $letter) {
                unset($users[$k]);
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
        if (empty($users)) {
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
                $table = $this->list_builder_helper($users, 'uid',
                    'fullname', 'course', 'uid', $term);

                $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                    $table);
            }
        }

        return array($t, $s);
    }
}
