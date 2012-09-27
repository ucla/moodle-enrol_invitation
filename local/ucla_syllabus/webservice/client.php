<?php

// Self contained syllabus web service client

class syllabus_ws_client {
    
    // Status messages
    const STATUS_OK = 0;
    const STATUS_FAIL = 1;
    
    /**
     * Get the payload from a syllabus web service POST.  This will 
     * test again a token if it's provided
     * 
     * @param type $token secret string that will be tested to verify payload
     * @return null | payload array
     */
    public static function get_payload($token = null) {
        if(isset($_POST['payload'])) {
            $data = $_POST['payload'];
            
            list($payload_json, $sig) = explode('.', $data);
            
            $payload = json_decode(base64_decode($payload_json));
            $payload = (array)$payload;
            
            if(empty($token)) {
                return $payload;
            } else {
                $expected = hash_hmac('sha256', $payload_json, $token, true);
                if($sig !== $expected) {
                    return null;
                } else {
                    return $payload;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Save a file included in POST payload
     * 
     * @param array $payload received from class::get_payload()
     * @param string $path to save the file
     * @param string $filename to rename the file
     * @return boolean true on success
     */
    public static function save_file($payload, $path, $filename = '') {
        if(isset($payload['file'])) {
            
            $fname = isset($payload['filename']) ? $payload['filename'] : 'syllabus.pdf';
            
            if(!empty($filename)) {
                $fname = $filename;
            }
            
            if(move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . $fname)) {
                return true;
            } 
        }
        
        return false;
    }
}