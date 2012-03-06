<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

class block_ucla_browseby extends block_base {
    var $termslist = array();

    function init() {
        $this->title = get_string('pluginname', 'block_ucla_browseby');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     *  This is called in the course where the block has been added to.
     **/
    function get_content() {

    }

    function instance_allow_config() {
        return true;
    }

    /**
     *  Returns the applicable places that this block can be adde.d
     *  This block really cannot be added anywhere, so we just made a place
     *  up (hacky). If we do not do this, we will get this
     *  plugin_devective_exception.
     **/
    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => false,
            'blocks-ucla_control_panel' => false,
        );
    }

    /**
     *  Determines the terms to run the cron job for if there were no
     *  specifics provided.
     **/
    function guess_terms() {
        global $CFG;

        if (!empty($this->termslist)) {
            return;
        }

        $this->termslist = array($CFG->currentterm);
    }

    function cron() {
        $this->guess_terms();

        if (empty($this->termslist)) {
            return true;
        }

        $this->sync($this->termslist);
    }

    function sync($terms, $subjareas=null) {
        global $DB; 

        ucla_require_registrar();

        if (empty($terms)) {
            debugging('no terms specified for browseby cron');
            return true;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;

        if (!empty($subjareas)) {
            list($sqlin, $saparams) = $DB->get_in_or_equal($subjareas);
            $where .= ' AND subjarea' . $sqlin;
            $params = array_merge($params, $saparams);
        }

        $records = $DB->get_records_select('ucla_request_classes',
            $where, $params, '', 'DISTINCT CONCAT(term, department), term, '
                . 'department AS subjarea');

        if (empty($records)) {
            return true;
        }
    
        // These are all going to SHARE KEYS, like roommates
        $toreg = array();
        $courseinfos = array();
        $instrinfos = array();

        foreach ($records as $record) {
            $term = $record->term;
            $subjarea = $record->subjarea;
            $thisreg = array('term' => $term, 
                'subjarea' => $subjarea);
            $toreg[] = $thisreg;

            $courseinfo = $this->run_registrar_query(
                'ccle_coursegetall', $toreg);

            foreach ($courseinfo as $ci) {
                $ci['term'] = $term;
                $courseinfos[] = $ci;
            }

            $instrinfo = $this->run_registrar_query(
                'ccle_getinstrinfo', $toreg);

            foreach ($instrinfo as $ii) {
                $ii['subjarea'] = $subjarea;
                $instrinfos[] = $ii;
            }
        }

        // Save which courses need instructor informations.
        // We need to update the existing entries, and remove 
        // non-existing ones.
        self::partial_sync_table('ucla_browseall_classinfo', $courseinfos,
            array('term', 'srs'), $where, $params);

        self::partial_sync_table('ucla_browseall_instrinfo', $instrinfos,
            array('term', 'srs'), $where, $params);
            
        return true;
    }

    /** 
     *  Will check a table for entries, insert and update entries provided
     *  in the arguments.
     *  @param  $table      The table to work with
     *  @param  $tabledata  
     *      Array( Array(), ... ) to sync the table with. Should have
     *      indices specified in $syncfields.
     *  @param  $syncfields
     *      Array() of fields from table and tabledata to compare old 
     *      and new entries with.
     *  @param  $partialwhere
     *      The where statement in a get_records_select() to synchronize
     *      a smaller part of a table.
     *  @param  $partialparmas
     *      The parameters for a get_records_select() to synchronize
     *      a smaller part of a table.
     *  @return
     **/
    static function partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere=null, $partialparams=null) {
        global $DB;

        $partial = ($partialwhere === null || $partialparams === null);

        // Optimization...
        if (empty($tabledata)) {
            if ($partial) {
                $r = $DB->delete_records_select($table, 
                    $partialwhere, $partialparams);
            } else {
                // This means a full delete...
                $r = $DB->delete_records($table);
            }

            return $r;
        }

        // Get existing records to determine if we're going to insert or
        // going to update
        if ($partial) {
            $existingrecords = $DB->get_records($table);
        } else {
            $existingrecords = $DB->get_records_select($table, 
                $partialwhere, $partialparams);
        }


        // Since if it exists already we update, we're going to be
        // constantly searching through this array, so we're going to
        // speed it up by doing something they call "indexing"
        $existing_indexed = array();
        foreach ($existingrecords as $record) {
            $existing_indexed[self::dynamic_hash($record, $syncfields)]
                = $record;
        }
        
        $u = 0;
        $i = 0;

        foreach ($tabledata as $data) {
            $hash = self::dynamic_hash($data, $syncfields);

            if (isset($existing_indexed[$hash])) {
                $data['id'] = $existing_indexed[$hash]->id;

                $DB->update_record($table, $data);
                unset($existing_indexed[$hash]);
                $u++;
            } else {
                $DB->insert_record($table, $data);
                // Remove this from the array so we don't delete
                // it from the db.
                $i++;
            }
        }


        // We're going to generate a set of ids of records we're going
        // to obliterate
        if (empty($existing_indexed)) {
            return;
        }

        $delete_ids = array();
        foreach ($existing_indexed as $existing) {
            $delete_ids[] = $existing->id;
        }

        list($sqlin, $params) = $DB->get_in_or_equal($delete_ids);
        $where = 'id ' . $sqlin;

        $DB->delete_records_select($table, $where, $params);
    }
    
    static function dynamic_hash($data, $hashfields) {
        $prehash = array();
        if (is_object($data)) {
            $datarr = get_object_vars($data);
        } else {
            $datarr = $data;
        }

        foreach ($hashfields as $field) {
            if (isset($datarr[$field])) {
                $prehash[$field] = $datarr[$field];
            } else {
                $prehash[$field] = null;
            }
        }

        return serialize($prehash);
    }

    protected function run_registrar_query($q, $d) {
        ucla_require_registrar();

        return registrar_query::run_registrar_query($q, $d, true);
    }
}

/** eof **/
