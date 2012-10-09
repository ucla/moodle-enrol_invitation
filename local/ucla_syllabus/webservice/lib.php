<?php

require_once($CFG->dirroot . '/local/ucla/lib.php');


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
        $data = array(
            'url' => $this->_data->url
        );
        
        $message = get_string('email_msg', 'local_ucla_syllabus', $data);
        $subject = get_string('email_subject', 'local_ucla_syllabus');
        
        $to = $this->_data->contact;
        
        return mail($to, $subject, $message);
    }


    private function _match_criteria() {
        return $this->_match_subject() || $this->_match_srs();
    }
    
    private function _match_subject() {
        if(!empty($this->_data->subjectarea) && !empty($this->_criteria['subjectarea'])) {
            return intval($this->_data->subjectarea) === intval($this->_criteria['subjectarea']);
        }
        
        return false;
    }
    
    private function _match_srs() {
        // @todo: trim the criteria SRS and compare the string to stored SRS
        if(!empty($this->_data->leadingsrs) && !empty($this->_criteria['srs'])) {
            $trimmed = substr($this->_criteria['srs'], 0, strlen($this->_data->leadingsrs));
            return strstr($trimmed, $this->_data->leadingsrs);
        }
        
        return false;
    }

    private function _post($payload) {
        $ch = curl_init();

        $sig = '';
        
        // Encode token if needed
        if(!empty($this->_data->token)) {
            $sig = $this->_hash_payload(base64_encode($this->_data->token));
        }

        $data = $payload;
        $data['algorithm'] = 'sha256';
        $data['token'] = $sig;
        
        // Setup curl POST
        curl_setopt($ch, CURLOPT_URL, $this->_data->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        // Execute
        $result = curl_exec($ch);
        
        curl_close($ch);

        if($result === FALSE) {
            return false;
        }
        
        return true;
    }
    
    
    private function _hash_payload($payload) {
        $sig = hash_hmac('sha256', $payload, $this->_data->token);
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
     * @param type $event
     * @param type $criteria
     * @param type $payload 
     */
    static public function handle($event, $criteria, $payload) {
        global $DB;
        
        $records = $DB->get_records('ucla_syllabus_webservice', 
                array('enabled' => 1, 'action' => $event));

        // Process actions
        foreach($records as $rec) {
            $notifications = new syllabus_ws_item($rec, $criteria);
            $notifications->notify($payload);
        }
    }

    static function setup($courseid) {
        global $DB;
        
        $termsrs = ucla_map_courseid_to_termsrses($courseid);
        $course = array_shift($termsrs);
        
        $srs = $course->srs;
        $term = $course->term;
        
        // @todo: put this in a single SQL
        $classinfo = ucla_get_reg_classinfo($term, $srs);
        $subjarea = $DB->get_record('ucla_reg_subjectarea', 
                array('subjarea' => $classinfo->subj_area));
                
        $criteria = array(
            'srs' => $srs, 
            'subjectarea' => $subjarea->id,
        );
        
        $payload = array(
            'srs' => $srs,
            'term' => $term,
        );
        
        return array($criteria, $payload);
    }
    
    /**
     * Given a syllabus object, setup the criteria for which 
     * subscribers to the webservice will be notified and set up
     * the payload that they are expecting.
     * 
     * @param object $syllabus
     * @return array of $criteria and $payload 
     */
    static function setup_transfer($syllabus) {
        
        list($criteria, $payload) = self::setup($syllabus->courseid);
        
        $file = $syllabus->stored_file;
            
        // UGLY way of getting the file path
        $cr = new stdClass();
        $file->add_to_curl_request($cr, 'file');
        $path = $cr->_tmp_file_post_params['file'];

        $payload['file'] = $path;
        $payload['file_name_real'] = $file->get_filename();
        
        // Anderson needs the filenamehash to locate the file
        $filename_hash = $file->get_contenthash();
        $payload['file_name'] = $filename_hash;
        
        return array($criteria, $payload);
    }
    
    static function setup_alert($course) {
        global $CFG;
        
        list($criteria, $payload) = self::setup($course->id);
        
        // Ignore subjectarea
        $criteria['subjectarea'] = -1;
        $payload['url'] = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        
        return array($criteria, $payload);
    }
    
        
    static function setup_delete($data) {
        list($criteria, $payload) = self::setup($data->courseid);
        $payload['deleted'] = 1;
        
        return array($criteria, $payload);
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
        if(empty($data->subjectarea) && empty($data->leadingsrs)) {
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
    
    /**
     * Return list of subject areas
     * 
     * @return type 
     */
    static public function get_subject_areas() {
        global $DB;
        $records = $DB->get_records('ucla_reg_subjectarea', null, '', 'id, subj_area_full ');
        foreach($records as &$r) {
            $r = ucwords(strtolower($r->subj_area_full));
        }
        return array_merge(array(0 => 'Select subject area'), $records);
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