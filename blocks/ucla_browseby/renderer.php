<?php

/**
 *  To be honest, i don't know why i called it "block_" ucla_browseby_renderer
 **/
class block_ucla_browseby_renderer {
    const browsebytableid = 'browsebycourseslist';

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
            $ringer = count($lists) - 1;
            foreach ($lists as $list) {
                $s .= html_writer::tag('ul', $list, array(
                    'class' => 'list' . $ringer . " $customclass"
                ));
            }
        }

        return html_writer::tag('div', $s, array('class' => 'browsebylist'));
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
        $disptable->id = self::browsebytableid;
        $disptable->head = self::ucla_browseby_course_list_headers();

        $data = array();
        if (!empty($courses)) {
            foreach ($courses as $course) {
                if (!empty($course->nonlinkdispname)) {
                    $courselink = $course->nonlinkdispname . ' '
                        . html_writer::link(new moodle_url(
                            $course->url), $course->dispname);
                } else {
                    $courselink = ucla_html_writer::external_link(
                        new moodle_url($course->url), $course->dispname);
                }


                $data[] = array($courselink, $course->instructors, 
                    $course->fullname);
                
                $disptable->data = $data;                
            }
        } else {
            $cell = new html_table_cell(get_string('noresults', 'admin'));
            $cell->colspan = 3;
            $cell->style = 'text-align: center';
            $row = new html_table_row(array($cell));            
            $disptable->data[] = $row;
        }
        
        return $disptable;
    }

    static function ucla_browseby_course_list_headers() {
        $headelements = array('course', 'instructors', 'coursetitle');
        $headstrs = array();

        foreach ($headelements as $headelement) {
            $headstrs[] = get_string($headelement, 
                'block_ucla_browseby');
        }

        return $headstrs;
    }

    /**
     *  Convenience function, use this.
     *  @param  $terms Array() Possible terms to select from, in form
     *     YYT (i.e. 11F)
     *  @param  $default string default one to pick
     **/
    static function make_terms_selector($terms, $default=false) {        
        list($w, $p) = self::render_terms_restricted_helper($terms);
        return self::render_terms_selector($default, $w, $p);
    }
    
    /**
     *  Another convenience function.
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

        $contents = get_string('term', 'local_ucla') . ':' 
            . $OUTPUT->render(self::terms_selector($defaultterm, 
                $restrictor_where, $restrictor_params));               
        return html_writer::tag('div', $contents, array('class' => 'term_selector'));
    }

    /**
     *  Builds a automatic-redirecting drop down menu, populated
     *  with terms. Returns a thing you $OUTPUT->render()
     **/
    static function terms_selector($defaultterm=false, 
            $restrictor_where=false, $restrictor_params=null) {
        global $DB, $PAGE;

        if (!empty($restrictor_where)) {
            $termobjs = $DB->get_records_select('ucla_reg_classinfo', 
                $restrictor_where, $restrictor_params, '', 'DISTINCT term');
        } else {
            $termobjs = $DB->get_records('ucla_reg_classinfo', null, '',
                'DISTINCT term');
        }

        foreach ($termobjs as $term) {
            $terms[] = $term->term;
        }

        $terms = terms_arr_sort($terms);
        $terms = array_reverse($terms);
                
        // CCLE-3141 - Prepare for post M2 deployment
        $terms[] = '12W';   // make this say Winter 2012 or earlier
        
        $urls = array();
        $page = $PAGE->url;
        $default = '';
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);

            if ($term == '12W') {
                $urls[$url] = 'Winter 2012 or earlier'; // yes, going to hardcode this...    
            } else {
                $urls[$url] = ucla_term_to_text($term);            
            }

            if ($defaultterm !== false && $defaultterm == $term) {
                $default = $url;
            }
        }
        
        $selects = new url_select($urls, $default);

        return $selects;
    }
}

