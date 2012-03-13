<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');
require_once($CFG->dirroot . '/' . $CFG->admin 
    . '/tool/uclacoursecreator/uclacoursecreator.class.php');

class block_ucla_browseby extends block_list {
    // These are all the possible arguments handled by types...
    function get_possible_arguments() {
        return array(
            'subjarea',
            'division',
            'alpha',
            'inst'
        );
    }

    var $termslist = array();
    
    const browseall_sql_helper =  "
        SELECT 
        CONCAT(ubc.term, ubc.srs, ubci.uid) AS 'recordsetid',
        ubc.section AS 'sectnum',
        ubc.course AS 'coursenum',
        ubc.activitytype,
        ubc.subjarea as 'subj_area',
        ubc.url,
        ubc.term,
        ubc.srs,
        ubc.coursetitlelong AS course_title,
        ubc.sectiontitle AS section_title,
        ubci.uid,
        COALESCE(user.firstname, ubci.firstname) AS firstname,
        COALESCE(user.lastname, ubci.lastname) AS lastname,
        user.url AS userlink,
        urc.courseid
    ";

    function init() {
        $this->title = get_string('displayname', 'block_ucla_browseby');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     *  This has a per-session cache, so that we don't have
     *  to calculate a same division_handler() within the same session.
     **/
    function handle_types($type, $data) {
        $hfn = $type . '_handler';

        if (method_exists($this, $hfn)) {
            $r = $this->{$hfn}($data);
        } else {
            $r = array(false, false);
        }

        return $r;
    }

    function course_handler($args) {
        global $OUTPUT;

        $subjarea = null;
        $instructor = null;

        $t = false;
        $s = '';

        $terms_select_where = '';
        $terms_select_param = null;

        // This is the parameters for one of the two possible query
        // types in this function...
        $params = array();

        $fullcourselist = array();

        if (isset($args['term'])) {
            $termwhere = ' AND ubc.term = :term ';
            $param['term'] = $args['term'];
        } else {
            $termwhere = '';
        }

        if (isset($args['subjarea'])) {
            $subjarea = $args['subjarea'];

            $terms_select_where = 'department = ?';
            $terms_select_param = array($subjarea);

            $subjareapretty = to_display_case(
                $this->get_pretty_subjarea($subjarea));

            $t = get_string('coursesinsubjarea', 'block_ucla_browseby',
                $subjareapretty);

            // Get all courses in this subject area but from 
            // our browseall tables
            $sql = self::browseall_sql_helper . "
                FROM {ucla_browseall_classinfo} ubc
                LEFT JOIN {ucla_browseall_instrinfo} ubci
                    USING(term, srs)
                LEFT JOIN {ucla_request_classes} urc
                    USING(term, srs)
                LEFT JOIN {user} user
                    ON ubci.uid = user.idnumber
                WHERE ubc.subjarea = :subjarea
                $termwhere
            ";

            $param['subjarea'] = $subjarea;

            $courseslist = $this->get_records_sql($sql, $param);
        } else if (isset($args['inst'])) {
            ucla_require_db_helper();

            // This is the local-system specific instructor's courses view
            $instructor = $args['inst'];

            // Apparently, this is how moodle fetches users
            $instruser = $this->get_user($instructor);
            if (!$instruser) {
                return array(false, false);
            }

            $t = get_string('coursesbyinstr', 'block_ucla_browseby',
                fullname($instruser));

            $sql = self::browseall_sql_helper . "
                FROM {ucla_browseall_instrinfo} ubi
                LEFT JOIN {ucla_browseall_classinfo} ubc
                    USING(term, srs)
                LEFT JOIN {ucla_browseall_instrinfo} ubci
                    USING(term, srs)
                LEFT JOIN {ucla_request_classes} urc
                    USING(term, srs)
                LEFT JOIN {user} user
                    ON ubci.uid = user.idnumber
                WHERE ubi.uid = :uid
                $termwhere
            ";

            $params['uid'] = $instruser->idnumber;

            $courseslist = $this->get_records_sql($sql, $params);

            // hack to hide some terms
            $terms_avail = array();
            foreach ($courseslist as $course) {
                $tt = $course->term;
                if (isset($terms_avail[$tt])) {
                    continue;
                }

                $terms_avail[$tt] = $tt;
            }

            list($terms_select_where, $terms_select_param) =
                $this->render_terms_restricted_helper($terms_avail);
        } else {
            return array(false, false);
        }

        foreach ($courseslist as $course) {
            $k = make_idnumber($course);

            if (isset($fullcourseslist[$k])) {
                $courseobj = $fullcourseslist[$k];
                $courseobj->instructors[$course->uid] = 
                    $this->fullname($course);
            } else {
                $courseobj = new stdclass(); 
                if ($this->config['use_local_courses']
                        && isset($course->courseid)) {
                    $course->id = $course->courseid;
                    $courseobj->url = 
                        uclacoursecreator::build_course_url($course);
                } else {
                    if (isset($course->url)) {
                        $courseobj->url = $course->url;
                    } else {
                        // Display registrar?
                    }
                }

                $courseobj->dispname = ucla_make_course_title(
                    get_object_vars($course));

                // TODO make this function name less confusing
                $courseobj->fullname = 
                    uclacoursecreator::make_course_title(
                        $course->course_title, $course->section_title
                    );

                $courseobj->instructors = 
                    array($course->uid => $this->fullname($course));
            }

            $fullcourseslist[$k] = $courseobj;
        }

        $table = block_ucla_browseby_renderer::ucla_browseby_courses_list(
            $fullcourseslist);

        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $terms_select_where, $terms_select_param);

        $s .= html_writer::table($table);
        
        return array($t, $s);
    }

