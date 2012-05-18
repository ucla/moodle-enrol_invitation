#******************************************************************************
#*                                                                            *
#* Copyright (c) 1999-2007 Horizon Wimba, All Rights Reserved.                *
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

# $Id: postgres7.sql 45296 2007-02-19 12:07:16Z hugues $

# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display

# --------------------------------------------------------

#
# Table structure for table `liveclassroom`
#

CREATE TABLE prefix_liveclassroom (
   id SERIAL,
   course integer NOT NULL default '0',
   type varchar(255) NOT NULL default '',
   name varchar(255) NOT NULL default '',
   section integer NOT NULL default '0',
   timemodified integer NOT NULL default '0',
   PRIMARY KEY (id)
);

CREATE INDEX prefix_liveclassroom_course_idx ON prefix_liveclassroom (course);

