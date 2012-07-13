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
 * Internal library of functions for module nanogong
 *
 * All the nanogong specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @author     Ning
 * @author     Gibson
 * @package    mod
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/formslib.php');

function nanogong_get_applet_code($type, $content, $contextid, $itemid) {
    global $DB, $CFG, $PAGE;
    
    $nanogongcount = substr_count($content,'NanoGongItem');
    preg_match('/<img.*?title="NanoGongItem".*?alt="/', $content, $m);
    
    for ($i = 0; $i < $nanogongcount; $i++) {  
        $startpos = strpos($content, $m[0]);
        $curpos = $startpos + strlen($m[0]);            
        $nanogongname = substr($content, $curpos, 18);
        
        $modulename = 'mod_nanogong';
        $filearea = 'audio';

        $curpos += 18;
        $totallength = $curpos - $startpos + strlen('" />');
        
        if ($type == 1) {
            $typename = 'student';
        }
        else if ($type == 2) {
            $typename = 'teacher';
        }
        else {
            $typename = 'other';
        }
        $nanogongid = $typename . substr($nanogongname, 0, strlen($nanogongname) - 4);

        $newimg = '<span style="position:relative;" id= "' . $nanogongid . '" ><img title="NanoGong Item" src="pix/icon.gif" style="vertical-align: middle" onclick="javascript:nanogong_show_applet_item(this, \'' . $nanogongid . '\', ' . $contextid . ', \'' . $modulename . '\', \'' . $filearea . '\', ' . $itemid . ', \'' . $nanogongname . '\', \'' . $CFG->wwwroot . '\');" /></span>';

        $content = substr_replace($content, $newimg, $startpos, $totallength);
    }
    
    return $content;
}

function nanogong_get_student_audios($contextid, $nanogongid, $userid) {
    global $CFG, $DB, $OUTPUT, $PAGE;
    
    echo '<tr><td align="center">' . get_string('timecreated', 'nanogong') . '</td><td>' . get_string('title', 'nanogong') . '</td><td align="center">' . get_string('voicerecording', 'nanogong') . '</td><td>' . get_string('notes', 'nanogong') . '</td></tr>';
    $nanogong = $DB->get_record('nanogong', array('id'=>$nanogongid));
    $nanogongaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$nanogongid, 'userid'=>$userid, 'type'=>1));
    $nanogongmessage = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogongid, 'userid'=>$userid));
    $nanogongcount = substr_count($nanogongmessage->message, "NanoGongItem");
    foreach ($nanogongaudios as $nanogongaudio) {
        echo '<tr><td>';
        echo userdate($nanogongaudio->timecreated);
        echo '</td><td>';
        echo $nanogongaudio->title;
        echo '</td><td align="center">';
        $itemid = substr($nanogongaudio->name, 0, strlen($nanogongaudio->name) - 4);
        $nanogongapplet = '<span id= "history' . $itemid . '" style="position:relative;" ><img src="pix/icon.gif" style="vertical-align: middle" onclick="javascript:nanogong_show_applet_item(this, \'history' . $itemid . '\', ' . $contextid . ', \'mod_nanogong\', \'audio\', ' . $nanogongid . ', \'' . $nanogongaudio->name . '\', \'' . $CFG->wwwroot . '\');" /></span>';
        echo $nanogongapplet;
        echo '</td><td>';
        if (strpos($nanogongmessage->message, $nanogongaudio->name)) {
            echo get_string('inuse', 'nanogong');
        }
        else if ($nanogong->maxnumber == 0 || $nanogongcount < $nanogong->maxnumber) {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('nanogongtitle'=>$nanogongaudio->title, 'nanogongname'=>$nanogongaudio->name)), get_string('insert', 'nanogong'));
        }
        echo '</td></tr>';
    }
}

