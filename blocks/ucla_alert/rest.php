<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

global $DB;

// Things that we can do...
$render = optional_param('render', '', PARAM_TEXT);
$update = optional_param('update', '', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

// Make sure user is allowed to update
require_login($courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
require_capability('moodle/course:update', $context);

if(!empty($render)) {
    $data = json_decode($render);
    
    $out = '';
    
    if($data->type == 'item') {
        $out = new alert_html_section_item(strip_tags($data->text));
    } else if($data->type == 'header') {
        $out = new alert_html_header_box(strip_tags($data->text));
    } else if($data->type == 'newnode') {
        $out = new alert_edit_section_li(strip_tags($data->text));
    }
    
    echo $out->render();
}

// We want to update data
if(!empty($update)) {
    $data = json_decode($update);

    if($data->sections) {
        foreach($data->sections as $section) {
            $record = new stdClass();
            $record->id = $section->recordid;
            $record->courseid = $data->courseid;
            $record->entity = $section->entity;
            $record->render = ucla_alert::RENDER_REFRESH;
            $record->json = json_encode($section);
            $record->visible = $section->visible;
            
            $DB->update_record('ucla_alerts', $record);
        }
    }
    
    if($data->headers) {
        foreach($data->headers as $header) {
            $record = new stdClass();
            $record->id = $header->recordid;
            $record->courseid = $data->courseid;
            $record->entity = $header->entity;
            $record->render = ucla_alert::RENDER_REFRESH;
            $record->json = json_encode($header);
            $record->visible = $header->visible;
            
            $DB->update_record('ucla_alerts', $record);
        }
    }
    
}