    /** 
     *  Displays user information, with a link if there is a provided 
     *  URL in the user table.
     *  @param $userinfo stdClass {
     *      firstname, lastname, userlink
     *  }
     **/
    function fullname($userinfo) {
        $name = fullname($userinfo);
        if (!empty($userinfo->userlink)) {
            $userurl = $userinfo->userlink;

            if (strpos($userurl, 'http://') === false 
                    && strpos($userurl, 'https://') === false) {
                $userurl = 'http://' . $userurl;
            }

            $name = html_writer::link(new moodle_url($userurl),
                $name);
        } 

        return $name;
    }

    /**
     *  Fetches a list of instructors with an alphabetized index.
     **/
    function instructor_handler($args) {
        $s = '';

        $term = false;
        if (isset($args['term'])) {
            $term = $args['term'];
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
            if ($rawletter != 'all') {
                $letter = strtoupper($rawletter);

                $t = get_string('instructorswith', 'block_ucla_browseby', 
                    $letter);

                // Add the 'All' option
                $lastnamefl['all'] = true;
            }
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

        // No users, no need to display...
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

            $user->fullname = fullname($user);
            $lnletter = strtoupper(substr($user->lastname, 0, 1));
            $lastnamefl[$lnletter] = true;
            if ($letter !== null && $lnletter != $letter) {
                unset($users[$k]);
            }
        }

        $lettertable = array();
        foreach ($lastnamefl as $letter => $exists) {
            if ($exists) {
                $content = html_writer::link(
                    new moodle_url('/blocks/ucla_browseby/view.php',
                        array('type' => 'instructor', 
                            'alpha' => strtolower($letter))),
                    ucwords($letter)

                );
            } else {
                $content = html_writer::tag('span',
                    $letter, array('class' => 'dimmed_text'));
            }

            $lettertable[$letter] = $content;
        }

        list($w, $p) = $this->render_terms_restricted_helper($valid_terms);

        $s .= block_ucla_browseby_renderer::render_terms_selector($term, 
            $w, $p);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $lettertable, 0, 1, 'flattened-list');