function nanogong_show_all_students($contextid, $nanogongid, $time) {
    global $DB;
    
    $nanogong = $DB->get_record('nanogong', array('id'=>$nanogongid));
    $nanogongaudios = $DB->get_records('nanogong_audios', array('timecreated'=>$time));
    foreach ($nanogongaudios as $nanogongaudio) {
        $student = $DB->get_record('user', array('id'=>$nanogongaudio->userid));
        $studentwork = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogongid, 'userid'=>$nanogongaudio->userid));
        
        if ($studentwork->message && strpos($studentwork->message, $nanogongaudio->name) >= 0) {
            echo '<tr><td>';
            if ($nanogongaudio->timecreated <= $nanogong->timedue) {
                echo userdate($nanogongaudio->timecreated);
            }
            else {
                echo '<font color="red">' . userdate($nanogongaudio->timecreated) . '</font>';
            }
            echo '</td>';
            echo '<td align="center">';
            if ($student->firstname) {
                echo $student->firstname;
            }
            if ($student->lastname) {
                echo ' '.$student->lastname;
            }
            echo '</td>';
            echo '<td>';
            echo $nanogongaudio->title . ' ';
            $nanogongimg = '<img title="NanoGongItem" src="pix/icon.gif" style="vertical-align: middle" alt="' . $nanogongaudio->name . '" />';
            echo nanogong_get_applet_code(1, $nanogongimg, $contextid, $nanogongid);
            echo '</td>';
            echo '</tr>';
        }
    }
}

function nanogong_show_students_list($nanogongcatalog, $contextid, $nanogongid, $userid) {
    global $DB, $OUTPUT, $PAGE;
    
    $nanogong = $DB->get_record('nanogong', array('id' => $nanogongid), '*', MUST_EXIST);
    $studentmessage = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogongid, 'userid'=>$userid));
    $student = $DB->get_record('user', array('id'=>$userid));
    
    echo '<tr><th width="20%"><b>';
    if ($student->firstname) {
        echo $student->firstname;
    }
    if ($student->lastname) {
        echo ' '.$student->lastname;
    }
    echo '</b></th>';
    if ($studentmessage) {
        echo '<td colspan="2"><b>' . get_string('tableyourmessage', 'nanogong') . '</b></td></tr><tr><td></td>';
        echo '<td colspan="2">';
        nanogong_print_content_dates($studentmessage->message, $nanogong->timedue, $contextid, $nanogongid);
        echo '</td>';
        echo '</tr>';
        echo '<tr><td></td><td colspan="2"><b>' . get_string('messagefrom', 'nanogong') . '</b></td></tr><tr><td></td>';
        echo '<td colspan="2" style="border-bottom:1px solid #d3d3d3;">';
        if ($studentmessage->supplement) {
            $text = file_rewrite_pluginfile_urls($studentmessage->supplement, 'pluginfile.php', $contextid, 'mod_nanogong', 'message', $studentmessage->id);
            echo format_text($text, $studentmessage->supplementformat);
        }
        else {
            echo '<p>-</p>';
        }
        echo '</td></tr>';
        echo '<tr><td></td><td width="20%"><b>' . get_string('grade', 'nanogong') . '</b></td><td><b>' . get_string('tablevoice', 'nanogong') . '</b></td></tr><tr><td></td>';
        if ($studentmessage->grade >= 0) {
            echo '<td><p>' . $studentmessage->grade . '</p></td>';
        }
        else {
            echo '<td><p>-</p></td>';
        }
        if ($studentmessage->audio) {
            echo '<td><p>';
            echo nanogong_get_applet_code(2, $studentmessage->audio, $contextid, $nanogongid);
            echo '</p></td>';
        }
        else {
            echo '<td><p>-</p></td>';
        }
        echo '</tr><tr><td></td>';
        echo '<td colspan="2"><b>' . get_string('comment', 'nanogong') . '</b></td></tr><tr><td></td>';
        if ($studentmessage->comments) {
            echo '<td colspan="2">';
            $text = file_rewrite_pluginfile_urls($studentmessage->comments, 'pluginfile.php', $contextid, 'mod_nanogong', 'message', $studentmessage->id);
            echo format_text($text, $studentmessage->commentsformat);
            echo '</td>';
        }
        else {
            echo '<td colspan="2" ><p>-</p></td>';
        }
        echo '</tr><tr><td></td><td style="border-bottom:5px double #d3d3d3;">';
        echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('catalog'=>$nanogongcatalog, 'tograde'=>$studentmessage->userid)), get_string('edit', 'nanogong'));
        echo '</td>';
        echo '<td align="right" style="border-bottom:5px double #d3d3d3;">';
        if ($studentmessage->audio || $studentmessage->comments || $studentmessage->grade >= 0) {
            echo get_string('tablemodified', 'nanogong') . ' ' . userdate($studentmessage->timestamp);
        }
        echo '</td></tr>';
    }
    else {
        echo '<td colspan="2"><b>' . get_string('nosubmission', 'nanogong') . '</b></td></tr>';
    }
}

