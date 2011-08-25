<?php

defined('MOODLE_INTERNAL') || die();

class easyupload {
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
}
