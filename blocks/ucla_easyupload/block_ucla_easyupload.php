<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_ucla_easyupload extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_easyupload');
    }

    // Handle a physical file upload
    static function upload($contextid) {
        global $CFG, $DB;

        $type = 'upload';
        $sql = "
            SELECT i.id, i.name 
            FROM {repository} r
                INNER JOIN {repository_instances} i 
                    ON i.typeid = r.id
            WHERE r.type = ?
        ";

        $repo = $DB->get_record_sql($sql, array($type));

        if (!$repo) {
            throw new moodle_exception();
        }

        $file = $CFG->dirroot . '/repository/' . $type . '/lib.php';

        if (file_exists($file)) {
            require_once($file);

            $classname = 'repository_' . $type;

            try {
                $repo = new $classname($repo->id, $contextid, array(
                    'ajax' => false,
                    'name' => $repo->name,
                    'type' => $type
                ));
            } catch (repository_exception $e) {
                print_error('pluginerror', 'repository');
            }
        } else {
            print_error('invalidplugin', 'repository');
        }

        $maxbytes = get_max_upload_file_size();

        return $repo->upload('', $maxbytes);
    }

    /**
     *  Checks if rearrange JS framework is available.
     **/
    static function block_ucla_rearrange_installed() {
        global $CFG;

        $rearrangepath = $CFG->dirroot
            . '/blocks/ucla_rearrange/block_ucla_rearrange.php';

        if (file_exists($rearrangepath)) {
            require_once($rearrangepath);
            return true;
        }

        return false;
    }

    /**
     *  Convenience function to generate a variable assignment 
     *  statement in JavaScript.
     *  TODO Might want to move this function to rearrange
     **/
    static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . $val . '"';
        }

        return 'M.block_ucla_easyupload.' . $var . ' = ' . $val;
    }

    /** 
     *  Returns if the type specified has code to handle it.
     **/
    static function upload_type_exists($type) {
        global $CFG;

        $typelib = $CFG->dirroot 
            . '/blocks/ucla_easyupload/upload_types/*.php';

        $possibles = glob($typelib);

        foreach ($possibles as $typefile) {
            require_once($typefile);
        }

        $typeclass = 'easyupload_' . $type . '_form';

        if (class_exists($typeclass)) {
            return $typeclass;
        } 

        return false;
    }

    static function ucla_cp_hook($course, $context) {
        global $CFG;

        $thispath = '/blocks/ucla_easyupload/upload.php';

        $common_components = array('file', 'link');
        $other_components = array('activity', 'resource', 'subheading', 
            'text');

        $allmods = array();

        foreach ($common_components as $common) {
            $allmods[] = array(
                'item_name' => 'add_' . $common,
                'tags' => array('ucla_cp_mod_common'),
                'action' => new moodle_url($thispath,
                    array('course_id' => $course->id, 'type' => $common)),
                'required_cap' => 'moodle/course:update'
            );
        }
        
        foreach ($other_components as $common) {
            $allmods[] = array(
                'item_name' => 'add_' . $common,
                'tags' => array('ucla_cp_mod_other'),
                'action' => new moodle_url($thispath,
                    array('course_id' => $course->id, 'type' => $common)),
                'required_cap' => 'moodle/course:update'
            );
        }

        return $allmods;
    }
    
    /**
     *  Do not allow block to be added anywhere
     */
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false
        );
    }    
}

// End of file