function nanogong_print_content_dates($message, $duedate, $contextid, $nanogongid) {
    global $DB, $CFG;
    
    $nanogongcount = substr_count($message,'NanoGongItem');
    
    for ($i = 0; $i < $nanogongcount; $i++) {
        preg_match('/<img.*?title="NanoGongItem".*?alt="/', $message, $m);
        preg_match('/<img.*?title="NanoGongItem".*?>/', $message, $n);
        $startpos = strpos($message, $m[0]);
        $curpos = $startpos + strlen($m[0]);
        $nanogongname = substr($message, $curpos, 18);
        
        preg_match('/<p title="NanoGong Title">.*?<img.*?title="NanoGongItem".*?>/', $message, $o);
        $titlelength = strlen($o[0]) - strlen($n[0]) - strlen('<p title="NanoGong Title">');
        $startpos = strpos($message, $o[0]);
        $curpos = $startpos + strlen('<p title="NanoGong Title">');
        $nanogongtitle = substr($message, $curpos, $titlelength);
        
        $nanogongaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$nanogongid));
        foreach ($nanogongaudios as $nanogongaudio) {
            if (strcmp($nanogongaudio->name, $nanogongname) == 0) {
                $nanogongtime = $nanogongaudio->timecreated;
            }
        }
        if ($nanogongtime > $duedate) {
            $time = '<font color="red">' . userdate($nanogongtime) . '</font>';
        }
        else {
            $time = userdate($nanogongtime);
        }

        echo '<p>' . $time . '&nbsp;&nbsp;&nbsp;&nbsp;' . nanogong_get_applet_code(2, substr($o[0], strlen('<p title="NanoGong Title">')), $contextid, $nanogongid) . '</p>';

        $message = substr_replace($message, '', $startpos, strlen($o[0]) + strlen('</p>'));
    }
}

