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
require_once($CFG->dirroot . '/blocks/ucla_browseby/renderer.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin 
    . '/tool/uclacoursecreator/uclacoursecreator.class.php');


class block_ucla_browseby extends block_list {
    var $termslist = array();

    function init() {
        $this->title = get_string('displayname', 'block_ucla_browseby');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     *  This is called in the course where the block has been added to.
     **/
    function get_content() {
        global $CFG;
        $this->content->icons = array();

        $link_types = browseby_handler_factory::get_available_types();

        $blockconfig = get_config('block_ucla_browseby');

        foreach ($link_types as $link_type) {
            if (empty($blockconfig->{'disable_' . $link_type})) {
                $this->content->items[] = html_writer::link(
                    new moodle_url(
                        $CFG->wwwroot . '/blocks/ucla_browseby/view.php',
                        array('type' => $link_type)
                    ), get_string('link_' . $link_type, 'block_ucla_browseby')
                );
            }
        }
    }

    function instance_allow_config() {
        return false;
    }

    /**
     *  Returns the applicable places that this block can be added.
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
   
        // These all share the same integer keys
        $toreg = array();
        $courseinfos = array();
        $instrinfos = array();

        // Collect data from registrar
        foreach ($records as $record) {
            $term = $record->term;
            $subjarea = $record->subjarea;
            $thisreg = array('term' => $term, 
                'subjarea' => $subjarea);
            $toreg = array($thisreg);

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
        $this->partial_sync_table('ucla_browseall_classinfo', $courseinfos,
            array('term', 'srs'), $where, $params);

        $this->partial_sync_table('ucla_browseall_instrinfo', $instrinfos,
            array('term', 'srs'), $where, $params);
            
        return true;
    }

    protected function partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere=null, $partialparams=null) {
        ucla_require_db_helper();

        return db_helper::partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere, $partialparams);
    }
   
    protected function run_registrar_query($q, $d) {
        ucla_require_registrar();

        return registrar_query::run_registrar_query($q, $d, true);
    }
}

/** eof **/
