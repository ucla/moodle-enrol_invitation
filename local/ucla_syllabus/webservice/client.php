<?php

// Self contained syllabus web service client

class syllabus_ws_client {
     
   public static function get_payload($token = null) {
        if(isset($_POST['payload'])) {
            $data = $_POST['payload'];
            
            list($payload, $sig) = explode('.', $data);
            
            $payload = base64_decode($payload);
            
            if(empty($token)) {
                return $payload;
            } else {
                $expected = hash_hmac('sha256', $payload, $token, true);
                if($sig !== $expected) {
                    return null;
                } else {
                    return $payload;
                }
            }
        }
        
        return null;
    }
}