function nanogong_show_in_listbox($message, $nanogongfilename, $contextid, $itemid, $listid) {
    global $DB, $CFG, $PAGE;
    
    $nanogongcount = substr_count($message,'NanoGongItem');
    $listrecordings = array();
    $nanogongaudiotime = array();
    $maxlength= 0;
    $maxcount = 0;
    
    for ($i = 0; $i < $nanogongcount; $i++) {
        preg_match('/<img.*?title="NanoGongItem".*?alt="/', $message, $m);
        preg_match('/<img.*?title="NanoGongItem".*?>/', $message, $n);
        $startpos = strpos($message, $m[0]);
        $curpos = $startpos + strlen($m[0]);
            
        $nanogongname = substr($message, $curpos, 18);
        $modulename = 'mod_nanogong';
        $filearea = 'audio';
        
        $nanogongaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$itemid));
        foreach ($nanogongaudios as $nanogongaudio) {
            if (strcmp($nanogongaudio->name, $nanogongname) == 0) {
                $nanogongaudiotime[] = $nanogongaudio->timecreated;
                if (str_word_count($nanogongaudio->title) > $maxcount) {
                    $maxcount = str_word_count($nanogongaudio->title);
                }
                if (strlen($nanogongaudio->title) > $maxlength) {
                    $maxlength = strlen($nanogongaudio->title);
                }
            }
        }
        
        $newurl= $CFG->wwwroot . '/mod/nanogong/nanogongfile.php?name=' . $nanogongname . '&modulename=' . $modulename . '&filearea=' . $filearea . '&itemid=' . $itemid . '&contextid=' . $contextid;
        
        preg_match('/<p title="NanoGong Title">.*?<img.*?title="NanoGongItem".*?>/', $message, $o);
        $titlelength = strlen($o[0]) - strlen($n[0]) - strlen('<p title="NanoGong Title">');
        $startpos = strpos($message, $o[0]);
        $curpos = $startpos + strlen('<p title="NanoGong Title">');
        $nanogongtitle = substr($message, $curpos, $titlelength);
        
        if ($nanogongfilename && strpos($newurl, $nanogongfilename)) {
            $listrecordings[] = '<option style="height:20px;" value="' . $newurl . '" selected="selected">' . $nanogongtitle . '</option>';
        }
        else {
            $listrecordings[] = '<option style="height:20px;" value="' . $newurl . '">' . $nanogongtitle . '</option>';
        }
        
        $message = substr_replace($message, '', $startpos, strlen($o[0]) + strlen('</p>'));
    }
    
    $nanogongtimes = 'a';
    for ($i = count($nanogongaudiotime) - 1; $i > -1; $i--) {
        $nanogongtimes .= $nanogongaudiotime[$i] . 'a';
    }
    if ($maxlength < 20 || $maxcount < 5) {
        $maxwidth = 'width:200px;';
    }
    else {
        $maxwidth = '';
    }
    if ($nanogongcount < 4) {
        $nanogongsize = 4;
    }
    else if ($nanogongcount > 20) {
        $nanogongsize = 20;
    }
    else {
        $nanogongsize = $nanogongcount;
    }
    echo '<div style="position:relative;"><select id="' . $listid . '" style="' . $maxwidth . 'text-align:center;overflow-y:auto;" size="' . $nanogongsize . '" onchange="javascript:nanogong_load_from_message(this, \'' . $nanogongtimes . '\', \'' . get_string('submittedon', 'nanogong') . ' \', \'' . $listid . '\');" onclick="javascript:nanogong_load_from_message(this, \'' . $nanogongtimes . '\', \'' . get_string('submitted', 'nanogong') . ' \', \'' . $listid . '\');">';
    for ($i = count($listrecordings) - 1; $i > -1; $i--) {
        echo $listrecordings[$i];
    }
    echo '</select></div>';
}

function nanogong_show_pagnumber_settings($nanogongcatalog, $pagenumber) {
    global $PAGE;
     
    echo '<tr><td></td><td align="right">';
    echo get_string('with', 'nanogong') . ' ';
    echo '</td><td align="center" width="20px">';
    echo '<select id="nanogongpagenumber" onchange="javascript:nanogong_set_pagenumber(\'' . $nanogongcatalog . '\', \'' . $PAGE->url . '\');">';
    if ($pagenumber == 1) {
        echo '<option value="1" selected="selected">1</option>';
    }
    else {
        echo '<option value="1">1</option>';
    }
    if ($pagenumber == 2) {
        echo '<option value="2" selected="selected">2</option>';
    }
    else {
        echo '<option value="2">2</option>';
    }
    if ($pagenumber == 5) {
        echo '<option value="5" selected="selected">5</option>';
    }
    else {
        echo '<option value="5">5</option>';
    }
    if ($pagenumber == 10) {
        echo '<option value="10" selected="selected">10</option>';
    }
    else {
        echo '<option value="10">10</option>';
    }
    if ($pagenumber == 20) {
        echo '<option value="20" selected="selected">20</option>';
    }
    else {
        echo '<option value="20">20</option>';
    }
    if ($pagenumber == 50) {
        echo '<option value="50" selected="selected">50</option>';
    }
    else {
        echo '<option value="50">50</option>';
    }
    if ($pagenumber == 0) {
        echo '<option value="0" selected="selected">' . get_string('all', 'nanogong') . '</option>';
    }
    else {
        echo '<option value="0">' . get_string('all', 'nanogong') . '</option>';
    }
    echo '</select>';
    echo '</td><td align="left">';
    echo ' ' . get_string('inonepage', 'nanogong') . ' </td></tr>';
}

