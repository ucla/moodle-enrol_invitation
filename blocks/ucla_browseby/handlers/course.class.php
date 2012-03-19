<?php

class course_handler extends browseby_handler {
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
        ubc.ses_grp_cd AS session_group,
        ubc.session AS session_code,
        ubc.coursetitlelong AS course_title,
        ubc.sectiontitle AS section_title,
        ubc.sect_enrl_stat_cd AS enrolstat,
        ubc.catlg_no AS sect_num,
        ubci.uid,
        COALESCE(user.firstname, ubci.firstname) AS firstname,
        COALESCE(user.lastname, ubci.lastname) AS lastname,
        user.url AS userlink,
        urc.courseid
    ";

    function get_params() {
        // This uses division in breadcrumbs
        return array('subjarea', 'inst', 'division');
    }

    function handle($args) {
        global $OUTPUT, $PAGE;

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
            $term = $args['term'];
            $termwhere = ' AND ubc.term = :term ';
            $param['term'] = $args['term'];
        } else {
            $termwhere = '';
        }

        $issummer = false;
        if (is_summer_term($term)) {
            $issummer = true;
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

            $param['uid'] = $instruser->idnumber;

            $courseslist = $this->get_records_sql($sql, $param);

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

        // Takes a denormalized Array of course-instructors and
        // returns a set of courses into $fullcourseslist
        foreach ($courseslist as $course) {
            $k = make_idnumber($course);

            if (isset($fullcourseslist[$k])) {
                $courseobj = $fullcourseslist[$k];
                $courseobj->instructors[$course->uid] = 
                    $this->fullname($course);
            } else {
                $courseobj = new stdclass(); 
                $coursetitle = ucla_make_course_title(get_object_vars($course));

                if (get_config('block_ucla_browseby', 'use_local_courses')
                        && isset($course->courseid)) {
                    $course->id = $course->courseid;
                    $courseobj->url = 
                        uclacoursecreator::build_course_url($course);
                } else {
                    if (!empty($course->url)) {
                        $courseobj->url = $course->url;
                        $courseobj->dispname = $coursetitle;
                    } else {
                        $courseobj->url = $this->registrar_url(
                            $course
                        );

                        $courseobj->nonlinkdispname = $coursetitle;
                        $courseobj->dispname =  '(' . html_writer::tag(
                            'span', get_string('registrar_link', 
                                'block_ucla_browseby'),
                            array('class' => 'registrar-link')) . ')';
                    }
                }

                $cancelledmess = '';
                if (enrolstat_is_cancelled($course->enrolstat)) {
                    $cancelledmess = html_writer::tag('span', 
                        get_string('cancelled'), 
                        array('class' => 'ucla-cancelled-course')) . ' ';
                }

                // TODO make this function name less confusing
                $courseobj->fullname = $cancelledmess . 
                    uclacoursecreator::make_course_title(
                        $course->course_title, $course->section_title
                    );

                $courseobj->instructors = 
                    array($course->uid => $this->fullname($course));

                $courseobj->session_group = $course->session_group;
            }

            $fullcourseslist[$k] = $courseobj;
        }

        // Flatten out instructors for display
        foreach ($fullcourseslist as $k => $course) {
            $instrstr = '';
            if (!empty($course->instructors)) {
                $instrstr = implode(' / ', $course->instructors);
            }

            $course->instructors = $instrstr;
            $fullcourseslist[$k] = $course;
        }
        
        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $terms_select_where, $terms_select_param);

        $headelements = array('course', 'instructors', 'coursedesc');
        $headelementsdisp = array();

        foreach ($headelements as $headelement) {
            $headelementsdisp[] = get_string($headelement, 
                'block_ucla_browseby');
        }

        if ($issummer) { 
            $sessionsplits = array();
            foreach ($fullcourseslist as $k => $fullcourse) {
                $session = $fullcourse->session_group;

                if (!isset($sessionsplits[$session])) {
                    $sessionsplits[$session] = array();
                }

                unset($fullcourse->session_group);

                $sessionsplits[$session][$k] = $fullcourse;
            }

            $table = new html_table();
            
            foreach ($sessionsplits as $session => $courses) {
                $sessioncell = new html_table_cell();
                $sessioncell->text = $OUTPUT->heading(get_string(
                    'session_break', 'block_ucla_browseby', $session), 3);

                $sessioncell->colspan = '3';
                $sessionrow = new html_table_row();
                $sessionrow->cells[] = $sessioncell;
                
                $subtable = block_ucla_browseby_renderer::
                    ucla_browseby_courses_list($courses);

                $table->data[] = $sessionrow;
                $table->data = array_merge($table->data, $subtable->data);
            }

            $s .= html_writer::table($table);
        } else {
            foreach ($fullcourseslist as $k => $course) {
                unset($fullcourseslist[$k]->session_group);
            }

            $table = block_ucla_browseby_renderer::ucla_browseby_courses_list(
                $fullcourseslist);

            $s .= html_writer::table($table);
        }

        return array($t, $s);
    }
    
    /** 
     *  Poorly named convenience function. Displays user information, 
     *      with a link if there is a provided 
     *
     *  URL in the user table.
     *  @param $userinfo stdClass {
     *      firstname, lastname, userlink
     *  }
     **/
    function fullname($userinfo) {
        $name = ucla_format_name(fullname($userinfo));
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

    function registrar_url($course) {
        $page = 'http://www.registrar.ucla.edu/schedule/detselect';

        $term = $course->term;

        $issummerterm = is_summer_term($term);
        $query = '.aspx?termsel=' . $term . '&subareasel=' 
            . urlencode($course->subj_area) . '&idxcrs=' 
            . urlencode($course->sect_num);

        if ($issummerterm) {
            $page .= '_summer';
            $query .= $course->session_group;
        }

        return $page . $query;
    }
    
    protected function get_user($userid) {
        global $DB;

        return $DB->get_record('user', array('id' => $userid));
    }
}
