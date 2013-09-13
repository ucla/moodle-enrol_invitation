<?php
/**
 * Copyright (c) 2009 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: admin.php 173 2012-11-04 20:05:05Z azeckoski@gmail.com $ */

/**
 * Handles rendering the CSV which represents all clicker registrations in the system
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;
require_once ('iclicker_service.php');
require_once ('controller.php');

$site = get_site();
require_login($site);

// activate the controller
$cntlr = new iclicker_controller();
$cntlr->processAdminCSV();
extract($cntlr->results);

// set CSV headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Transfer-Encoding: binary");
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=\"registrations.csv\";" );

// begin rendering
$regHeaderData = array(
    'ClickerId',
    'UserId',
    'UserDisplayName',
    'UserEmail',
    'Activated',
    'TimeCreated',
    'TimeModified',
);
$row = iclicker_service::make_CSV_row($regHeaderData);
echo $row;
foreach($registrations as $registration) {
    $regData = array(
        $registration->clicker_id,
        $registration->owner_id,
        $registration->user_display_name,
        $registration->user_email,
        $registration->activated ? 1 : 0,
        $registration->timecreated,
        $registration->timemodified,
    );
    $row = iclicker_service::make_CSV_row($regData);
    echo $row;
}
