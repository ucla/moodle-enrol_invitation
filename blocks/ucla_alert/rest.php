<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_login();

$render = optional_param('render', '', PARAM_TEXT);

if(!empty($render)) {
    $data = json_decode($render);
    
    if($data->type == 'item') {
        $out = new alert_html_section_item($data->text);
    } else if($data->type == 'header') {
        $out = new alert_html_header_box($data->text);
    }
    
    echo $out->render();
//    echo print_object($data);
}
