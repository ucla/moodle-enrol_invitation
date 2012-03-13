<?php

class block_ucla_browseby_renderer {
    static function ucla_custom_list_render($data, $min=8, $split=2, 
                                            $customclass='') {
        $s = '';

        $lists = array();
        $cdata = count($data);
        if ($cdata < $min) {
            $lists[] = self::ucla_custom_list_render_helper($data);
        } else {
            $splitted = ceil($cdata / $split);
            
            for ($i = 0; $i < $split; $i++) {
                if (count($data) < $splitted) {
                    $smaller = $data;
                } else {
                    $smaller = array_splice($data, 0, $splitted);
                }

                $lists[] = self::ucla_custom_list_render_helper($smaller);
            }
        }

        if (!empty($lists)) {
            $ringer = 0;
            foreach ($lists as $list) {
                $s .= html_writer::tag('ul', $list, array(
                    'class' => 'list' . $ringer . " $customclass"
                ));
            }
        }

        return $s;
    }

    static function ucla_custom_list_render_helper($data) {
        $s = '';

        foreach ($data as $d) {
            $s .= html_writer::tag('li', $d);
        }

        return $s;
    }

    /**
     *  Renders the giant list of courses.
     *  @param $courses 
     *      Array (
     *          Object {
     *              url => url of course
     *              dispname => displayed for link
     *              instructors => Array ( Instructor names )
     *              fullname => the fullname of the course
     *          }
     *      )
     **/
    static function ucla_browseby_courses_list($courses) {
        $disptable = new html_table();
        $disptable->id = 'browseby_courses_list';

        $data = array();

        foreach ($courses as $course) {
            $courselink = html_writer::link(new moodle_url($course->url),
                $course->dispname);

            $instrstr = 'N / A';
            if (!empty($course->instructors)) {
                $instrstr = implode(' / ', $course->instructors);
            }

            $data[] = array($courselink, $instrstr, $course->fullname);
        }

        $disptable->data = $data;

        return $disptable;
    }
    
    /**
     *  Another helper...move to renderer?
     **/
    static function render_terms_restricted_helper($restrict_terms=false) {
        global $DB;

        list($ti, $tp) = $DB->get_in_or_equal($restrict_terms);
        $tw = 'term ' . $ti;

        return array($tw, $tp);
    }

    /**
     *  Convenience function for drawing a terms-drop down
     **/
    static function render_terms_selector($defaultterm=false, 
                                   $restrictor_where=false,
                                   $restrictor_params=null) {
        global $OUTPUT;

        return get_string('term', 'local_ucla') . ':' 
            . $OUTPUT->render(self::terms_selector($defaultterm, 
                $restrictor_where, $restrictor_params));
    }

    /**
     *  Builds a automatic-redirecting drop down menu, populated
     *  with terms.
     **/
    static function terms_selector($defaultterm=false, 
            $restrictor_where=false, $restrictor_params=null) {
        global $DB, $PAGE;
       
        if (!empty($restrictor_where)) {
            $terms = $DB->get_records_select('ucla_request_classes', 
                $restrictor_where, $restrictor_params, '', 'DISTINCT term');
        } else {
            $terms = $DB->get_records('ucla_request_classes', null, '',
                'DISTINCT term');
        }

        $urls = array();
        $page = $PAGE->url;
        $default = '';
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $term = $term->term;
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);

            $urls[$url] = ucla_term_to_text($term);

            if ($defaultterm !== false && $defaultterm == $term) {
                $default = $url;
            }
        }

        $selects = new url_select($urls, $default, '');

        return $selects;
    }
}

class ucla_html_table extends html_table {
    // This is just an empty shell, the real junk is in the renderer...
}


