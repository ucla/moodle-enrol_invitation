<?php

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');

/**
 *  To be honest, i don't know why i called it "block_" ucla_browseby_renderer
 **/
class block_ucla_browseby_renderer extends block_navigation_renderer {
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
        global $CFG;
        
        $disptable = new html_table();
        $disptable->id = self::browsebytableid;
        $disptable->head = self::ucla_browseby_course_list_headers();

        $data = array();

        // once a ugrad or grad course is found, then print out an anchor tag
        $found_ugrad = false;
        $found_grad = false;
        
        if (!empty($courses)) {
            $public_syllabus_string = get_string('public_syllabus', 'block_ucla_browseby');
            $private_syllabus_string = get_string('private_syllabus', 'block_ucla_browseby');

            foreach ($courses as $termsrs => $course) {
                if (!empty($course->nonlinkdispname)) {
                    $courselink = $course->nonlinkdispname . ' '
                        . html_writer::link(new moodle_url(
                            $course->url), $course->dispname);
                } else {
                    $courselink = ucla_html_writer::external_link(
                        new moodle_url($course->url), $course->dispname);
                }

                if (!$found_ugrad && intval($course->coursenum) < 200) {
                    $anchor = html_writer::tag('a', '', array('name' => 'ugrad'));
                    $courselink = $anchor . $courselink;
                    $found_ugrad = true;
                }
                if (!$found_grad && intval($course->coursenum) >= 200) {
                    $anchor = html_writer::tag('a', '', array('name' => 'grad'));
                    $courselink = $anchor . $courselink;
                    $found_grad = true;
                }

                // Generate icon for course syllabus link                
                $syllabus = '';
                if (isset($course->has_public_syllabus) || isset($course->has_private_syllabus)) {
                    $syllabus = html_writer::start_tag('a', 
                        array('href' => $CFG->wwwroot . '/local/ucla_syllabus/index.php?id=' . $course->courseid));

                    if (isset($course->has_public_syllabus)) {
                        $syllabus .= html_writer::tag('img', '',
                            array('src' => $CFG->wwwroot . '/local/ucla_syllabus/pix/public.png',
                                'alt' => $public_syllabus_string,
                                'title' => $public_syllabus_string
                                )
                            );
                    } else {
                        $syllabus .= html_writer::tag('img', '',
                            array('src' => $CFG->wwwroot . '/local/ucla_syllabus/pix/private.png',
                                'alt' => $private_syllabus_string,
                                'title' => $private_syllabus_string
                                )
                            );
                    }
                    $syllabus .= html_writer::end_tag('a');
                }

                $data[] = array($syllabus, $courselink,
                    $course->instructors, $course->fullname);
            }
            $disptable->data = $data;
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
        $headstrs = array(0 => ''); // include empty column for syllabus icon

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

        // CCLE-3526: Dynamic selection of archive server notice
        $precutoffterm = term_get_prev(
            get_config('local_ucla', 'remotetermcutoff')
        );

        if (!$precutoffterm) {
            $precutoffterm = '12W';
        }
                
        // CCLE-3141 - Prepare for post M2 deployment
        $terms[] = $precutoffterm;   // make this say Winter 2012 or earlier
        
        $urls = array();
        $page = $PAGE->url;
        $default = '';
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);

            if (term_cmp_fn($term, $precutoffterm) < 0) {
                // We have an option for cut-off term and earlier,
                // so no point in displaying terms before the cut-off
                continue;
            } else if ($term == $precutoffterm) {
                $urls[$url] = ucla_term_to_text($term) . ' or earlier'; // yes, going to hardcode this...    
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
    
    /**
     *  Calls block_navigation_renderer's protected function.
     **/
    public function navigation_node($i, $a=array(), $e=null, 
            array $o=array(), $d=1) {
        return parent::navigation_node($i, $a, $e, $o, $d);
    }

}

