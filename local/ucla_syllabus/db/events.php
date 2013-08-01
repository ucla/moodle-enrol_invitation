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
 * Database events.
 * 
 * This file contains the event handlers for the Moodle event API.
 * 
 * @package local_ucla_syllabus
 * @subpackage db
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array (
    'ucla_syllabus_added' => array (
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_updated',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),

    'ucla_syllabus_updated' => array (
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_updated',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),

    'ucla_syllabus_deleted' => array (
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_deleted',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),

    'course_created' => array(
        'handlerfile'      => '/local/ucla_syllabus/webservice/eventlib.php',
        'handlerfunction'  => 'ucla_course_alert',
        'schedule'         => 'cron',
        'internal'         => 0,
    ),

    'ucla_format_notices' => array(
        'handlerfile'      => '/local/ucla_syllabus/eventlib.php',
        'handlerfunction'  => 'ucla_syllabus_handle_ucla_format_notices',
        'schedule'         => 'instant',    // This is made instant for message passing.
        'internal'         => 1,
    ),

    'course_deleted' => array(
        'handlerfile'      => '/local/ucla_syllabus/eventlib.php',
        'handlerfunction'  => 'delete_syllabi',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);