<?php

class instructor_handler extends browseby_handler {
    function get_params() {
        return array('alpha');
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
            } else {
                $a = new stdclass();
                $a->letter = $letter;
                $a->term = $prettyterm;

                $t = get_string('instructorswithterm', 
                    'block_ucla_browseby', $a);
            }

            // The breadcrumb logic is kind of disorganized
            $urlobj = clone($PAGE->url);
            $urlobj->remove_params('alpha');
            $PAGE->navbar->add(get_string('instructorsall', 
                'block_ucla_browseby'), $urlobj);
        }

        if (!isset($t)) {
            $t = get_string('instructorsall', 'block_ucla_browseby');
        }

        $where = 'u.idnumber <> \'\'';
        $param = null;

        ucla_require_db_helper();
        $users = db_helper::get_users_select(
            $where, $param, 'u.id'
        );
        
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
        foreach ($lastnamefl as $letter => $exists) {
            if ($exists) {
                $urlobj = clone($PAGE->url);
                $urlobj->params(array('alpha' => strtolower($letter)));
                $content = html_writer::link($urlobj, ucwords($letter));
            } else {
                $content = html_writer::tag('span',
                    $letter, array('class' => 'dimmed_text'));
            }

            $lettertable[$letter] = $content;
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

            return array($t, $s);
        }

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $lettertable, 0, 1, 'flattened-list');

        $table = $this->list_builder_helper($users, 'user_id',
            'fullname', 'course', 'inst', $term);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $table);

        return array($t, $s);
    }
}
