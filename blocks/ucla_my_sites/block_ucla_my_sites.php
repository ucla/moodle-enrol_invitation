<?php
/**
 * My sites block
 *
 * Based off of blocks/course_overview.
 *
 * @package   blocks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

require_once($CFG->dirroot.'/local/ucla/lib.php');

// Need this to build course titles
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacoursecreator/uclacoursecreator.class.php');

// Need this for host-course information
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacourserequestor/lib.php');

require_once($CFG->dirroot.'/blocks/ucla_browseby/handlers/browseby.class.php');
require_once($CFG->dirroot.'/blocks/ucla_browseby/handlers/course.class.php');

class block_ucla_my_sites extends block_base {
    private $cache = array();

    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_ucla_my_sites');
    }

    /**
     * block contents
     *
     * Get courses that user is currently assigned to and display them either as
     * class or collaboration sites.
     * 
     * @return object
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // NOTE: this thing currently takes the term in the get param...
        // so you may have some strange behavior if this block is not
        // in the my-home page...
        $showterm = optional_param('term', false, PARAM_RAW);
        if (!$showterm && isset($CFG->currentterm)) {
            $showterm = $CFG->currentterm;
        }

        $content = array();

        $courses = enrol_get_my_courses('id, shortname', 
            'visible DESC, sortorder ASC');
        $site = get_site();
        $course = $site; //just in case we need the old global $course hack

        if (array_key_exists($site->id,$courses)) {
            unset($courses[$site->id]);
        }

        // These are all the terms in the dropdown.
        $availableterms = array();

        // go through each course and categorize them into either class or
        // collaboration sites
        $class_sites = array(); $collaboration_sites = array();
        foreach ($courses as $c) {
            $reg_info = ucla_get_course_info($c->id);
            if (!empty($reg_info)) {
                $courseterm = false;
                foreach ($reg_info as $ri) {
                    $c->reg_info[make_idnumber($ri)] = $ri;
                    $courseterm = $ri->term;
                }

                $c->url = sprintf('%s/course/view.php?id=%d', $CFG->wwwroot,
                    $c->id);

                // We need to toss local information, or at least not 
                // display it twice
                $availableterms[$courseterm] = $courseterm;
                if ($courseterm == $showterm) {
                    $class_sites[] = $c;
                }
            } else {
                $collaboration_sites[] = $c;
            }
        }

        // Append the list of sites from our stored procedure 
        ucla_require_registrar();
     
        if (empty($USER->idnumber)) {
            $remotecourses = false;
        } else {
            $spparam = array('uid' => $USER->idnumber);
            $remotecourses = registrar_query::run_registrar_query(
                'ucla_get_user_classes', 
                array($spparam), 
                true
            );
        }

        if ($remotecourses) {
            foreach ($remotecourses as $remotecourse) {
                // Do not use this object after this, this is because
                // browseby_handler::ignore_course uses an object
                $objrc = (object) $remotecourse;
                $objrc->activitytype = $objrc->act_type;
                $objrc->course_code = $objrc->catlg_no;
                if (empty($objrc->url) 
                        && browseby_handler::ignore_course($objrc)) {
                    continue;
                }
                
                $subj_area = $remotecourse['subj_area'];
                list($term, $srs) = explode('-', 
                    $remotecourse['termsrs']);

                // Save the term
                $availableterms[$term] = $term;
                if ($term != $showterm) {
                    continue;
                }

                // We're going to format this object to return
                // something similar to what locally-existing courses
                // return
                $rclass = new stdclass();
                $rclass->url = $remotecourse['url'];
                $rclass->fullname = $remotecourse['course_title'];

                $rreg_info = new stdclass();
                $rreg_info->subj_area = $subj_area;
                $rreg_info->acttype = $remotecourse['act_type'];
                $rreg_info->coursenum = trim($remotecourse['catlg_no'], '0');
                $rreg_info->sectnum = trim($remotecourse['sect_no'], '0');
                $rreg_info->term = $term;
                $rreg_info->srs = $srs;
                $rreg_info->session_group = $remotecourse['session_group'];
                $rreg_info->course_code = $remotecourse['catlg_no'];
                $rreg_info->hostcourse = 1;

                $rclass->reg_info = array($rreg_info);

                $rclass->role = get_moodlerole($remotecourse['role'],
                    $subj_area);

                // If this particular course already exists locally, then there
                // is no real need to add another copy of it to the list of
                // my sites
                $key = make_idnumber($rreg_info);
                $localexists = false;
                foreach ($class_sites as $k => $class_site) {
                    foreach ($class_site->reg_info as $reginfo) {
                        if ($key == make_idnumber($reginfo)) {
                            $class_sites[$k]->role = $rclass->role;

                            $localexists = true;
                        }
                    }
                }

                if (!$localexists) {
                    $class_sites[] = $rclass;
                }
            }
        }

        // We want to sort things, so that it appears classy yo
        usort($class_sites, array(get_class(), 'registrar_course_sort'));

        // Now we need to handle all the terms
        $termoptstr = '';
        if (!empty($availableterms)) {
            // Leaves them descending
            $availableterms = terms_arr_sort($availableterms);

            $termoptstr = get_string('term', 'local_ucla') . ': '
                    . $OUTPUT->render(self::make_terms_selector(
                        $availableterms, $showterm));
        } else {
            $noclasssitesoverride = 'noclasssitesatall';
        }

        $termoptstr = html_writer::tag('div', $termoptstr,
            array('class' => 'termselector'));

        // In order to translate values returned by get_moodlerole
        $allroles = get_all_roles();

        // print class sites
        $content[] = html_writer::tag('h3', 
                get_string('classsites', 'block_ucla_my_sites'), 
                    array('class' => 'mysitesdivider')) . $termoptstr;
        if (empty($class_sites)) {
            if (!isset($noclasssitesoverride)) {
                $ncsstr = 'noclasssites';
            } else {
                $ncsstr = $noclasssitesoverride;
            }

            $content[] = html_writer::tag('p', get_string($ncsstr, 
                    'block_ucla_my_sites', ucla_term_to_text($showterm)));
        } else {
            $t = new html_table();
            $t->head = array(get_string('classsitesnamecol', 
                'block_ucla_my_sites'), get_string('rolescol', 
                'block_ucla_my_sites'));
            foreach ($class_sites as $class) {
                // build class title in following format:
                // <subject area> <cat_num>, <activity_type e.g. Lec, Sem> <sec_num> (<term name e.g. Winter 2012>): <full name>
                
                // there might be multiple reg_info records for cross-listed 
                // courses
                $class_title = ''; $first_entry = true;
                foreach ($class->reg_info as $reg_info) {
                    $first_entry ? $first_entry = false : $class_title .= '/';
                    $class_title .= sprintf('%s %s, %s %s', 
                            $reg_info->subj_area,
                            $reg_info->coursenum,
                            $reg_info->acttype,
                            $reg_info->sectnum);
                }
                
                $reg_info = reset($class->reg_info);
                $title = sprintf('%s (%s): %s', 
                        $class_title,
                        ucla_term_to_text($reg_info->term, 
                            $reg_info->session_group), $class->fullname);

                // add link
                if (!empty($class->url)) {
                    $class_link = ucla_html_writer::link(
                        new moodle_url($class->url), 
                        $title);
                } else {
                    // Courses without urls should not have information
                    // stating that they are crosslisted
                    if (count($class->reg_info) != 1) {
                        debugging('strangeness!');
                    } else {
                        // THis external link generation mechanism should
                        // be pulled outside this block
                        $class_link = "$title " . html_writer::link(
                            new moodle_url(
                                course_handler::registrar_url(reset(
                                    $class->reg_info))
                            ),
                            '(' . html_writer::tag(
                                    'span', 
                                    get_string('registrar_link', 
                                        'block_ucla_browseby'),
                                    array('class' => 'registrar-link')
                                ) . ')'
                        );
                    }
                }
                
                // get user's role
                if (empty($class->id) && !empty($class->role)) {
                    $roles = $allroles[$class->role]->name;
                } else {
                    $roles = get_user_roles_in_course($USER->id, $class->id);
                }

                // remove links from role string
                $roles = strip_tags($roles);
                
                $t->data[] = array($class_link, $roles);
            }

            $content[] = html_writer::table($t);
        }
        
        // print collaboration sites (if any)
        if (!empty($collaboration_sites)) {
            $content[] = html_writer::tag('h3', get_string('collaborationsites', 
                    'block_ucla_my_sites'), array('class' => 'mysitesdivider'));
            
            $t = new html_table();
            $t->head = array(get_string('collaborationsitesnamecol', 
                'block_ucla_my_sites'), get_string('rolescol', 
                'block_ucla_my_sites'));      
            
            foreach ($collaboration_sites as $collab) {
                
                // make link
                $collab_link = sprintf(
                    '<a href="%s/course/view.php?id=%d">%s<a/>', 
                    $CFG->wwwroot, $collab->id, $collab->fullname);
                
                // get user's role               
                $roles = get_user_roles_in_course($USER->id, $collab->id);

                // remove links from role string  
                $roles = strip_tags($roles);    
                
                $t->data[] = array($collab_link, $roles);                
            }
                
            $content[] = html_writer::table($t);    
        }
        
        $this->content->text = implode($content);

        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index'=>true);
    }

    public function make_terms_selector($terms, $default=false) {
        global $CFG, $PAGE;

        $urls = array();

        $page = $PAGE->url;

        // Hack to stop debugging message that says that the current
        // term is not a local relative url.
        $defaultfound = false;

        foreach ($terms as $term) {
            $thisurl = clone($page);
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);

            $urls[$url] = ucla_term_to_text($term);

            if ($default !== false && $default == $term) {
                $default = $url;
                $defaultfound = true;
            }
        }

        if (!$defaultfound) {
            $default = false;
        }
    
        return $selects = new url_select($urls, $default);
    }

    /**
     *  Used with usort(), sorts a bunch of entries returned via 
     *  ucla_get_reg_classinfo.
     *    https://jira.ats.ucla.edu:8443/browse/CCLE-2832
     *  Sorts via term, subject area, cat_num, sec_num
     **/
    static function registrar_course_sort($a, $b) {
        if (empty($a->reg_info) || empty($b->reg_info)) {
            throw new moodle_exception('cannotcomparecourses');
        }

        // Find the host course
        $ariarr = array();
        foreach ($a->reg_info as $k => $v) {
            $ariarr[$k] = get_object_vars($v);
        }

        foreach ($b->reg_info as $k => $v) {
            $briarr[$k] = get_object_vars($v);
        }

        $arik = set_find_host($ariarr);
        $brik = set_find_host($briarr);

        // If they're indeterminate
        if ($arik === false || $brik === false) {
            throw new moodle_exception(UCLA_REQUESTOR_BADHOST);
        }

        // Fetch the ones that are relevant to compare
        $areginfo = $a->reg_info[$arik];
        if (isset($a->role)) {
            $areginfo->role = $a->role;
        }
        
        $breginfo = $b->reg_info[$brik];
        if (isset($b->role)) {
            $breginfo->role = $b->role;
        }
        
        // This is an array of fields to compare by after the off-set
        // term and role
        $comparr = array('term', 'role', 'subj_area', 'course_code', 
            'sectnum');

        // Go through each of those fields until we hit an imbalance
        foreach ($comparr as $field) {
            if (!isset($areginfo->{$field})) {
                return 1;
            } 
            
            if (!isset($breginfo->{$field})) {
                return -1;
            }

            $strcmpv = strcmp($areginfo->{$field}, $breginfo->{$field});

            if ($strcmpv != 0) {
                return $strcmpv;
            }
        }

        return 0;
    }
}

