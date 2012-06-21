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
 * Prints a particular instance of nanogong
 *
 * @author     Ning 
 * @author     Gibson
 * @package    mod
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // nanogong instance ID - it should be named as the first character of the module

$tograde = optional_param('tograde', 0, PARAM_INT);
$toadd = optional_param('toadd', 0, PARAM_BOOL);
$todelete = optional_param('todelete', 0, PARAM_BOOL);
$checkdelete = optional_param('checkdelete', 0, PARAM_BOOL);
$tomessage = optional_param('tomessage', 0, PARAM_BOOL);
$toreverse = optional_param('toreverse', 0, PARAM_BOOL);
$tolistall = optional_param('tolistall', 0, PARAM_BOOL);

$topage = optional_param('topage', 0, PARAM_INT);
$pagenumber = optional_param('pagenumber', 10, PARAM_INT);

if ($id) {
    $cm         = get_coursemodule_from_id('nanogong', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $nanogong   = $DB->get_record('nanogong', array('id' => $cm->instance), '*', MUST_EXIST);
}
elseif ($n) {
    $nanogong   = $DB->get_record('nanogong', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $nanogong->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('nanogong', $nanogong->id, $course->id, false, MUST_EXIST);
}
else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/nanogong:view', $context);


add_to_log($course->id, 'nanogong', 'view', "view.php?id={$cm->id}", $nanogong->name, $cm->id);

$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['applet'] = array('quickform/applet.php','HTML_QuickForm_applet');


/// Print the page header

$PAGE->set_url('/mod/nanogong/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($nanogong->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->js('/mod/nanogong/nanogongapplet.js');

// Output starts here
echo $OUTPUT->header();
nanogong_preload_applet();

echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td><b>' . get_string('modulenamefull', 'nanogong') . '</b></td></tr></table>';
if ($nanogong->intro) {
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
    echo format_module_intro('nanogong', $nanogong, $cm->id);
    echo $OUTPUT->box_end();
    echo '<br >';
}

echo $OUTPUT->box_start();
echo '<table align="center" cellspacing="0" cellpadding="0">';
echo '<tr><td align="right"><b>' . get_string('maxdurationdetail', 'nanogong') . ':</b></td><td>';
echo $nanogong->maxduration;
echo '</td></tr><tr><td align="right"><b>' . get_string('maxnumberdetail', 'nanogong') . ':</b></td><td>';
if ($nanogong->maxnumber) {
    echo $nanogong->maxnumber;
}
else {
    echo get_string('nolimitation', 'nanogong');
}
echo '</td></tr>';
echo '</td></tr><tr><td align="right"><b>' . get_string('maxgrade', 'nanogong') . ':</b></td><td>';
echo $nanogong->grade;
echo '</td></tr>';
if ($nanogong->timeavailable || $nanogong->timedue) {
    if ($nanogong->timeavailable) {
        echo '<tr><td align="right"><b>' . get_string('availabledate','nanogong') . ':</b></td>';
        echo '<td>' . userdate($nanogong->timeavailable) . '</td></tr>';
    }
    if ($nanogong->timedue) {
        echo '<tr><td align="right"><b>' . get_string('duedate','nanogong') . ':</b></td>';
        echo '<td>' . userdate($nanogong->timedue) . '</td></tr>';
    }
}
echo '</table>';
echo $OUTPUT->box_end();

$isavailable = false;
$time = time();
if ($nanogong->timedue) {
    $isavailable = ($nanogong->timeavailable <= $time && $time <= $nanogong->timedue);
}
else {
    $isavailable = ($nanogong->timeavailable <= $time);
}
if ($nanogong->preventlate == 0) {
    $isavailable = true;
}

//Only students can submit
if (has_capability('mod/nanogong:submit', $context)) {
    $isopen = true;
}
else {
    $isopen = false;
}

//$coursecontext = context_course::instance($course->id);
//if ($isopen && is_enrolled($coursecontext, $USER)) {
if  ($isopen) {
    $submission = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$USER->id));
    
    // prepare form and process submitted data
    $editoroptions = array(
        'noclean'   => false,
        'maxfiles'  => EDITOR_UNLIMITED_FILES,
        'maxbytes'  => $course->maxbytes,
        'context'   => $context
    );
    
    $data = new stdClass();
    $data->id = $cm->id;
    $data->nanogongmaxduration = $nanogong->maxduration;
    if ($submission) {
        $data->sid              = $submission->id;
        $data->supplement       = $submission->supplement;
        $data->supplementformat = $submission->supplementformat;
    }
    else {
        // A bug in Postgres DB reported by Eric Katchan (2012-05-30)
        //$data->sid              = '';
        $data->sid              = 0;
        $data->supplement       = '';
        $data->supplementformat = '';
    }
    $data = file_prepare_standard_editor($data, 'supplement', $editoroptions, $context, 'mod_nanogong', 'message', $data->sid);

    $supplementform = new mod_nanogong_supplement_form(null, array($data, $editoroptions));

    if($supplementform->is_cancelled()) {
        redirect(new moodle_url($PAGE->url));
    }
    if ($data = $supplementform->get_data()) {
        $s = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$USER->id));
        $data = file_postupdate_standard_editor($data, 'supplement', $editoroptions, $context, 'mod_nanogong', 'message', $s->id);
        $s->supplement = $data->supplement;
        $s->supplementformat = $data->supplementformat;

        $DB->update_record('nanogong_messages', $s);
        
        //TODO fix log actions - needs db upgrade
        add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);
        
        //redirect to get updated submission date and word count
        redirect(new moodle_url($PAGE->url));
    }
    
    // For insert function
    $nanogongtag = optional_param('nanogongtitle', '', PARAM_TEXT);
    $nanogongname = optional_param('nanogongname', '', PARAM_TEXT);
    if ($nanogongtag && $nanogongname) {
        nanogong_check_content($nanogongname, $nanogong->id, $USER->id, 0);    
        $nanogongimg = '<img title="NanoGongItem" src="pix/icon.gif" style="vertical-align: middle" alt="' . $nanogongname . '" />';
        $submission->message .= '<p title="NanoGong Title">' . $nanogongtag . ' ' . $nanogongimg . '</p>';
        $DB->update_record('nanogong_messages', $submission);
        
        add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);
    }
    
    if ($toadd) {
        $nanogongnum = substr_count($submission->message,'NanoGongItem') + 1;
        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'add');
        nanogong_student_submit_form($nanogongnum, $nanogong->maxduration, $cm->id, 'add');
        echo $OUTPUT->box_end();
    }
    
    if ($todelete) {
        $nanogongfilename = optional_param('nanogongfilename', '', PARAM_TEXT);
        if ($nanogongfilename) {
            nanogong_check_content($nanogongfilename, $nanogong->id, $USER->id, 1);
            $nanogongaudios = $DB->get_records('nanogong_audios', array('nanogongid'=>$nanogong->id));
            foreach ($nanogongaudios as $nanogongaudio) {
                if (strcmp($nanogongaudio->name, $nanogongfilename) == 0) {
                    break;
                }
            }
            preg_match('/<img.*?title="NanoGongItem".*?alt="/', $submission->message, $m);
            $nanogongvoice = $m[0] . $nanogongfilename . '" />';
            $nanogongform = '<p title="NanoGong Title">' . $nanogongaudio->title . ' ' . $nanogongvoice . '</p>';
        
            $submission->message = str_replace($nanogongform, '', $submission->message);
            $DB->update_record('nanogong_messages', $submission);
        
            add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);
        }
    }
    

    if (!$submission) {
        if ($isavailable) {
            echo '<br >';
            echo $OUTPUT->box_start('generalbox', 'submitform');
            nanogong_student_submit_form(1, $nanogong->maxduration, $cm->id, 'save');
            echo $OUTPUT->box_end();
        }
        else {
            echo '<p><i>' . get_string('notavailable', 'nanogong') . '</i></p>';
        }
    }
    else {
        $nanogongcheckfilename = optional_param('nanogongcheckfilename', '', PARAM_TEXT);
        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'submit');
        echo '<table align="center" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>' . get_string ('tablemessage', 'nanogong') . '</b></td>';
        if (isset($_COOKIE['submissionarea']) && $_COOKIE['submissionarea'])
            $display = 'block';
        else
            $display = 'none';
        echo '<td align="right">';
        echo '<a style="display:' . (($display=='block')?'none':'block') .
            '" id="submissionicon_view" href="javascript:;" onmousedown="toggleDiv(\'submissionarea\', \'submissionicon\');"><img src="pix/switch_plus.gif" alt="Switch plus" title="' . get_string('view', 'nanogong') . '" /></a>';
        echo '<a style="display:' . $display .
            '" id="submissionicon_hide" href="javascript:;" onmousedown="toggleDiv(\'submissionarea\', \'submissionicon\');"><img src="pix/switch_minus.gif" alt="Switch minus" title="' . get_string('hide', 'nanogong') . '" /></a>';
        echo '</td></tr></table>';
        echo '<div id="submissionarea" style="display:'.$display.'">';
        echo '<table align="center" cellspacing="0" cellpadding="0">';
        if ($checkdelete) {
            echo '<tr><td colspan="2" align="center">';
            echo '<font color="red">' . get_string('checkdeletemessage', 'nanogong') . '</font></td><td>';
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('todelete'=>1, 'nanogongfilename'=>$nanogongcheckfilename)), get_string('yes', 'nanogong'));
            echo '</td><td>';
            echo $OUTPUT->single_button(new moodle_url($PAGE->url), get_string('no', 'nanogong'));
            echo '</td></tr></table><table align="center" cellspacing="0" cellpadding="0">';
        }
        echo '<tr><td align="center">' . get_string('instructions', 'nanogong') . '</td></tr>';       
        echo '<tr><td align="center">';
        echo nanogong_show_in_listbox($submission->message, $nanogongcheckfilename, $context->id, $nanogong->id, 'nanogongstudentlistbox');
        echo '</td></tr></table><table align="center" cellspacing="0" cellpadding="0"><tr><td align="center">';
        $nanogongcount = substr_count($submission->message, "NanoGongItem");
        if ($isavailable && $toadd == 0) {
            if ($nanogong->maxnumber == 0 || $nanogongcount < $nanogong->maxnumber) {
                echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('toadd'=>1)), get_string('add', 'nanogong'));
            }
            else {
                echo get_string('add', 'nanogong');
            }
            echo '</td><td align="left">';
            if ($checkdelete) {
                echo get_string('delete', 'nanogong');
            }
            else {
                echo '<input type="button" id="nanogongcheckdelete" value="' . get_string('delete', 'nanogong') . '" onclick="javascript:nanogong_check_delete_item_from_message(\'' . $PAGE->url . '\', \'' . get_string('deletealertmessage', 'nanogong') . '\');" />';
            }
        }
        echo '</td></tr></table>';
        echo '</div>';
        echo $OUTPUT->box_end();

        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'message');
        echo '<table align="center" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>' . get_string ('messagearea', 'nanogong') . '</b></td>';
        if (isset($_COOKIE['messagearea']) && $_COOKIE['messagearea'])
            $display = 'block';
        else
            $display = 'none';
        echo '<td align="right">';
        echo '<a style="display:' . (($display=='block')?'none':'block') .
            '" id="messageicon_view" href="javascript:;" onmousedown="toggleDiv(\'messagearea\', \'messageicon\');"><img src="pix/switch_plus.gif" alt="Switch plus" title="' . get_string('view', 'nanogong') . '" /></a>';
        echo '<a style="display:' . $display .
            '" id="messageicon_hide" href="javascript:;" onmousedown="toggleDiv(\'messagearea\', \'messageicon\');"><img src="pix/switch_minus.gif" alt="Switch minus" title="' . get_string('hide', 'nanogong') . '" /></a>';
        echo '</td></tr></table>';
        echo '<div id="messagearea" style="display:'.$display.'">';
        if ($tomessage) {
            $supplementform->display();
        }
        else {
            echo '<table align="center" cellspacing="0" cellpadding="0">';
            if ($submission->supplement) {
                echo '<tr><td>';    
                $text = file_rewrite_pluginfile_urls($submission->supplement, 'pluginfile.php', $context->id, 'mod_nanogong', 'message', $submission->id);
                echo format_text($text, $submission->supplementformat);
                echo '</td></tr>';
            }
            if ($isavailable) {
                echo '<tr><td align="center">';
                echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('tomessage'=>1)), get_string('leavemessage', 'nanogong'));
                echo '</td></tr></table>';
            }
            else {
                if (!$submission->supplement) {
                    echo '<tr><td>-</td></tr>';
                }    
                echo '</table>';
            }
        }
        echo '</div>';
        echo $OUTPUT->box_end();

        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'comment');
        echo '<table align="center" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>' . get_string ('feedbacktitle', 'nanogong') . '</b></td>';
        if (isset($_COOKIE['feedbackarea']) && $_COOKIE['feedbackarea'])
            $display = 'block';
        else
            $display = 'none';
        echo '<td align="right">';
        echo '<a style="display:' . (($display=='block')?'none':'block') .
            '" id="feedbackicon_view" href="javascript:;" onmousedown="toggleDiv(\'feedbackarea\', \'feedbackicon\');"><img src="pix/switch_plus.gif" alt="Switch plus" title="' . get_string('view', 'nanogong') . '" /></a>';
        echo '<a style="display:' . $display .
            '" id="feedbackicon_hide" href="javascript:;" onmousedown="toggleDiv(\'feedbackarea\', \'feedbackicon\');"><img src="pix/switch_minus.gif" alt="Switch minus" title="' . get_string('hide', 'nanogong') . '" /></a>';
        echo '</td></tr></table>';
        echo '<div id="feedbackarea" style="display:'.$display.'">';
        echo '<table align="center" border="0" cellspacing="0" cellpadding="0">';       
        if ($submission->grade >= 0 || $submission->comments || $submission->audio) {
            echo '<tr><td align="center"><b>' . get_string('grade', 'nanogong') . '</b></td><td align="center"><b>' . get_string('voicefeedback', 'nanogong') . '</b></td></tr><tr>';
            
            if ($submission->grade >= 0) {   
                echo '<td align="center">' . $submission->grade . '</td>';
            }
            else {
                 echo '<td align="center">-</td>'; 
            }
            echo '<td align="center">';
            if ($submission->audio) {
                echo nanogong_get_applet_code(1, $submission->audio, $context->id, $nanogong->id, $USER->id);
            }
            else {
                echo '-';
            }
            echo '</td>';
            echo '</tr></table>';
            echo '<table align="center" border="0" cellspacing="0" cellpadding="0"><tr><td align="center"><b>' . get_string('commentmessage', 'nanogong') . '</b></td></tr>';
            echo '<tr>';
            if ($submission->comments) {
                echo '<td>';    
                $text = file_rewrite_pluginfile_urls($submission->comments, 'pluginfile.php', $context->id, 'mod_nanogong', 'message', $submission->id);
                echo format_text($text, $submission->commentsformat);
            }
            else {
                echo '<td align="center">-';
            }
            echo '</td></tr>';
            echo '<tr><td align="center">' . get_string('gradedon', 'nanogong') . ' ' . userdate($submission->timestamp) . '</td></tr>';
            echo '</table>';
        }
        else {
            echo '<tr><td><i>' . get_string('nofeeadback', 'nanogong') . '</i></td></tr></table>';
        }
        echo '</div>';
        echo $OUTPUT->box_end();
    }
    
    if ($nanogong->permission) {
        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'choice');
        echo '<table align="center" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>' . get_string ('otherrecording', 'nanogong');
        if ($toreverse) {
            echo ' ' . get_string('reverse', 'nanogong');
        }
        else {
            echo ' ' . get_string('chronological', 'nanogong');
        }
        echo '</b></td>';
        if (isset($_COOKIE['allrecordingsarea']) && $_COOKIE['allrecordingsarea'])
            $display = 'block';
        else
            $display = 'none';
        echo '<td align="right">';
        echo '<a style="display:' . (($display=='block')?'none':'block') .
            '" id="allrecordingsicon_view" href="javascript:;" onmousedown="toggleDiv(\'allrecordingsarea\', \'allrecordingsicon\');"><img src="pix/switch_plus.gif" alt="Switch plus" title="' . get_string('view', 'nanogong') . '" /></a>';
        echo '<a style="display:' . $display .
            '" id="allrecordingsicon_hide" href="javascript:;" onmousedown="toggleDiv(\'allrecordingsarea\', \'allrecordingsicon\');"><img src="pix/switch_minus.gif" alt="Switch minus" title="' . get_string('hide', 'nanogong') . '" /></a>';
        echo '</td></tr></table>';
        echo '<div id="allrecordingsarea" style="display:'.$display.'">';
        nanogong_show_chronological_order($context->id, $nanogong->id, $toreverse, $topage, $pagenumber, $tolistall);
        echo '</div>';
        echo $OUTPUT->box_end();
    }
    if ($isavailable && $submission) {
        echo '<br >';
        echo $OUTPUT->box_start('generalbox', 'history');
        echo '<table align="center" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>' . get_string ('voicerecorded', 'nanogong') . '</b></td>';
        if (isset($_COOKIE['historyarea']) && $_COOKIE['historyarea'])
            $display = 'block';
        else
            $display = 'none';
        echo '<td align="right">';
        echo '<a style="display:' . (($display=='block')?'none':'block') .
            '" id="historyicon_view" href="javascript:;" onmousedown="toggleDiv(\'historyarea\', \'historyicon\');"><img src="pix/switch_plus.gif" alt="Switch plus" title="' . get_string('view', 'nanogong') . '" /></a>';
        echo '<a style="display:' . $display .
            '" id="historyicon_hide" href="javascript:;" onmousedown="toggleDiv(\'historyarea\', \'historyicon\');"><img src="pix/switch_minus.gif" alt="Switch minus" title="' . get_string('hide', 'nanogong') . '" /></a>';
        echo '</td></tr></table>';
        echo '<div id="historyarea" style="display:'.$display.'">';
        echo '<table align="center" cellspacing="0" cellpadding="0">';
        echo nanogong_get_student_audios($context->id, $nanogong->id, $USER->id);
        echo '</table>';
        echo '</div>';
        echo $OUTPUT->box_end();
    }
    echo '<br >';
}

