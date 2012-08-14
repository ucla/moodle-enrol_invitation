<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// @todo:
// move this code to block class
//$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
//require_once($CFG->dirroot . $thisdir . 'lib.php');

$json = required_param('incoming', PARAM_TEXT);
global $DB;

// Update records, based on ID?
$objs = json_decode($json);

foreach($objs as $o) {
    
    if($o->type == 'delete') {
        $DB->delete_records('ucla_alert', array('id' => $o->alertid));
        continue;
    }
    // content packet
    $content = new stdClass();
    $content->type = $o->type;
    $content->content = $o->content;
    
    // DB record
    $in = new stdClass();
    $in->id = $o->alertid;
    $in->type = 'default';
    $in->module = $o->module;
    $in->content = json_encode($content);
    $in->sortorder = $o->sortorder;
    $in->visible = $o->visible;
    
    if(empty($in->id)) {
        $DB->insert_record('ucla_alert', $in);
    } else {
        $DB->update_record('ucla_alert', $in);
    }
}

