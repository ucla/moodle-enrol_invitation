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
 * Configuration file for role mappings
 *
 * @copyright 2013 UC Regents
 * @package   local_ucla
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// SYSTEM defaults.
$role['ta']['*SYSTEM*'] = 'ta_admin'; // 02 whenever there is also an 01.
$role['ta_instructor']['*SYSTEM*'] = 'ta_instructor';
$role['supervising_instructor']['*SYSTEM*'] = 'supervising_instructor';
$role['student_instructor']['*SYSTEM*'] = 'studentfacilitator';
$role['editingteacher']['*SYSTEM*'] = 'editinginstructor'; // Always an 01.
$role['student']['*SYSTEM*'] = 'student'; // Student enrolled in the course.
$role['waitlisted']['*SYSTEM*'] = 'student'; // Student waitlisted in the course.

// Subject areas using limited TA roles.
$role['ta']['CHEM'] = 'ta';
$role['ta']['EE BIOL'] = 'ta';
$role['ta']['LIFESCI'] = 'ta';
$role['ta']['MCD BIO'] = 'ta';
$role['ta']['PHYSCI'] = 'ta';
$role['ta']['NEUROSC'] = 'ta';
