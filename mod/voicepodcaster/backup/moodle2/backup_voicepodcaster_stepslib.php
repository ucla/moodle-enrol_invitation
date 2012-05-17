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
 * Define all the backup steps that will be used by the backup_voicepodcaster_activity_task
 */

/**
 * Define the complete voicepodcaster structure for backup, with file and id annotations
 */
class backup_voicepodcaster_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $voicepodcaster = new backup_nested_element('voicepodcaster', array('id'), array(
            'rid', 'course', 'name', 'type', 'section', 'timemodified', 'isfirst'));

        // Build the tree

        // Define sources

        $voicepodcaster->set_source_table('voicepodcaster', array('id' => backup::VAR_ACTIVITYID));
        //TODO: Set name to activityname for the backup as backup doesnt like tags

        // Define id annotations

        // Define file annotations

        // Return the root element (voicepodcaster), wrapped into standard activity structure
        return $this->prepare_activity_structure($voicepodcaster);
    }

}
