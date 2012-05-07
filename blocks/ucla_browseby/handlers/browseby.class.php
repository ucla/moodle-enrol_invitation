<?php

/**
 *  This is the base class.
 *  Very abstract indeed...
 **/
abstract class browseby_handler {
    /**
     *  @return Array( ... ) strings that we should require_param, you do
     *      not need to include 'term'
     **/
    abstract function get_params();

    /**
     *  @param Array 
     *  @return Array(string $title, string $content)
     **/
    abstract function handle($args);

    /**
     *  Hook function to do some checks before running the handler.
     **/
    function run_handler($args) {
        $return = $this->handle($args);
        return $return;
    }
    
    /**
     *  A highly-specific convenience function. That feeds into the
     *  renderer.
     **/
    function list_builder_helper($data, $keyfield, $dispfield, $type, $get, 
                                 $term=false, $evenmore=false) {
        global $PAGE;

        $table = array();
        foreach ($data as $datum) {
            if (!empty($datum->no_display)) {
                continue;
            }

            $k = $datum->{$keyfield};
            $queryterms = array('type' => $type, $get => $k);

            if ($term) {
                $queryterms['term'] = $term;
            }

            if ($evenmore) {
                $queryterms = array_merge($queryterms, $evenmore);
            }

            $urlobj = clone($PAGE->url);
            $urlobj->params($queryterms);

            $table[$k] = html_writer::link($urlobj, 
                ucla_format_name(strtolower($datum->{$dispfield}))
            );
        }

        return $table;
    }
  
    /** 
     *  Returns a display-ready string for subject areas.
     **/
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
     *  Determines whether the course should not be displayed if it doesn't 
     *  a website.
     **/
    function ignore_course($course) {
        // If the course is a particular number
        if (!empty($course->course_code)) {
            $coursecode = intval(substr($course->course_code, 0, 4));

            $ignorecoursenums = get_config('block_ucla_browseby', 
                    'ignore_coursenum');
            if ($ignorecoursenums) {

                // Special formatting
                if (!is_array($ignorecoursenums)) {
                    $ignorecoursenums = explode(',', $ignorecoursenums);
                }

                foreach ($ignorecoursenums as $ignorecoursenum) {
                    $ignorecoursenum = trim($ignorecoursenum);
                    if ($coursecode == $ignorecoursenum) {
                        return true;
                    }
                }
            }
        }

        // If the course is NOT a particular activity type
        if (!empty($course->activitytype)) {
            $allowacttypes = $this->get_config('allow_acttypes');
            if (empty($allowacttypes)) {
                return false;
            } else {
                if (is_string($allowacttypes)) {
                    $acttypes = explode(',', $allowacttypes);
                } else {
                    $acttypes = $allowacttypes;
                }

                foreach ($acttypes as $acttype) {
                    if ($course->activitytype == trim($acttype)) {
                        return false;
                    } 
                }
            }
        }

        return true;
    }

    /**
     *  Decoupled functions.
     **/
    protected function get_config($name) {
        if (!isset($this->configs)) {
            $this->configs = get_config('block_ucla_browseby');
        }


        if (empty($this->configs->{$name})) {
            return false;
        }

        return $this->configs->{$name};
    }

    protected function render_terms_restricted_helper($rt=false) {
        return block_ucla_browseby_renderer::render_terms_restricted_helper(
            $rt);
    }

    protected function get_records_sql($sql, $params=null) {
        global $DB;

        return $DB->get_records_sql($sql, $params);
    }

    protected function get_roles_with_capability($cap) {
        return get_roles_with_capability($cap);
    }

    protected function role_mapping($pc, $o, $sa="*SYSTEM*") {
        return role_mapping($pc, $o, $sa);
    }
    
    protected function get_division($division_code) {
        global $DB;

        return ucla_format_name($DB->get_field('ucla_reg_division', 'fullname',
            array('code' => $division_code)));        
    }
}

