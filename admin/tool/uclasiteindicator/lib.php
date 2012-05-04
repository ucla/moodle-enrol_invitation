<?php

/**
 * UCLA Site Indicator
 * 
 * @package     ucla
 * @subpackage  uclasiteindicator
 * @author      Alfonso Roman
 */

require_once(dirname(__FILE__) . '/../../../config.php');

require_once($CFG->libdir.'/formslib.php');

// To get categories
require_once($CFG->dirroot . '/course/lib.php');

// From the UCLA help block -- to get support contacts and send jira ticket
require_once($CFG->dirroot . '/local/ucla/jira.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');


/**
 * A site indicator entry unit 
 */
class site_indicator_entry {
    
    public $property;
    
    public $type_obj;
    
    private $_id;
    
    function __construct($courseid) {
        global $DB;
        
        $indicator = $DB->get_record('ucla_indicator', array('courseid' => $courseid), '*', MUST_EXIST);
        $this->property->courseid = $courseid;
        $this->property->type = $indicator->type;
        $this->_id = $indicator->id;
        
        $this->type_obj = null;
    }
    
    /**
     * Delete a site indicator entry
     */
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_indicator', array('id' => $this->_id));
    }
    
    /**
     * @todo write this function
     * @global type $DB
     * @param type $newtype 
     */
    public function change_type($newtype) {
//        global $DB;
//        $option = array();
//        
//        // Handle int and short_name types
//        if(is_int($newtype)) {
//            
//        } else {
//            
//        }
    }
    
    /**
     * Get a site indicator type object.  Expect following object:
     *  type->fullname
     *  type->shortname
     *  
     * @return type 
     */
    public function load_type() {
        global $DB;

        if(!empty($this->type_obj)) {
            // Cache the object
            return $this->type_obj;
        } else {
            $typeobj = $DB->get_record('ucla_indicator_type', array('id' => $this->property->type), '*', MUST_EXIST);
            $this->type_obj = $typeobj;
            return $typeobj;
        }
    }
    
    public function get_assignable_roles() {
        global $CFG, $DB;
        $list = array();
        
        $query = "SELECT r.name
                FROM {$CFG->prefix}role AS r
                JOIN {$CFG->prefix}ucla_indicator_assign AS sra ON sra.roleid = r.id
                JOIN {$CFG->prefix}ucla_indicator_mapping srm ON srm.siteroleid = sra.siteroleid
                WHERE srm.typeid = {$this->property->type}";
        
        $records = $DB->get_records_sql($query);
        
        foreach($records as $rec) {
            $list[] = $rec->name;
        }
        
        return $list;                
    }
    

    
    /**
     * Safe way of getting an indicator entry. 
     * 
     * @param type $courseid
     * @return null|\site_indicator_entry 
     */
    static function load($courseid) {
        try {
            return new site_indicator_entry($courseid);
            
        } catch(Exception $e) {
            
            return null;
        }
    }
}

/**
 * A site indicator request unit 
 */
class site_indicator_request {

    public $request;
    
    public $entry;
    
    private $id;
    
    function __construct($requestid) {
        global $DB;

        $this->request = new stdClass();
        $this->entry = new stdClass();
                
        $request = $DB->get_record('ucla_indicator_request', 
                array('requestid' => $requestid), '*', MUST_EXIST);

        $this->id = $request->id;                           // Indicator request ID
        $this->entry->type = $request->type;                // Indicator type
        $this->request->support = $request->support;        // Support Contact
        $this->request->categoryid = $request->categoryid;  // Requested category
        $this->request->requestid = $requestid;             // Request ID of course_request
        $this->request->requester = $request->requester;    // User who requested the course
    }
    
    /**
     * Create a site indicator entry from a request.  This also deletes
     * the request.
     * 
     * @global type $DB 
     */
    function create_indicator_entry() {
        global $DB;
        
        $DB->insert_record('ucla_indicator', $this->entry);
        
        $this->set_default_role();
        $this->delete();
    }
    