function nanogong_show_pagnumber_settings_chronological($pagenumber, $toreverse, $tolistall) {
    global $PAGE;
     
    echo '<td>';
    echo get_string('with', 'nanogong') . ' ';
    echo '</td><td>';
    echo '<select id="nanogongpagechronological" onchange="javascript:nanogong_set_pagenumber_chronological(' . $toreverse . ', ' . $tolistall . ', \'' . $PAGE->url . '\');">';
    if ($pagenumber == 5) {
        echo '<option value="5" selected="selected">5</option>';
    }
    else {
        echo '<option value="5">5</option>';
    }
    if ($pagenumber == 10) {
        echo '<option value="10" selected="selected">10</option>';
    }
    else {
        echo '<option value="10">10</option>';
    }
    if ($pagenumber == 20) {
        echo '<option value="20" selected="selected">20</option>';
    }
    else {
        echo '<option value="20">20</option>';
    }
    if ($pagenumber == 50) {
        echo '<option value="50" selected="selected">50</option>';
    }
    else {
        echo '<option value="50">50</option>';
    }
    if ($pagenumber == 0) {
        echo '<option value="0" selected="selected">' . get_string('all', 'nanogong') . '</option>';
    }
    else {
        echo '<option value="0">' . get_string('all', 'nanogong') . '</option>';
    }
    echo '</select>';
    echo '</td><td>';
    echo ' ' . get_string('recordinginonepage', 'nanogong') . ' </td>';
}

function nanogong_preload_applet() {
    echo '<div id="nanogongstudentdiv" style="position:absolute;top:-40px;left:-130px;z-index:100;visibility:hidden;"><applet id="nanogongstudentmessage" archive="nanogong.jar" code="gong.NanoGong" width="130" height="40"><param name="ShowTime" value="true" /><param name="ShowAudioLevel" value="false" /><param name="ShowRecordButton" value="false" /></applet><p id="nanogongsubmittedtime"></p></div>';
    
    echo '<div id="nanogonguniquediv" style="position:absolute;top:-40px;left:-130px;z-index:100;visibility:hidden;"><applet id="nanogongunique" archive="nanogong.jar" code="gong.NanoGong" width="130" height="40"><param name="ShowTime" value="true" /><param name="ShowAudioLevel" value="false" /><param name="ShowRecordButton" value="false" /></applet></div>';
}

function nanogong_student_submit_form($nanogongnum, $maxduration, $cmid, $type) {
    global $CFG, $PAGE, $OUTPUT, $USER;
    
    $nanogongtitle = get_string('recordingtempname', 'nanogong') . $nanogongnum;
    
    echo '<form name="nanogongsubmit" action="' . $PAGE->url . '" method="post"><table align="center" cellspacing="0" cellpadding="0"><tr><td align="right">' . get_string('voicetitle', 'nanogong') . '<img class="req" title="Required field" alt="Required field" src="' . $CFG->wwwroot . '/mod/nanogong/pix/req.gif" />' . '<br >' . get_string('titlemax', 'nanogong') . '</td><td><input type="text" id="nanogongtitle" maxlength="30" size="25" value="' . $nanogongtitle . '" /></td></tr><tr><td align="right">' . get_string('voicerecording', 'nanogong') . '<img class="req" title="Required field" alt="Required field" src="' . $CFG->wwwroot . '/mod/nanogong/pix/req.gif" /></td><td><applet id="nanogonginstance" archive="nanogong.jar" code="gong.NanoGong" width="180" height="40"><param name="MaxDuration" value="' . $maxduration .'" /></applet></td></tr><tr>';
    if ($type == 'add') {
        echo '<td align="right"><input type="button" id="save" name="save" value="' . get_string('submit', 'nanogong') . '" onclick="javascript:nanogong_save_message(\'' . $type . '\', ' . $cmid . ', \'' . get_string('emptymessage', 'nanogong') . '\', \'' . get_string('voicetitle', 'nanogong') . '\');" /></td><td>';
        echo $OUTPUT->single_button(new moodle_url($PAGE->url), get_string('cancel', 'nanogong'));
        echo '</td>';
    }
    else {
        echo '<td align="center" colspan="2"><input type="button" id="save" name="save" value="' . get_string('submit', 'nanogong') . '" onclick="javascript:nanogong_save_message(\'' . $type . '\', ' . $cmid . ', \'' . get_string('emptymessage', 'nanogong') . '\', \'' . get_string('voicetitle', 'nanogong') . '\');" /></td>';
    }
    echo '</tr></table></form>';
    echo '<div style="text-align:right">' . get_string('requiredfield', 'nanogong') . '<img class="req" title="Required field" alt="Required field" src="' . $CFG->wwwroot . '/mod/nanogong/pix/req.gif" />' . '</div>';
}

