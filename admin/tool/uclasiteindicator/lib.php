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
    private $id;
    
    function __construct($courseid) {
        global $DB;
        
        $indicator = $DB->get_record('ucla_siteindicator', array('courseid' => $courseid), '*', MUST_EXIST);
        $this->property->courseid = $courseid;
        $this->property->type = $indicator->type;
        $this->id = $indicator->id;
        
        $this->type_obj = null;
    }
    
    /**
     * Delete a site indicator entry
     * 
     * @global type $DB 
     */
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator', array('id' => $this->id));
    }
    
    /**
     * @todo write this function
     * @global type $DB
     * @param type $newtype 
     */
    public function change_type($newtype) {
        global $DB;
        $option = array();
        
        // Handle int and short_name types
        if(is_int($newtype)) {
            
        } else {
            
        }
    }
    
    /**
     * Get a site indicator type object.  Expect following object:
     *  type->fullname
     *  type->shortname
     *  
     * @global type $DB
     * @return type 
     */
    public function load_type() {
        global $DB;

        if(!empty($this->type_obj)) {
            // Cache the object
            return $this->type_obj;
        } else {
            $typeobj = $DB->get_record('ucla_siteindicator_type', array('id' => $this->property->type), '*', MUST_EXIST);
            $this->type_obj = $typeobj;
            return $typeobj;
        }
    }
    
    /**
     * Safe way of getting an indicator entry.  This will assign null if
     * the entry does not exist.
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
                
        $request = $DB->get_record('ucla_siteindicator_request', array('requestid' => $requestid), '*', MUST_EXIST);

        $this->id = $request->id;
        $this->entry->type = $request->type;
        $this->request->support = $request->support;
        $this->request->categoryid = $request->categoryid;
        $this->request->requestid = $requestid;
    }
    
    /**
     * Create a site indicator entry from a request.
     * 
     * @global type $DB 
     */
    function create_indicator_entry() {
        global $DB;
        
        $DB->insert_record('ucla_siteindicator', $this->entry);
        $this->delete();
    }
    
    /**
     * @todo test this function! 
     */
    public function generate_jira_ticket() {
        global $DB , $CFG;
        
        // Get the information we need to print in the ticket
        $requested_course = $DB->get_record('course_request', array('id' => $this->request->requestid));
        $user = $DB->get_record('user', array('id' => $requested_course->requester));
        $user_string = $user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')';
        
        $requested_course->user = $user_string;
        $requested_course->pending = $CFG->wwwroot . '/course/pending.php';
        $requested_course->approve = $CFG->wwwroot . '/course/pending.php?approve=' . $this->request->requestid;
        $requested_course->reject = $CFG->wwwroot . '/course/pending.php?reject=' . $this->request->requestid;
        
        $title = get_string('jira_title', 'tool_uclasiteindicator', $requested_course);
        $message = get_string('jira_msg', 'tool_uclasiteindicator', $requested_course);
        
        
        $params = array(
            'pid' => get_config('block_ucla_help', 'jira_pid'),
            'issuetype' => 1,
            'os_username' => get_config('block_ucla_help', 'jira_user'),
            'os_password' => get_config('block_ucla_help', 'jira_password'),
            'summary' => $title,
            'assignee' => $this->request->support,
            'reporter' => $this->request->support,
            'description' => $message,
        );        

        echo "<pre>";
        print_r($message);
        echo "<pre>";
        //$result = do_request(get_config('block_ucla_help', 'jira_endpoint'), $params, 'POST');      
        
    }
    
    /**
     * Delete the site indicator request from the table
     * 
     * @global type $DB 
     */
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator_request', array('requestid' => $this->id));
    }
        
    /**
     * Create a site indicator request.  
     * 
     * @global type $DB
     * @param obj $newindicator is an object 
     */
    static function create($newindicator) {
        global $DB;
        $DB->insert_record('ucla_siteindicator_request', $newindicator);
        
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
    
}

/**
 * Collection of site indicator functions, these are static functions 
 * to work with the site indicator 
 */
class ucla_site_indicator {