    /**
     * This sets the default role for the course requestor.  This role is based 
     * on the site's role assignments.
     * 
     * @todo assign a dummy 'course creator' role
     * @return type 
     */
    private function set_default_role() {
        global $CFG, $DB;
        
        // Pick out the highest ranked role
        $query = "SELECT r.id
                FROM {$CFG->prefix}role AS r
                JOIN {$CFG->prefix}ucla_indicator_assign AS sra ON sra.roleid = r.id
                JOIN {$CFG->prefix}ucla_indicator_mapping srm ON srm.siteroleid = sra.siteroleid
                WHERE srm.typeid = {$this->entry->type}
                ORDER BY r.sortorder";
        
        $records = $DB->get_records_sql($query);

        // Get role id
        $records = array_shift($records);
        
        $roleid = $records->id;
        
        // We need to get the user
        $userid = $this->request->requester;
        $courseid = $this->entry->courseid;
        
        // Get context
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        
        // Assign role
        require_capability('moodle/role:assign', $context);
        
        return role_assign($roleid, $userid, $context->id, '', NULL);
    }
    
     /**
     * @todo test this function! 
     */
    public function generate_jira_ticket() {
        global $DB , $CFG;
        
        // Get the information we need to print in the ticket
        $req_course = $DB->get_record('course_request', 
                array('id' => $this->request->requestid));
        
        // Format strings
        $req_course->type = $this->get_type_str();
        $req_course->user = $this->get_user_str($req_course->requester);
        $req_course->category = $this->get_category_str();
        $req_course->summary = $this->fix_linebreaks($req_course->summary);
       
        // Attach the pending course links
        $req_course->action = $CFG->wwwroot . '/course/pending.php?request=' 
                . $this->id;
        
        // Prepare JIRA params
        $title = get_string('jira_title', 'tool_uclasiteindicator', $req_course);
        $message = get_string('jira_msg', 'tool_uclasiteindicator', $req_course);
        $support = $this->request->support;
        
        // Jira params
        $params = array(
            'pid' => get_config('block_ucla_help', 'jira_pid'),
            'issuetype' => 1,
            'os_username' => get_config('block_ucla_help', 'jira_user'),
            'os_password' => get_config('block_ucla_help', 'jira_password'),
            'summary' => $title,
            'assignee' => $support,
            'reporter' => $support,
            'description' => $message,
        );
        
        echo "support: " . $support . "<br/>";
        echo "<pre>";
        print_r($title);
        echo "</pre>";
        echo "<pre>";
        print_r($message);
        echo "</pre>";

        // Create ticket
        //$result = do_request(get_config('block_ucla_help', 'jira_endpoint'), $params, 'POST');      
    }
    
    /**
     * Delete the site indicator request 
     * 
     */
    public function delete() {
        global $DB;
        
        $DB->delete_records('ucla_indicator_request', 
                array('id' => $this->id));
    }
        
    /**
     * Create a site indicator request.  
     * 
     * @param obj $newindicator is an object 
     */
    static function create($newindicator) {
        global $DB;
        
        $DB->insert_record('ucla_indicator_request', $newindicator);
        
        // Get the request and generate jira ticket
        $request = new site_indicator_request($newindicator->requestid);
        $request->generate_jira_ticket();
    }
    

    /**
     * Safe loading of indicator request.
     * 
     * @param type $requestid
     * @return null|\site_indicator_request 
     */
    static function load($requestid) {
        try {
            return new site_indicator_request($requestid);
        } catch(Exception $e) {
            return null;
        }
    }

    
    private function fix_linebreaks($string) {
        $fix = str_replace('</p>', "\n\n", $string);
        $fix = preg_replace('#<br\s*/?>#i', "\n", $string);
        $fix = strip_tags($fix);
        return $fix;
    }
    
