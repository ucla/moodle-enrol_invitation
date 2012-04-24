<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Horizon Wimba, All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Horizon Wimba.                       *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Horizon Wimba Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 ******************************************************************************/


 // $Id: migrate2utf8.php 45296 2007-02-19 12:07:16Z hugues $
function migrate2utf8_wiki_name($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->name, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->name = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_summary($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->summary, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->summary = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_pagename($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->pagename, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->pagename = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

function migrate2utf8_wiki_initialcontent($recordid){
    global $CFG, $globallang;

/// Some trivial checks
    if (empty($recordid)) {
        log_the_problem_somewhere();
        return false;
    }

    if (!$wiki = get_record('wiki','id',$recordid)) {
        log_the_problem_somewhere();
        return false;
    }
    if ($globallang) {
        $fromenc = $globallang;
    } else {
        $sitelang   = $CFG->lang;
        $courselang = get_course_lang($wiki->course);  //Non existing!
        $userlang   = get_main_teacher_lang($wiki->course); //N.E.!!

        $fromenc = get_original_encoding($sitelang, $courselang, $userlang);
    }

/// We are going to use textlib facilities
    
/// Convert the text
    if (($fromenc != 'utf-8') && ($fromenc != 'UTF-8')) {
        $result = utfconvert($wiki->initialcontent, $fromenc);

        $newwiki = new object;
        $newwiki->id = $recordid;
        $newwiki->initialcontent = $result;
        migrate2utf8_update_record('wiki',$newwiki);
    }
/// And finally, just return the converted field
    return $result;
}

?>
