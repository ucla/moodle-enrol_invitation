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
 * Uploading NanoGong files to the server
 *
 * @author     Ning
 * @author     Gibson
 * @package    mod
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__).'/lib.php');

require_login();  // CONTEXT_SYSTEM level

$id = required_param('id', PARAM_INT);
$type = optional_param('type', '', PARAM_TEXT);
$userid = optional_param('userid', '', PARAM_INT);
$title = optional_param('title', '', PARAM_TEXT);

if ($id) {
    $cm = get_coursemodule_from_id('nanogong', $id, 0, false, MUST_EXIST);
    $nanogong = $DB->get_record('nanogong', array('id' => $cm->instance), '*', MUST_EXIST);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $itemid = $nanogong->id;
}
else {
    error('Invalid Parameters!');
}

if (!empty($type)) {
    $nanogongtitle = strip_tags(nanogong_unicode2utf8($title));
    $nanogongtitle = preg_replace('/(^\s+)|(\s+$)/us', '', $nanogongtitle); 
    if ($nanogongtitle == '') {
        print "1";
        exit;
    }
}

$elname = "nanogong_upload_file";

// use data/time as the file name
if (isset($_FILES[$elname]['name'])) {
    $oldname = $_FILES[$elname]['name'];
    $ext = preg_replace("/.*(\.[^\.]*)$/", "$1", $oldname);
    $newname = date("Ymd") . date("His") . $ext;
    $_FILES[$elname]['name'] = $newname;
}

$fs = get_file_storage();

$file = array('contextid'=>$context->id, 'component'=>'mod_nanogong', 'filearea'=>'audio',
              'itemid'=>$itemid, 'filepath'=>'/', 'filename'=>$_FILES[$elname]['name'],
              'timecreated'=>time(), 'timemodified'=>time());
$fs->create_file_from_pathname($file, $_FILES[$elname]['tmp_name']);

$url = $_FILES[$elname]['name'];

if ($type == 'save') {
    $submission = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$USER->id));
    $nanogongimg = '<img title="NanoGongItem" src="pix/icon.gif" style="vertical-align: middle" alt="' . $url . '" />';
    $nanogongform = '<p title="NanoGong Title">' . $nanogongtitle . ' ' . $nanogongimg . '</p>';
        
    if (!$submission) {
        $submission = new stdClass();
        $submission->nanogongid       = $nanogong->id;
        $submission->userid           = $USER->id;
        $submission->message          = $nanogongform;
        $submission->supplement       = '';
        $submission->supplementformat = FORMAT_HTML;
        $submission->audio            = '';
        $submission->comments         = '';
        $submission->commentsformat   = FORMAT_HTML;
        $submission->commentedby      = 0;
        $submission->grade            = -1;
        $submission->timestamp        = time();
        $submission->locked           = true;
        $submission->id = $DB->insert_record("nanogong_messages", $submission);
    }
    else {
        $submission->message          = $nanogongform;
        $submission->supplement       = '';
        $submission->supplementformat = FORMAT_HTML;
    }
    $DB->update_record('nanogong_messages', $submission);
    add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);

    $nanogongaudio = new stdClass();
    $nanogongaudio->nanogongid   = $itemid;
    $nanogongaudio->userid       = $USER->id;
    $nanogongaudio->type         = 1;
    $nanogongaudio->title        = $nanogongtitle;
    $nanogongaudio->name         = $_FILES[$elname]['name'];
    $nanogongaudio->timecreated  = time();
    $nanogongaudio->id = $DB->insert_record("nanogong_audios", $nanogongaudio);
    $DB->update_record('nanogong_audios', $nanogongaudio);    
}
else if ($type == 'add') {
    $submission = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$USER->id));
    $nanogongimg = '<img title="NanoGongItem" src="pix/icon.gif" style="vertical-align: middle" alt="' . $url . '" />';
    $nanogongform = '<p title="NanoGong Title">' . $nanogongtitle . ' ' . $nanogongimg . '</p>';
    if (!strpos($submission->message, $nanogongform)) {
        $submission->message .= $nanogongform;
        $DB->update_record('nanogong_messages', $submission);
        add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);
        
        $nanogongaudio = new stdClass();
        $nanogongaudio->nanogongid   = $itemid;
        $nanogongaudio->userid       = $USER->id;
        $nanogongaudio->type         = 1;
        $nanogongaudio->title        = $nanogongtitle;
        $nanogongaudio->name         = $_FILES[$elname]['name'];
        $nanogongaudio->timecreated  = time();
        $nanogongaudio->id = $DB->insert_record("nanogong_audios", $nanogongaudio);
        $DB->update_record('nanogong_audios', $nanogongaudio);    
    }
}
else {
    $s = $DB->get_record('nanogong_messages', array('nanogongid'=>$nanogong->id, 'userid'=>$userid));
    $s->audio = '<img title="NanoGongItem" src="pix/icon.gif" style="vertical-align: middle" alt="' . $url . '" />';
    $DB->update_record('nanogong_messages', $s);
}

print "$url";

?>
