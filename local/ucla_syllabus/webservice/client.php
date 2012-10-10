<?php

// Self contained syllabus web service client

class syllabus_ws_client {

    public static function get_data() {
        global $DB;
        
        $records = $DB->get_records('ucla_syllabus_client');
        
        $data = array();
        foreach($records as $rec) {
            $data[] = unserialize($rec->data);
        }
        
        return $data;
    }
}
