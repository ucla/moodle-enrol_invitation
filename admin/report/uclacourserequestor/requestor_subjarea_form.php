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

        $sac = get_courses_for_subj_area($term, $sa);

        // This is backward... 
        $translators = array(
            'subj_area' => 'subjarea',
            'coursenum' => 'course',
            'sectnum' => 'section'
        );

        $infos = array();

        $instrs = array();
        foreach ($sac as $course) {
            $preprep = get_object_vars($course);

            // THERE AIN't NO TERM, BABE
            $preprep['term'] = $term;
            $srs = $preprep['srs'];
            $k = "$term-$srs";

            foreach ($translators as $to => $from) {
                $preprep[$to] = $preprep[$from];
            }
            
            if (requestor_ignore_entry($preprep)) {
                continue;
            }

            // Maybe optimize?
            $exists = get_course_request($term, $srs);
            if ($exists) {
                $infos[$k] = $exists;
                continue;
            }

            if (empty($ci['skipinstructors'])) {
                $instrs[$k] = get_instructor_info_from_registrar($term, $srs);
            } else {
                $instrs[$k] = array();
            }

            $infos[$k] = $preprep;
        }

        $returninfos = array();
        foreach ($infos as $key => $info) {
            if (isset($instrs[$key])) {
                $returninfos[] = prep_registrar_entry($info, $instrs[$key]);
            } else {
                $returninfos[] = $info;
            }
        }

        return $returninfos;
    }
}

