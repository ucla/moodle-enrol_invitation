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

require_once(dirname(__FILE__) . '/../lib.php');

/**
 *  Runs extra commands when installing.
 *  Called by Moodle automatically.
 **/
function xmldb_local_ucla_install() {
    // Do stuff eventually
    $result = true;

    local_ucla_cron();

    // Maybe add some tables we need?
    return $result;
}

/**
 *  Runs commands to recover a halted installation.
 *  Called by Moodle automatically.
 **/
function xmldb_local_ucla_install_recovery() {
    // Do stuff eventually
    $result = true;

    return $result;
}

// EOF
