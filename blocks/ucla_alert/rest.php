<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

global $DB;

// Things that we can do...
$render = optional_param('render', '', PARAM_TEXT);
$update = optional_param('update', '', PARAM_TEXT);

// Prepare status that will be sent back.  Assume it will fail
$status = new stdClass();
$status->status = false;

// We're going to make sure that we always send back a response
try {
    
    // Render is READ-ONLY
    if(!empty($render)) {
        // Get data sent
        $data = json_decode($render);

        // At this point, there's no suitable type
        $status->data = 'No suitable type';
        
        // Return a rendered HTML element
        if($data->type == 'item') {
            $out = new alert_html_section_item(strip_tags($data->text));
            $status->status = true;
            $status->data = $out->render();
        } else if($data->type == 'header') {
            $out = new alert_html_header_box(strip_tags($data->text));
            $status->status = true;
            $status->data = $out->render();
        } else if($data->type == 'newnode') {
            $out = new alert_edit_section_li(strip_tags($data->text));
            $status->status = true;
            $status->data = $out->render();
        } else if($data->type == 'tweet') {
            $out = new alert_html_box_tweet($data);
            $status->status = true;
            $status->data = $out->render();
        }

        // Send data and finish
        echo json_encode($status);
        exit();
    }

    // We want to update data
    if(!empty($update)) {

        // If we're going to write new data, check for permissions
        $courseid = required_param('courseid', PARAM_INT);
        
        // Make sure only a logged in user with permission can modify information
        require_login($courseid);
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        require_capability('moodle/course:update', $context);

        // Get our packet
        $data = json_decode($update);
        
        $status->data = 'No updates were made';

        if($data->sections) {
            foreach($data->sections as $section) {
                $record = new stdClass();
                $record->id = $section->recordid;
                $record->courseid = $data->courseid;
                $record->entity = $section->entity;
                $record->render = ucla_alert::RENDER_REFRESH;
                $record->json = json_encode($section);
                $record->visible = $section->visible;

                $status->status = $DB->update_record('ucla_alerts', $record);
            }
            
            $status->data = 'Updated sections.';
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

                $status->status = $DB->update_record('ucla_alerts', $record);
            }

            // Sets config for banner
            if($status->status && $courseid == SITEID) {
                set_config('alert_sitewide', $data->banner, 'block_ucla_alert');
            }
            
            $status->data .= 'Updated headers.';
        }
        
        echo json_encode($status);
        exit(0);
    }

} catch (Exception $e) {
    // Something blew up!
    
    $status->data = 'Something blew up!';
    echo json_encode($status);
}