    /**
     * Wrapper for getting site categories list.  
     * @todo: hide categorie we don't want to make visible
     * 
     * @param array $parentlist
     * @return type 
     */
    static function get_categories_list(&$parentlist = null) {
        $displaylist = array();
        if($parentlist == null) {
            $parentlist = array();
        }

        // @TODO: what capabilities will be required here?
        // @TODO: sanitize category list if needed...
        make_categories_list($displaylist, $parentlist, 'moodle/course:create');
        
        $displaylist[0] = get_string('req_selopt_other', 'tool_uclasiteindicator');
        
        return $displaylist;
    }
    
    static function get_support_contacts_list() {
        $manager = get_support_contacts_manager();
        $support_contacts = $manager->get_support_contacts();
        return $support_contacts;
    }
    
    /**
     * Create 
     * @global type $DB
     * @param type $data 
     */
    static function request($data) {
        global $DB;
        
        $indicator_category = intval($data->indicator_category);
        
        // Find the ID of the course_request
        $request = $DB->get_record('course_request', array('fullname' => $data->fullname, 
            'shortname' => $data->shortname), '*', MUST_EXIST);
        
        // Determine support contact for JIRA ticket.  This uses the 
        // Help & Feedback block support contacts.  The context of a given 
        // contact maps to a category on the site.
        if($indicator_category) {
            $parents_list = array();
            $category_list = array($indicator_category);

            self::get_categories_list($parents_list);

            if(key_exists($indicator_category, $parents_list)) {
                $category_list = array_merge($parents_list[$indicator_category], $category_list);
            }

            $select = 'id IN (' . implode(',', $category_list) . ')';
            $categories = $DB->get_records_select('course_categories', $select);
            
            $contacts = self::get_support_contacts_list();

            $support_contact = $contacts['System'];
            
            foreach($categories as $cat) {
                if(key_exists($cat->name, $contacts)) {
                    $support_contact = $contacts[$cat->name];
                }
            }
            
        } else {
            // Get default support admin
            $contacts = self::get_supports_contact_list();
            $support_contact = $contacts['System'];
            // @TODO: make a generic indicator category
            $indicator_category = 0;
        }
        
        // Now save to site indicator request table
        $newindicator = new stdClass();
        $newindicator->requestid = $request->id;
        $newindicator->support = $support_contact;
        $newindicator->type = $data->indicator_type;
        $newindicator->categoryid = $indicator_category;
        
        site_indicator_request::create($newindicator);
    }
    
    /**
     * Create a site indicator entry from an existing request entry.  A course
     * is needed to attach that course to the new to be made indicator entry.
     * 
     * @global type $DB
     * @param int $courseid course that will be attached to the site indicator entry
     * @param int $requestid the id of existing request
     * @return int category ID specified in the indicator request
     */
    static function create($courseid, $requestid) {
        global $DB;

        $newindicator = site_indicator_request::load($requestid);
        
        if($newindicator) {
            $newindicator->entry->courseid = $courseid;

            // Create record for course
            $newindicator->create_indicator_entry();

            return $newindicator->property->categoryid;
        }
        
        return 0;
    }

    /**
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
     *
     * @global type $DB
     * @param type $courseid 
     */
    static function delete($courseid) {
        global$DB;
        
        $indicator = site_indicator_entry::load($courseid);
        
        if($indicator) {
            $indicator->delete();
        }
    }
    
    /**
     * 
     * @param type $courseid 
     */
    static function get_site_indicator($courseid) {
        
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
        return $DB->record_exists('ucla_site_indicator', array('courseid' => $courseid));
    }
    
    static function get_indicator_types() {
        global $DB, $CFG;
        
        $query = "SELECT si.id, si.fullname AS
                TYPE , sr.shortname, sr.description
                FROM {$CFG->prefix}ucla_siteindicator_type AS si
                JOIN {$CFG->prefix}ucla_siteindicator_rolemapping AS srm ON srm.typeid = si.id
                JOIN {$CFG->prefix}ucla_siteindicator_roles AS sr ON sr.id = srm.roleid
                ORDER BY si.sortorder";
                
//        $roles = $DB->get_records_sql($query);
        $types = $DB->get_records('ucla_siteindicator_type', null, 'sortorder');
        
        return $types;
    }
    
    static function get_indicators_by_type($type) {
        
    }

}