        $table = $this->list_builder_helper($users, 'user_id',
            'fullname', 'course', 'inst', $term);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $table);

        return array($t, $s);
    }

    /**
     *  A list of divisions.
     **/
    function division_handler($args) {
        $s = '';
        $t = get_string('division_title', 'block_ucla_browseby');

        $divisions = $this->get_divisions();

        if (empty($divisions)) {
            return array(false, false);
        } else {
            $table = $this->list_builder_helper($divisions, 'code',
                'fullname', 'subjarea', 'division');

            $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                $table);
        }

        return array($t, $s);
    }

    /**
     *  A highly-specific convenience function. That feeds into the
     *  renderer.
     **/
    function list_builder_helper($data, $keyfield, $dispfield, $type, $get, 
                                 $term=false) {
        $table = array();
        foreach ($data as $datum) {
            $k = $datum->{$keyfield};
            $queryterms = array('type' => $type, $get => $k);
            if ($term) {
                $queryterms['term'] = $term;
            }

            $table[$k] = html_writer::link(
                new moodle_url('/blocks/ucla_browseby/view.php', $queryterms),
                ucwords(strtolower($datum->{$dispfield}))
            );
        }

        return $table;
    }

    /**
     *  This view is used whenever we are displaying a list of subject areas.
     **/
    function subjarea_handler($args) {
        global $OUTPUT;

        $division = false;
        if (isset($args['division'])) {
            $division = $args['division'];
        }

        if (isset($args['term'])) {
            $term = $args['term'];
        } else {
            $term = false;
        }

        $conds = array();
        $where = '';
       
        if ($division) {
            $conds['division'] = $division;
            $where = 'WHERE urs.division = :division';
        } else {
            $division = get_string('all_subjareas', 'block_ucla_browseby');
        }

        if (empty($conds)) {
            $conds = null;
        }

        // This is the content
        $s = '';
        // This is the title
        $t = get_string('subjarea_title', 'block_ucla_browseby', 
            $division);

        // Display a list of things to help us narrow down our path to 
        // destination
        $sql = "
            SELECT DISTINCT
                CONCAT(urs.subjarea, ubc.term) AS rsid,
                urs.subjarea,
                urs.subj_area_full,
                ubc.term
            FROM {ucla_browseall_classinfo} ubc
            INNER JOIN {ucla_reg_subjectarea} urs
                ON ubc.subjarea = urs.subjarea
            $where
        ";

        $subjectareas = $this->get_records_sql($sql, $conds);

        if (empty($subjectareas)) {
            return array(false, false);
        }

        $terms = array();
        foreach ($subjectareas as $k => $subjarea) {
            // Save this query...
            $this->subjareas_pretty[$subjarea->subjarea] = 
                $subjarea->subj_area_full;

            // Figure out which terms we can include in the drop down
            $tt = $subjarea->term;
            $terms[$tt] = $tt;
            if ($term !== false && $tt != $term) {
                unset($subjectareas[$k]);
            }
        }

        list($w, $p) = $this->render_terms_restricted_helper($terms);
        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $w, $p);

        $table = $this->list_builder_helper($subjectareas, 'subjarea',
            'subj_area_full', 'course', 'subjarea', $term);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render($table);

        return array($t, $s);
    }


    /**
     *  This is called in the course where the block has been added to.
     **/
    function get_content() {
        global $CFG;
        $this->content->icons = array();

        $link_types = array(
            'subjarea', 'division', 'instructor', 'collab', 'mycourses'
        );

        foreach ($link_types as $link_type) {
            if (reset($this->handle_types($link_type, false)) !== false) {
                $this->content->items[] = html_writer::link(
                    new moodle_url(
                        $CFG->wwwroot . '/blocks/ucla_browseby/view.php',
                        array('type' => $link_type)
                    ), get_string('link_' . $link_type, 'block_ucla_browseby')
                );
            }
        }
    }

    function instance_allow_config() {
        return false;
    }

    /**
     *  Returns the applicable places that this block can be added.
     *  This block really cannot be added anywhere, so we just made a place
     *  up (hacky). If we do not do this, we will get this
     *  plugin_devective_exception.
     **/
    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => false,
            'blocks-ucla_control_panel' => false,
        );
    }

    /**
     *  Determines the terms to run the cron job for if there were no
     *  specifics provided.
     **/
    function guess_terms() {
        global $CFG;

        if (!empty($this->termslist)) {
            return;
        }

        $this->termslist = array($CFG->currentterm);
    }

    function cron() {
        $this->guess_terms();

        if (empty($this->termslist)) {
            return true;
        }

        $this->sync($this->termslist);
    }

    function sync($terms, $subjareas=null) {
        global $DB; 

        ucla_require_registrar();

        if (empty($terms)) {
            debugging('no terms specified for browseby cron');
            return true;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;

        if (!empty($subjareas)) {
            list($sqlin, $saparams) = $DB->get_in_or_equal($subjareas);
            $where .= ' AND subjarea' . $sqlin;
            $params = array_merge($params, $saparams);
        }

        $records = $DB->get_records_select('ucla_request_classes',
            $where, $params, '', 'DISTINCT CONCAT(term, department), term, '
                . 'department AS subjarea');

        if (empty($records)) {
            return true;
        }
    
        // These are all going to SHARE KEYS, like roommates, or we could
        // define a class for these data structures
        $toreg = array();
        $courseinfos = array();
        $instrinfos = array();

        foreach ($records as $record) {
            $term = $record->term;
            $subjarea = $record->subjarea;
            $thisreg = array('term' => $term, 
                'subjarea' => $subjarea);
            $toreg = array($thisreg);

            $courseinfo = $this->run_registrar_query(
                'ccle_coursegetall', $toreg);

            foreach ($courseinfo as $ci) {
                $ci['term'] = $term;
                $courseinfos[] = $ci;
            }

            $instrinfo = $this->run_registrar_query(
                'ccle_getinstrinfo', $toreg);

            foreach ($instrinfo as $ii) {
                $ii['subjarea'] = $subjarea;
                $instrinfos[] = $ii;
            }
        }

        // Save which courses need instructor informations.
        // We need to update the existing entries, and remove 
        // non-existing ones.
        $this->partial_sync_table('ucla_browseall_classinfo', $courseinfos,
            array('term', 'srs'), $where, $params);

        $this->partial_sync_table('ucla_browseall_instrinfo', $instrinfos,
            array('term', 'srs'), $where, $params);
            
        return true;
    }

    function get_pretty_subjarea($subjarea) {
        global $DB;
    
        $sa = $DB->get_record('ucla_reg_subjectarea', 
            array('subjarea' => $subjarea));

        if ($sa) {
            return $sa->subj_area_full;
        }

        return false;
    }

    /**
     *  decoupled functions
     **/
    protected function render_terms_restricted_helper($rt=false) {
        return block_ucla_browseby_renderer::render_terms_restricted_helper(
            $rt);
    }

    protected function get_divisions() {
        global $DB;

        return $DB->get_records('ucla_reg_division');
    }

    protected function get_user($userid) {
        global $DB;

        return $DB->get_record('user', array('id' => $userid));
    }

    protected function get_records_sql($sql, $params) {
        global $DB;

        return $DB->get_records_sql($sql, $params);
    }

    protected function partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere=null, $partialparams=null) {
        ucla_require_db_helper();

        return db_helper::partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere, $partialparams);
    }
   
    protected function run_registrar_query($q, $d) {
        ucla_require_registrar();

        return registrar_query::run_registrar_query($q, $d, true);
    }
}

/** eof **/