    private function get_category_str() {
        global $DB;
        
        if($this->request->categoryid) {
            $category_string = "";
            
            $category = $DB->get_record('course_categories', 
                    array('id'=>$this->request->categoryid));
            
            if($category->parent) {
                $parent = $DB->get_record('course_categories', 
                        array('id' => $category->parent));
                $category_string = $parent->name . ' > ';
            }
            $category_string .= $category->name;
        } else {
            $category_string = "Other: ";
        }
    }
    
    private function get_type_str() {
       
        $type = ucla_site_indicator::get_type($this->entry->type);
        $str = $type->fullname;
        
        return $str;
    }
    
    private function get_user_str($id) {
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $id));
        $str = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
        return $str;        
    }
    
    
}

// Reference: http://docs.atlassian.com/jira/REST/latest/
// https://jira.ats.ucla.edu/CreateIssueDetails.jspa
class jira_api {
    const base_url = 'https://jira.ats.ucla.edu/rest/';
    
    static function auth() {
//        $url = '/auth/latest/session';
        $url = 'api/2.0.alpha1/issue';
        
        $data = array(
            'fields' => array(
                'project' => array('id' => '10077'),
                'summary' => 'This is a test summary',
                'description' => 'This is a test description',
                'issuetype' => array('id' => '1'),
                'assignee' => array('name' => 'aroman'),
                'reporter' => array('name' => 'aroman')
                )
        );
        
        $auth = base64_encode(get_config('block_ucla_help', 'jira_user') . ':' 
                . get_config('block_ucla_help', 'jira_password'));
        $headers = array(
            'Content-Type: application/json',
            'X-Atlassian-Token: no-check',
            'Authorization: Basic ' . $auth
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, jira_api::base_url . $url); // set url to post to
//        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
//        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // times out after 4s
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // add POST fields
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);
        
        echo "<pre>";
        echo print_r($result);
        echo "</pre>";
    }
}


/**
 * Collection of site indicator functions, these are static functions 
 * to work with the site indicator 
 */
class ucla_site_indicator {

    /**
     * Returns a filtered categories list
     * 
     * @todo: hide categorie we don't want to make visible
     * 
     * @param array $parentlist
     * @return type 
     */
    static function get_categories_list(&$parentlist = null) {
        global $DB;
        
        $displaylist = array();
        if($parentlist == null) {
            $parentlist = array();
        }

        //make_categories_list($displaylist, $parentlist, 'moodle/course:create');
        // @todo: pick up from DB eventually
        $exclusion_list = array('Miscellaneous');        
        
        // Division level categories
        $categories = $DB->get_records('course_categories', array('parent' => 0));
        
        // Subject area categories
        foreach($categories as $cat) {
            
            if(in_array($cat->name, $exclusion_list)) {
                continue;
            }

            $displaylist[$cat->id] = $cat->name;
            
            // Subject area level categories
            if($children = $DB->get_records('course_categories', 
                    array('parent' => $cat->id))) {
                foreach($children as $child) {
                    $displaylist[$child->id] = $cat->name . ' > ' . $child->name;
                }
            }
        }
        
        $displaylist[0] = get_string('req_selopt_other', 'tool_uclasiteindicator');
        
        return $displaylist;
    }
    
    static function get_support_contacts_list() {
        $manager = get_support_contacts_manager();
        $support_contacts = $manager->get_support_contacts();
        return $support_contacts;
    }
    
    static function get_support_contact($categoryid, &$contacts) {
        global $DB;
        // Attempt to find the support contact for category
        $category = $DB->get_record('course_categories', array('id' => $categoryid));

        if(key_exists($category->name, $contacts)) {
            return $contacts[$category->name];
        } else if(!empty($category->parent)) {
            self::get_support_contact($category->parent, $contacts);
        }
        
        return $contacts['System'];
    }
    
