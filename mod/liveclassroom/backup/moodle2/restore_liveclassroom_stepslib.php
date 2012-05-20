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
 * Define all the restore steps that will be used by the restore_liveclassroom_activity_task
 */

/**
 * Structure step to restore one liveclassroom activity
 */
class restore_liveclassroom_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('liveclassroom', '/activity/liveclassroom');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_liveclassroom($data) {
        global $CFG, $DB;

        if (!function_exists('getKeysOfGeneralParameters')) {
            require_once("$CFG->dirroot/mod/liveclassroom/lib/php/common/WimbaLib.php");
        }

        require_once("$CFG->dirroot/mod/liveclassroom/lib/php/lc/lcapi.php");
        require_once("$CFG->dirroot/mod/liveclassroom/lib/php/lc/LCAction.php");

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $userinfo = $this->get_setting_value('userinfo');
        $status = true;
        $lcAction = new LCAction(null,$CFG->liveclassroom_servername,
                     $CFG->liveclassroom_adminusername,
                     $CFG->liveclassroom_adminpassword,$CFG->dataroot, $data->course);

        $copy_content = $userinfo ? 1 : 0;
        $sameResource = $DB->get_record('liveclassroom', array('fromid' => $data->type, 'course' => $data->course, 'copy_content' => $copy_content));
        $resource = $DB->get_record('liveclassroom', array('fromid' => $data->type, 'course' => $data->course, 'copy_content' => !$copy_content));
        if (empty($sameResource)) {
            if (!$new_lc_id = $lcAction->cloneRoom($data->course, $data->type,
                    $userinfo, $data->studentadmin, $data->preview)) {
                return false;
            }

            if (!empty($resource)) {
                if ($userinfo) {
                    $room = $lcAction->getRoom($new_lc_id);
                    $room->setLongname($room->getLongname()." with user data");
                    $lcAction->api->lcapi_modify_room($new_lc_id, $room->getAttributes());
                }
                else {
                    $room = $lcAction->getRoom($resource->type);
                    $room->setLongname($room->getLongname()." with user data");
                    $lcAction->api->lcapi_modify_room($resource->type, $room->getAttributes());
                }
            }
        } else {
            $new_lc_id = $resource->type;
        }
        $data->isfirst = 1;
        $data->fromid = $data->type;
        $data->type = $new_lc_id;
        unset($data->preview);
        unset($data->studentadmin);

        $newitemid = $DB->insert_record('liveclassroom', $data);
        $this->apply_activity_instance($newitemid);
    }
}
