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

# $Id: postgres7.sql 45986 2007-03-05 16:56:39Z thomasr $

# This file contains a complete database schema for all the 
# tables used by this module, written in SQL

# It may also contain INSERT statements for particular data 
# that may be used, especially new entries in the table log_display

# --------------------------------------------------------

#
# Table structure for table `voicetools`
#

CREATE TABLE prefix_voicetools (
   id SERIAL,
   rid varchar(160) NOT NULL default '',
   course integer NOT NULL default '0',
   name varchar(255) NOT NULL default '',
   type varchar(15) NOT NULL default '',
   section integer NOT NULL default '0',
   timemodified integer NOT NULL default '0',
   PRIMARY KEY (id)
);

CREATE INDEX prefix_voicetools_course_idx ON prefix_voicetools (course);

-- 
-- Table structure for table `mdl_voicetools_resources`
-- 

CREATE TABLE prefix_voicetools_resources (
  id SERIAL,
  rid varchar(160) NOT NULL default '',
  
  course integer  NOT NULL default '0',
  type varchar(160) NOT NULL default '',
  availability integer NOT NULL default '0',
  start_date integer NOT NULL default '0',
  end_date integer NOT NULL default '0',
  PRIMARY KEY  (id)
);
CREATE INDEX prefix_voicetools_resources_course_idx ON prefix_voicetools_resources (course);

CREATE TABLE prefix_voicetools_recorder (
  id SERIAL,
  bid integer NOT NULL default '0',
  title varchar(160) NOT NULL default '',
  comment varchar(160) NOT NULL default '',
  PRIMARY KEY  (id)
);

