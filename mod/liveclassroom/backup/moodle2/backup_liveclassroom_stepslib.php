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
 * Define all the backup steps that will be used by the backup_liveclassroom_activity_task
 */

/**
 * Define the complete liveclassroom structure for backup, with file and id annotations
 */
class backup_liveclassroom_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $CFG, $DB;

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $liveclassroom = new backup_nested_element('liveclassroom', array('id'), array(
            'course', 'type', 'name', 'section', 'timemodified', 'isfirst',
            'fromid', 'copy_content', 'studentadmin', 'preview'));

        // Build the tree

        // Define sources
        $courseid = $this->task->get_courseid();
        $lcAction = new LCAction(null,$CFG->liveclassroom_servername,
                     $CFG->liveclassroom_adminusername,
                     $CFG->liveclassroom_adminpassword,null,$courseid);
        $record = $DB->get_record('liveclassroom', array('id' => $this->task->get_activityid()));
        $type = $record->type;
        $roomPreview = $lcAction->getRoomPreview($type);

        $studentadmin = false;
        if($lcAction->isStudentAdmin($courseid, $courseid.'_S') == "true") {
            $studentadmin = true;
        }

//      $liveclassroom->set_source_table('liveclassroom', array('id' => backup::VAR_ACTIVITYID));
        $liveclassroom->set_source_sql("SELECT *, '$studentadmin' as studentadmin, '$roomPreview' as preview
                                        FROM {liveclassroom} 
                                        WHERE id = :id", array('id'=>backup::VAR_ACTIVITYID));

        // Define id annotations

        // Define file annotations

        // Return the root element (liveclassroom), wrapped into standard activity structure
        return $this->prepare_activity_structure($liveclassroom);
    }

}
