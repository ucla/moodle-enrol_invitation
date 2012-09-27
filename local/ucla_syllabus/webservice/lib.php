<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');


class syllabus_ws_item {
    
    const MAX_ATTEMPTS = 3;
    
    private $_data;
    private $_criteria;
    private $_attempt;

    function __construct($record, $criteria) {
        $this->_data = $record;
        $this->_criteria = $criteria;
        $this->_attempt = 0;
    }
    
    /**
     * POST $payload to specified URL if the criteria matches
     * 
     * @param type $payload 
     */
    public function notify($payload) {
        
        if($this->_match_criteria()) {
            
            // Attempt to POST at most MAX_TRIES times
            while(self::MAX_ATTEMPTS > $this->_attempt) {
                
                if($this->_post($payload)) {
                    break;
                } 
                
                $this->_attempt++;
            }
            
            // If we kept ran out of tries, then report
            if($this->_attempt == self::MAX_ATTEMPTS) {
                $this->_contact();
            }
        }
    }
    
    private function _contact() {
        
        // Send email message
        $message = "Couldn't send POST";
        $subject = "Couldn't send POST";
        
        $to = $this->_data->contact;
        
        return mail($to, $subject, $message);
    }


    private function _match_criteria() {
        return $this->_match_subject() || $this->_match_srs();
    }
    
    private function _match_subject() {
        // @todo: test this
        if(!empty($this->_data->subjectareas) && !empty($this->_criteria['subjectarea'])) {
            $subjects = explode('|', $this->_data->subject);
            return in_array($this->_criteria['subject'], $subjects);
        }
        
        return false;
    }
    
    private function _match_srs() {
        // @todo: trim the criteria SRS and compare the string to stored SRS
        if(!empty($this->_data->leadingsrs) && !empty($this->_criteria['srs'])) {
            return strstr($this->_criteria['srs'], $this->_data->leadingsrs);
        }
        
        return false;
    }
    
    private function _post($payload) {
        $ch = curl_init();
        
        $sig = '';
        $payload_json = json_encode($payload);
        
        // Encode token if needed
        if(isset($this->_data->token)) {
            $sig = $this->_hash_payload($payload_json);
        }

        $data = array('payload' => base64_encode($payload_json) . '.' . $sig);
        
        // Attach binary file
        if(isset($payload['file'])) {
            $data['file'] = '@' . $payload['file'];
        }
        
       
        // Setup curl POST
        curl_setopt($ch, CURLOPT_URL, $this->_data->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        if(isset($payload['file'])) {
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($payload['file']));
        }
        
        // Execute
        $result_json = curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($result_json);
        
        if(isset($result->status) && $result->status === syllabus_ws_manager::STATUS_OK) {
            return true;
        }
        
        return false;
    }
    
    private function _hash_payload($payload) {
        $sig = hash_hmac('sha256', $payload, $this->_data->token, true);
        return base64_encode($sig);
    }
}

class syllabus_ws_manager {
    
    // Types of events we'll handle
    const ACTION_TRANSFER = 0;
    const ACTION_ALERT = 1;
    
    // Status messages
    const STATUS_OK = 0;
    const STATUS_FAIL = 1;
    
    /**
     * Handle an event action.  
     * 
     * $payload = array(
     *          'file' => 'file.txt';
     *      )
     * 
     * @param type $event
     * @param type $criteria
     * @param type $payload 
     */
    static public function handle($event, $criteria, $payload) {
        global $DB;
        
        $records = $DB->get_records('ucla_syllabus_webservice',
                array('enabled' => 1, 'action' => $event));

        print_object($records);
        // Process actions
        foreach($records as $rec) {
            $notifications = new syllabus_ws_item($rec, $criteria);
            $notifications->notify($payload);
        }
    }
    
    /**
     * Add a new subscription to the webservice
     * 
     * @param type $data
     * @return boolean 
     */
    static public function add_subscription($data) {
        global $DB;
        
        // If nothing to do, then skip it
        if(!isset($data->subjectareas) && !isset($data->srs)) {
            return false;
        }
        
        // Enable by default
        $data->enabled = 1;

        // Save
        $DB->insert_record('ucla_syllabus_webservice', $data);
    }
    
    /**
     * Return list of events we're handling
     * 
     * @return array
     */
    static public function get_event_actions() {
        $actions = array(
            self::ACTION_TRANSFER => get_string('action_transfer','local_ucla_syllabus'),
            self::ACTION_ALERT => get_string('action_alert','local_ucla_syllabus')
        );
        
        return $actions;
    }
    
    static public function get_subscriptions() {
        global $DB;
        return $DB->get_records('ucla_syllabus_webservice');
    }
    
    static public function update_subscription($record) {
        global $DB;
        $DB->update_record('ucla_syllabus_webservice', $record);
    }
    
    static public function delete_subscription($id) {
        global $DB;
        $DB->delete_records('ucla_syllabus_webservice', array('id' => $id));
    }
    
}