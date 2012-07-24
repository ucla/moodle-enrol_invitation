<?php

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/requestor_shared_form.php');

class requestor_subjarea_form extends requestor_shared_form {
    var $type = 'builddept';

    function specification() {
        $mform =& $this->_form;

        $subjareas = $this->_customdata['subjareas'];

        $pulldown_subject = array();
        foreach ($subjareas as $subjarea) {
            $s = $subjarea->subjarea;
            $pulldown_subject[$s] = $s . ' - ' . $subjarea->subj_area_full;
        }

        $spec = array();
        $spec[] =& $mform->createElement('select', 'subjarea', null, 
            $pulldown_subject);

        return $spec;
    }

    function respond($data) {
        $ci = $data->{$this->groupname};

        $term = $ci['term'];
        $sa = $ci['subjarea'];
        
        // Fetch all possible courses
        $sac = get_courses_for_subj_area($term, $sa);

        // The stored procedures returns fields with different names for 
        // the same semantic data
        // Destination - Source
        $translators = array(
            'subj_area' => 'subjarea',
            'coursenum' => 'course',
            'sectnum' => 'section',
            'enrolstat' => 'sect_enrl_stat_cd'
        );

        // Translate everything
        $sacreq = array();
        foreach ($sac as $course) {
            if (is_object($course)) {
                $course = get_object_vars($course);
            }

            // Add the stupid term to the data
            $course['term'] = $term;

            foreach ($translators as $to => $from) {
                $course[$to] = $course[$from];
                unset($course[$from]);
            }

            $k = make_idnumber($course);

            $sacreq[$k] = $course;
        }
       
        // Get the request in the DB...
        $exists = get_course_requests($sacreq);

        foreach ($sacreq as $key => $course) {
            // ignore request that either need to be ignored for department
            // builds or have already been requested, in terms of
            // prepping from registrar to requests
            if (requestor_ignore_entry($course) || !empty($exists[$key])) {
                unset($sacreq[$key]);
            }
        }
       
        $newones = registrar_to_requests($sacreq);

        $hcs = array_merge($exists, $newones);

        // And their figure out their links
        $sets = array();
        foreach ($hcs as $hc) {
            if ($hc) {
                $set = get_crosslist_set_for_host($hc);

                $sets[] = $set;
            }
        }

        return $sets;
    }
}

// EOF
