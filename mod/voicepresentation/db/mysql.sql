#******************************************************************************
#*                                                                            *
#* Copyright (c) 1999-2006 Horizon Wimba, All Rights Reserved.                *
#*                                                                            *
#* COPYRIGHT:                                                                 *
#*      This software is the property of Horizon Wimba.                       *
#*      You can redistribute it and/or modify it under the terms of           *
#*      the GNU General Public License as published by the                    *
#*      Free Software Foundation.                                             *
#*                                                                            *
#* WARRANTIES:                                                                *
#*      This software is distributed in the hope that it will be useful,      *
#*      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
#*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
#*      GNU General Public License for more details.                          *
#*                                                                            *
#*      You should have received a copy of the GNU General Public License     *
#*      along with the Horizon Wimba Moodle Integration;                      *
#*      if not, write to the Free Software Foundation, Inc.,                  *
#*      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
#*                                                                            *
#* Author: Hugues Pisapia                                                     *
#*                                                                            *
#* Date: 15th April 2006                                                      *
#*                                                                            *
#******************************************************************************

# $Id: mysql.sql 67495 2008-09-11 11:17:38Z thomasr $

# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display


CREATE TABLE `prefix_voicepresentation` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `rid` varchar(160)  NOT NULL default '',
  `course` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `type` varchar(160) NOT NULL default '',
  `section` int(10) unsigned NOT NULL default '0',
  `timemodified` int(10) unsigned NOT NULL default '0', 
  `isfirst` int(10) unsigned NOT NULL default '0', 
 
  PRIMARY KEY  (`id`)
) COMMENT='Defines Voice Tools Activities';


CREATE TABLE `prefix_voicepresentation_resources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `rid` varchar(160) NOT NULL default '',
  `name` varchar(255) NOT NULL,
  `course` int(10) unsigned NOT NULL default '0',
  `type` varchar(160) NOT NULL default '',
  `availability` int(10) unsigned NOT NULL default '0',
  `start_date` int(10) NOT NULL default '0',
  `end_date` int(10) NOT NULL default '0',
PRIMARY KEY  (`id`)
) COMMENT='Defines Voice Tools Resources on the server';

