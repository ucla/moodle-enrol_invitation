<?php
/*
 * ----------------------------------------------------------------------------
 *
 * This file is part of the Course Menu block for Moodle
 *
 * The Course Menu block for Moodle software package is Copyright 2008 onwards 
 * NetSapiensis AB and is provided under the terms of the GNU GENERAL PUBLIC 
 * LICENSE Version 3 (GPL). This program is free software: you can redistribute 
 * it and/or modify it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of the License, 
 * or (at your option) any later version.
 *
 * This program is free software: you can redistribute it and/or modify it under 
 * the terms of the GNU General Public License as published by the Free Software 
 * Foundation, either version 3 of the License, or (at your option) any later 
 * version. This program is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 * See the GNU General Public License for more details. You should have received 
 * a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 * ----------------------------------------------------------------------------
 */

defined('MOODLE_INTERNAL') || die;

// Is there no convenience function for loading a block?
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_course_menu/block_ucla_course_menu.php');

$settings->add(new admin_setting_configtext('block_ucla_course_menu/trimlength', 
        get_string('trimlength', 'block_ucla_course_menu'), '', 
        22, PARAM_INT, 11));

$options = array(
    block_ucla_course_menu::TRIM_RIGHT => 
        get_string('trimmoderight', 'block_ucla_course_menu'),
    block_ucla_course_menu::TRIM_LEFT =>
        get_string('trimmodeleft', 'block_ucla_course_menu'),
    block_ucla_course_menu::TRIM_CENTER =>
        get_string('trimmodecenter', 'block_ucla_course_menu')
    );

$settings->add(new admin_setting_configselect('block_ucla_course_menu/trimmode', 
        get_string('trimmode', 'block_ucla_course_menu'), '', 
        block_ucla_course_menu::TRIM_RIGHT, $options));

