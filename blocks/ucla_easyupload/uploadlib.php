<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_control_panel/rearrangelib.php');

class easyupload {
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
     *  Convenience function to generate a variable assignment 
     *  statement in JavaScript.
     **/
    static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . $val . '"';
        }

        return 'M.block_ucla_control_panel.' . $var . ' = ' . $val;
    }
}

