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

require_once(dirname(__FILE__) . '/../locallib.php');

// TODO Make sure that this does not cause the install to crash

function xmldb_local_ucla_install() {
    // Do stuff eventually
    $result = ucla_verify_configuration_setup();

    if (!$result) {
        throw new moodle_exception('access_failure', 'local_ucla');
    }

    // Maybe add some tables we need?
    return $result;
}

function xmldb_local_ucla_install_recovery() {
    // Do stuff eventually

    return true;
}

// EOF
