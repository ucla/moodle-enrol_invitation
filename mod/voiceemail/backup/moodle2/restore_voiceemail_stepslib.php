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
 * Define all the restore steps that will be used by the restore_voiceemail_activity_task
 */

/**
 * Structure step to restore one voiceemail activity
 */
class restore_voiceemail_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('voiceemail', '/activity/voiceemail');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_voiceemail($data) {
        global $DB, $CFG;

        $data = (object)$data;

        $userinfo = $this->get_setting_value('userinfo');
        if($userinfo)
        {
            $copyOptions="0";//top message
        }
        else
        {
            $copyOptions="2";//top message
        }


        $oldResource = $DB->get_record("voiceemail_resources", array("id" => $data->rid));
        $resourceId = $oldResource->rid;
        $resource = voicetools_api_copy_resource($resourceId,null,$copyOptions);
        if($resource === false) {
          return false;//error to copy the resource
        }
        $bdId = voiceemail_createResourceFromResource($resourceId,$resource->getRid(),$data->course);

        $data->course = $this->get_courseid();
        $data->rid = $bdId;
        $data->isfirst = 1;

        $newitemid = $DB->insert_record('voiceemail', $data);
        $this->apply_activity_instance($newitemid);
    }
}
