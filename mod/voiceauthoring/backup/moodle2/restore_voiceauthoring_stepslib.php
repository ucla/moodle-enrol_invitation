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

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_voiceauthoring_activity_task
 */

/**
 * Structure step to restore one voiceauthoring activity
 */
class restore_voiceauthoring_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('voiceauthoring', '/activity/voiceauthoring');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_voiceauthoring($data) {
        global $DB, $CFG;
        $userinfo = $this->get_setting_value('userinfo');
        $data = (object)$data;
        $oldid = $data->id;
        $oldcourse = $data->course;
        $data->course = $this->get_courseid();
        $resourceId = $data->rid;
        //Now, build the voiceauthoring record structure
        $resource = $DB->get_record("voiceauthoring_resources", array("fromrid" => $data->rid, "course" => $data->course));
        if(empty($resource))
        {
            $resourceCopy = voicetools_api_copy_resource($data->rid,null,"0");
            if($resourceCopy === false){
              return false;//error to copy the resource
            }
            $resourceId=$resourceCopy->getRid();
            voiceauthoring_createResourceFromResource($data->rid,$resourceId,$data->course);
            $resource = $DB->get_record("voiceauthoring_resources", array("fromrid" => $data->rid, "course" => $data->course));
        }

        if(!$userinfo)
        {
           $mid = $resource->mid + 1;
           $resource->mid = $resource->mid + 1;
           $data->mid = "va-$mid";
           $DB->update_record("voiceauthoring_resources",$resource);
           $data->name = str_replace($data->mid, $mid, $data->name);
        }

        $data->course = $this->get_courseid();
        $data->name = str_replace($data->rid, $resourceId, $data->name);
        $data->rid = $resourceId;

        $newitemid = $DB->insert_record('voiceauthoring', $data);
        $this->apply_activity_instance($newitemid);
    }
}