if (has_capability('mod/nanogong:grade', $context)) {
    $nanogongcatalog = optional_param('catalog', 'submitted', PARAM_TEXT);
    
    $s = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$tograde));
    
    // prepare form and process submitted data
    $editoroptions = array(
        'noclean'   => false,
        'maxfiles'  => EDITOR_UNLIMITED_FILES,
        'maxbytes'  => $course->maxbytes,
        'context'   => $context
    );

    $data = new stdClass();
    $data->id = $cm->id;
    $data->nanogongmaxduration = $nanogong->maxduration;
    $data->nanogongcatalog     = $nanogongcatalog;
    if ($s) {
        $data->sid            = $s->id;
        if ($s->grade < 0) {
            $data->nanogonggrade = '';
        }
        else {
            $data->nanogonggrade = $s->grade;
        }
        $data->comments       = $s->comments;
        $data->commentsformat = $s->commentsformat;
    }
    else {
        $data->sid            = 0;
        $data->nanogonggrade  = '';
        $data->comments       = '';
        $data->commentsformat = '';
    }
    $data = file_prepare_standard_editor($data, 'comments', $editoroptions, $context, 'mod_nanogong', 'message', $data->sid);

    if ($s) {
        $nanogongjs = 'javascript:nanogong_save_audio_form(' . $cm->id . ', ' . $s->userid . ');';
        if ($s->audio) {
            $isvoice = $CFG->wwwroot . '/mod/nanogong/nanogongfile.php?contextid=' . $context->id . '&modulename=mod_nanogong&filearea=audio&itemid=' . $nanogong->id . '&name=' . substr($s->audio, strpos($s->audio, 'alt="') + strlen('alt="'), 18);
        }
        else {
            $isvoice = '';
        }
        $commentform = new mod_nanogong_grade_form(null, array($data, $editoroptions, $nanogongjs, $isvoice, $nanogong->grade));
    }
    else {
        $commentform = new mod_nanogong_grade_form(null, array($data, $editoroptions, '', '', $nanogong->grade));
    }

    if ($data = $commentform->get_data()) {
        $s = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'locked'=>false));
        $data = file_postupdate_standard_editor($data, 'comments', $editoroptions, $context, 'mod_nanogong', 'message', $s->id);
        $s->comments = $data->comments;
        $s->commentsformat = $data->commentsformat;
        
        $datagrade = trim($data->nanogonggrade);
        if ($datagrade && (int)$datagrade <= $nanogong->grade && (int)$datagrade >= 0) {
            $s->grade = $data->nanogonggrade;
        }
        else {
            $s->grade = -1;
        }
        $s->commentedby    = $USER->id;
        $s->timestamp      = time();
        $s->locked         = true;
        $DB->update_record('nanogong_messages', $s);
        
        //TODO fix log actions - needs db upgrade
        add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);
        
        nanogong_update_grades($nanogong, $s->userid);
        
        //redirect to get updated submission date and word count
        redirect(new moodle_url($PAGE->url, array('catalog'=>$data->nanogongcatalog)));
    }

    if ($tograde && $s) {
        echo '<br >';    
        echo $OUTPUT->box_start('generalbox', 'gradeform');
        echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td><b>' . get_string('listof', 'nanogong');
        $student = $DB->get_record('user', array('id'=>$s->userid));
        if ($student->firstname) {
            echo $student->firstname;
        }
        if ($student->lastname) {
            echo ' '.$student->lastname;
        }
        echo '</b></td></tr></table><table align="center" cellspacing="0" cellpadding="0"><tr><td align="center">';
        echo '<p>' . get_string('instructions', 'nanogong') . '</p>';
        echo nanogong_show_in_listbox($s->message, '', $context->id, $nanogong->id, 'nanogongteacherlistbox');
        echo '</td>';
        echo '</tr></table>';
        $s->locked = false;
        $DB->update_record('nanogong_messages', $s);
        echo '<table cellspacing="0" cellpadding="0"><tr><td><b>' . get_string('feedbackfor', 'nanogong') . ' ';
        if ($student->firstname) {
            echo $student->firstname;
        }
        if ($student->lastname) {
            echo ' '.$student->lastname;
        }
        echo '</b></td></tr></table>';
        $commentform->display();
        echo $OUTPUT->box_end();
    }
    else {
        if ($tolistall) {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('tolistall'=>0)), get_string('changetostudents', 'nanogong'));
            echo '<br >';
            echo $OUTPUT->box_start('generalbox', 'teacherchoice');
            echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td align="center"><b>' . get_string('otherrecording', 'nanogong');
            if ($toreverse) {
                echo ' ' . get_string('reverse', 'nanogong');
            }
            else {
                echo ' ' . get_string('chronological', 'nanogong');
            }
            echo '</b></td></tr></table>';
            nanogong_show_chronological_order($context->id, $nanogong->id, $toreverse, $topage, $pagenumber, $tolistall);
            echo $OUTPUT->box_end();
        }
        else {
            echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('tolistall'=>1)), get_string('changetorecordings', 'nanogong'));
            echo '<br >';
            $submissions = $DB->get_records('nanogong_messages', array('nanogongid'=>$nanogong->id));
            $nangogongstudents = nanogong_get_participants($nanogong->id);
            $studentarray = array();
            if ($nanogongcatalog == 'graded') {
                foreach ($nangogongstudents as $nangogongstudent) {
                    $studentwork = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$nangogongstudent->id));
                    if ($studentwork->grade >= 0) {
                        $studentarray[] = $nangogongstudent->id;
                    }
                }
            }
            else if ($nanogongcatalog == 'ungraded') {
                foreach ($nangogongstudents as $nangogongstudent) {
                    $studentwork = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$nangogongstudent->id));
                    if ($studentwork->grade < 0) {
                        $studentarray[] = $nangogongstudent->id;
                    }
                }
            }
            else if ($nanogongcatalog == 'all') {
                $nangogongstudents = get_users_by_capability($context, 'mod/nanogong:submit');
                foreach ($nangogongstudents as $nangogongstudent) {
                    $studentarray[] = $nangogongstudent->id;
                }
            }
            else if ($nanogongcatalog == 'unsubmitted') {
                $nangogongstudents = get_users_by_capability($context, 'mod/nanogong:submit');
                $studentworks = $DB->get_records('nanogong_messages', array('nanogongid'=>$nanogong->id));
                foreach ($nangogongstudents as $nangogongstudent) {
                    $issubmitted = false;
                    foreach ($studentworks as $studentwork) {
                        if ($nangogongstudent->id == $studentwork->userid) {
                            $issubmitted = true;
                            break;
                        }
                    }
                    if (!$issubmitted) {
                        $studentarray[] = $nangogongstudent->id;
                    }
                }
            }
            else {
                foreach ($nangogongstudents as $nangogongstudent) {
                    $studentwork = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$nangogongstudent->id));
                    if ($studentwork->message) {
                        $studentarray[] = $nangogongstudent->id;
                    }
                }
            }
            $subcategorystring = '';
            $subcategorystringone = '';
            echo $OUTPUT->box_start('generalbox', 'studentlist');
            echo '<table align="center" cellspacing="0" cellpadding="0">';
            echo '<tr><td align="center" colspan="4"><p><b>' . get_string('studentlist', 'nanogong') . ' ' . get_string('forentering', 'nanogong') . '</b></p></td></tr>';
            echo '<tr><td align="right">' . get_string('show', 'nanogong') . '</td><td colspan="3">';
            echo '<select id="nanogongcatalog" onchange="javascript:nanogong_get_catalog(\'' . $PAGE->url . '\', ' . $pagenumber . ');">';
            if ($nanogongcatalog == 'all') {
                echo '<option value="all" selected="selected">- ' . get_string('allstudents', 'nanogong') . '</option>';
                $subcategorystring = get_string('allcategory', 'nanogong');
                $subcategorystringone = get_string('allcategoryone', 'nanogong');
            }
            else {
                echo '<option value="all"> ' . get_string('allstudents', 'nanogong') . '</option>';
            }
            if ($nanogongcatalog == 'submitted') {
                echo '<option value="submitted" selected="selected">-- ' . get_string('submiitedrecordings', 'nanogong') . '</option>';
                $subcategorystring = get_string('submittedcategory', 'nanogong');
                $subcategorystringone = get_string('submittedcategoryone', 'nanogong');
            }
            else {
                echo '<option value="submitted">-- ' . get_string('submiitedrecordings', 'nanogong') . '</option>';
            }
            if ($nanogongcatalog == 'graded') {
                echo '<option value="graded" selected="selected">---- ' . get_string('gradedstudents', 'nanogong') . '</option>';
                $subcategorystring = get_string('gradedcategory', 'nanogong');
                $subcategorystringone = get_string('gradedcategoryone', 'nanogong');
            }
            else {
                echo '<option value="graded">---- ' . get_string('gradedstudents', 'nanogong') . '</option>';
            }
            if ($nanogongcatalog == 'ungraded') {
                echo '<option value="ungraded" selected="selected">---- ' . get_string('ungradedstudents', 'nanogong') . '</option>';
                $subcategorystring = get_string('ungradedcategory', 'nanogong');
                $subcategorystringone = get_string('ungradedcategoryone', 'nanogong');
            }
            else {
                echo '<option value="ungraded">---- ' . get_string('ungradedstudents', 'nanogong') . '</option>';
            }
            if ($nanogongcatalog == 'unsubmitted') {
                echo '<option value="unsubmitted" selected="selected">-- ' . get_string('studentsnosubmissions', 'nanogong') . '</option>';
                $subcategorystring = get_string('unsubmittedcategory', 'nanogong');
                $subcategorystringone = get_string('unsubmittedcategoryone', 'nanogong');
            }
            else {
                echo '<option value="unsubmitted">-- ' . get_string('studentsnosubmissions', 'nanogong') . '</option>';
            }
            echo '</select>';
            echo '</td>';
            echo nanogong_show_pagnumber_settings($nanogongcatalog, $pagenumber);
            echo '</tr><tr><td colspan="4"><i>';
            if (count($studentarray) == 1) {
               echo get_string('thereis', 'nanogong') . count($studentarray) . ' ' . $subcategorystringone;
            }
            else if (count($studentarray) > 1) {
                echo get_string('thereare', 'nanogong') . count($studentarray) . ' ' . $subcategorystring;
            }
            else {
                echo get_string('thereareno', 'nanogong') . $subcategorystring;
            }
            echo '</i></td></tr></table>';
        
            if (count($studentarray)) {
                echo '<table align="center" width="100%" cellspacing="0" cellpadding="0">';
                if ($pagenumber == 0) {
                   $pagenumber = count($studentarray);
                }
                    for ($i = $topage * $pagenumber; $i < count($studentarray) && $i < ($topage + 1) * $pagenumber; $i++) {
                   nanogong_show_students_list($nanogongcatalog, $context->id, $nanogong->id, $studentarray[$i]);
                }
                echo '</table>';
                echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td>';
                if ($topage) {
                   echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('catalog'=>$nanogongcatalog, 'topage'=>$topage - 1, 'pagenumber'=>$pagenumber)), get_string('previouspage', 'nanogong'));
                }
                echo '</td><td>';
                echo get_string('page', 'nanogong');
                echo $topage + 1;
                echo '/';
                if ($pagenumber) {
                    $pages = count($studentarray) / $pagenumber;
                    $pagesint = (int) (count($studentarray) / $pagenumber);
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
                if (($topage + 1) * $pagenumber < count($studentarray)) {
                    echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('catalog'=>$nanogongcatalog, 'topage'=>$topage + 1, 'pagenumber'=>$pagenumber)), get_string('nextpage', 'nanogong'));
                }
                echo '</td></tr></table>';
            }
            echo $OUTPUT->box_end();
         }
    }
}

// Finish the page
echo $OUTPUT->footer();