function nanogong_check_content($nanogongfilename, $nanogongid, $userid, $type) {
    global $DB;

    $isright = 0;
    $nanogongaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$nanogongid, 'userid'=>$userid));
    foreach ($nanogongaudios as $nanogongaudio) {
        if (strcmp($nanogongaudio->name, $nanogongfilename) == 0) {
            $isright = 1;
        }
    }
    $studentmessage = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogongid, 'userid'=>$userid));
    if (strpos($studentmessage->message, $nanogongfilename)) {
        $isright = $type;
    }
    if ($isright == 0) {
        error('Invalid Parameters');
    }
}

function nanogong_show_chronological_order($contextid, $nanogongid, $toreverse, $topage, $pagenumber, $tolistall) {
    global $DB, $OUTPUT, $PAGE;
    
    $recordingarray = array();
    $nangogongstudents = nanogong_get_participants($nanogongid);
    foreach ($nangogongstudents as $nangogongstudent) {
        $studentwork = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogongid, 'userid'=>$nangogongstudent->id));
        $studentaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$nanogongid, 'userid'=>$nangogongstudent->id, 'type'=>1));
        foreach ($studentaudios as $studentaudio) {
            if (strpos($studentwork->message, $studentaudio->name)) {
                $recordingarray[] = $studentaudio->timecreated;
            }
        }
    }
    sort($recordingarray);

    if (count($recordingarray)) {
        echo '<table align="center" cellspacing="0" cellpadding="0"><tr>';
        echo nanogong_show_pagnumber_settings_chronological($pagenumber, $toreverse, $tolistall);
        echo '</tr></table>';
        echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td align="center" colspan="3">';
        if ($toreverse == 0) {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('toreverse'=>1, 'pagenumber'=>$pagenumber, 'tolistall'=>$tolistall)), get_string('reversebutton', 'nanogong'));
        }
        else {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('toreverse'=>0, 'pagenumber'=>$pagenumber, 'tolistall'=>$tolistall)), get_string('chronologicalbutton', 'nanogong'));
        }
        echo '</td></tr><tr><td align="center"><b>' . get_string('submittedtime', 'nanogong') . '</b></td><td align="center"><b>' . get_string('tablename', 'nanogong') . '</b></td><td><b>' . get_string('recording', 'nanogong') . '</b></td></tr>';
        if ($pagenumber == 0) {
            $pagenumber = count($recordingarray);
        }
     
        for ($i = $topage * $pagenumber; $i < count($recordingarray) && $i < ($topage + 1) * $pagenumber; $i++) {
            if ($toreverse) {
                $j = count($recordingarray) - $i - 1;
            }
            else {
                $j = $i;
            }
            nanogong_show_all_students($contextid, $nanogongid, $recordingarray[$j]);
        }
        echo '</table>';
        echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td>';
        if ($topage) {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('topage'=>$topage - 1, 'pagenumber'=>$pagenumber, 'tolistall'=>$tolistall, 'toreverse'=>$toreverse)), get_string('previouspage', 'nanogong'));
        }
 
        echo '</td><td>';
        echo get_string('page', 'nanogong');
        echo $topage + 1;
        echo '/';
        if ($pagenumber) {
            $pages = count($recordingarray) / $pagenumber;
            $pagesint = (int) (count($recordingarray) / $pagenumber);
            if (abs($pages - $pagesint) == 0) {
                echo $pagesint;
            }
            else {
                echo $pagesint + 1;
            }
        }
        else {
            echo '1';
        }
        echo '</td><td>';
        if (($topage + 1) * $pagenumber < count($recordingarray)) {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('topage'=>$topage + 1, 'pagenumber'=>$pagenumber, 'tolistall'=>$tolistall, 'toreverse'=>$toreverse)), get_string('nextpage', 'nanogong'));
        }
        echo '</td></tr></table>';
    }
}
