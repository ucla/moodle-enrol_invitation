<?php

// Self contained syllabus web service client

class syllabus_ws_client {
     
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
     * Save a file given in payload
     * 
     * @param type $payload
     * @param string $path where to save the file
     * @return boolean 
     */
    public static function save_file($payload, $path) {
        if(isset($payload['file']) && isset($payload['filename'])) {
            if(move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . $payload['filename'])) {
                return true;
            } 
        }
        
        return false;
    }
}