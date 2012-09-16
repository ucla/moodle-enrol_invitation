<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
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
/* $Id: version.php 164 2012-08-22 20:11:41Z azeckoski@gmail.com $ */

defined('MOODLE_INTERNAL') || die();

// http://docs.moodle.org/dev/version.php
$plugin->version    = 2012082200;        // The current plugin version (Date: YYYYMMDDXX) - must match iclicker_service constant
$plugin->requires   = 2010112400;        // moodle 2.0 - Requires this Moodle version - Moodle 2.0 = 2010112400; Moodle 2.1 = 2011070100; Moodle 2.2 = 2011120100; Moodle 2.3 = 2012062500
$plugin->cron       = 86400;
$plugin->component  = 'block_iclicker';    // Full name of the plugin (used for diagnostics)
$plugin->maturity   = MATURITY_STABLE;
$plugin->release    = '1.1 (Build: '.$plugin->version.')'; // visible version - must match iclicker_service constant
