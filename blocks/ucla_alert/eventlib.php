<?php

require_once(dirname(__FILE__) . '/locallib.php');

function ucla_alert_post($data) {

    if(is_array($data)) {
        $data = (object)$data;
    }
    
    ucla_alert::handle_alert_post($data);
    
}