    static function get_category_manager($categoryid) {
        global $DB;
        
        $query = "SELECT ra.userid, r.name
                FROM {role_assignments} ra
                JOIN {context} c ON ra.contextid = c.id
                JOIN {role} r ON ra.roleid = r.id
                LEFT JOIN {role_names} rn ON rn.roleid = ra.roleid
                AND rn.contextid = ra.contextid
                WHERE c.instanceid = ?
                AND r.shortname = ?";
        
        $record = $DB->get_records_sql($query, array($categoryid, 'manager'));
//        echo "<pre>";
//        var_dump($record);
//        echo "</pre>";
        
        return -1;
    }
    
    /**
     * Create an indicator request
     * 
     * @param type $data 
     */
    static function request($data) {
        global $DB;
        
        // Find the ID of the course_request
        $request = $DB->get_record('course_request', 
                array('fullname' => $data->fullname, 
                'shortname' => $data->shortname), '*', MUST_EXIST);
        
        // Determine support contact for JIRA ticket. 
        $contacts = self::get_support_contacts_list();
        $contact = self::get_support_contact($data->indicator_category, $contacts);

        // Now save to site indicator request table
        $newindicator = new stdClass();
        $newindicator->requestid = $request->id;
        $newindicator->support = $contact;
        $newindicator->type = $data->indicator_type;
        $newindicator->categoryid = $data->indicator_category;
        $newindicator->requester = $request->requester;
        
        site_indicator_request::create($newindicator);
    }
    
    /**
     * Create a site indicator entry from an existing request entry.  A course
     * is needed to attach that course to the new to be made indicator entry.
     * 
     * @param int $courseid course that will be attached to the site indicator entry
     * @param int $requestid the id of existing request
     * @return int category ID specified in the indicator request
     */
    static function create($courseid, $requestid) {
        $newindicator = site_indicator_request::load($requestid);
        
        if($newindicator) {
            $newindicator->entry->courseid = $courseid;

            // Create record for course
            $newindicator->create_indicator_entry();

            return $newindicator->request->categoryid;
        }
        
        return 0;
    }
    
    /**
     * Delete an indicator entry
     * 
     * @param type $courseid 
     */
    static function delete($courseid) {
        $indicator = site_indicator_entry::load($courseid);
        
        if($indicator) {
            $indicator->delete();
        }
    }
    
    /**
     * Reject a indicator request
     * 
     * @param type $requestid 
     */
    static function reject($requestid) {
        $request = site_indicator_request::load($requestid);

        if($request) {
            $request->delete();
        }
    }
    
    /**
     * Check if a site is in the site indicator table.  If it is,
     * then => site is collab
     * 
     * @global type $DB
     * @param int $courseid of course
     * @return bool true if site is in table 
     */
    static function is_collab_site($courseid) {
        global $DB;
        return $DB->record_exists('ucla_site_indicator', 
                array('courseid' => $courseid));
    }
    
    static function get_indicator_types() {
        global $DB;

        return $DB->get_records('ucla_indicator_type', 
                array('visible' => 1), 'sortorder');
    }
        
    static function get_type($identifier) {
        global $DB;
        
        if(is_int($identifier) || is_numeric($identifier)) {
            $attributes = array('id' => $identifier);
        } else {
            $attributes = array('shortname' => $identifier);
        }

        $type = $DB->get_record('ucla_indicator_type', $attributes);
        
        return $type;

    }
    
}

class ucla_indicator_admin {
    static function create_types_sql() {
        $query = "INSERT INTO `mdl_ucla_indicator_type` (`id`, `sortorder`, `fullname`, `shortname`, `description`, `visible`) VALUES
                (1, 0, 'Instruction', 'instruction', 'This is the description of the instruction role', 1),
                (2, 0, 'Non-Instruction', 'non_instruction', 'This describes the project role', 1),
                (3, 0, 'Research', 'research', 'this describes the project role as it applies to research', 1),
                (4, 0, 'Test', 'test', 'this describes the test role', 1),
                (5, 0, 'Instruction (Listed at Registrar)', 'registrar', 'An instruction site with an SRS number that is listed at the Registrar', 0)";
        
    }
}