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

// Use the UCLA help block to get support contacts
require_once($CFG->dirroot . '/local/ucla/jira.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');


class site_indicator_entry {
    public $property;
    private $id;
    
    function __construct($courseid) {
        global $DB;
        
        $indicator = $DB->get_record('ucla_siteindicator', array('courseid' => $courseid), '*', MUST_EXIST);
        $this->property->courseid = $courseid;
        $this->property->type = $indicator->type;
        $this->id = $indicator->id;
    }
    
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator', array('id' => $this->id));
    }
    
    public function change_type($newtype) {
        global $DB;
        $option = array();
        
        // Handle int and short_name types
        if(is_int($newtype)) {
            
        } else {
            
        }
    }
    
    public function get_type() {
        global $DB;
        
        if(isset($this->property->type_obj)) {
            return $this->property->type_obj;
        } else {
            $typeobj = $DB->get_record('ucla_siteindicator_type', array('id' => $this->property->type), '*', MUST_EXIST);
            $this->property->type_obj = $typeobj;
            return $typeobj;
        }
    }
}

class site_indicator_request {

    public $request;
    public $entry;
    private $id;

    function __construct($requestid) {
        global $DB;

        $this->request = new stdClass();
        $this->entry = new stdClass();
                
        $request = $DB->get_record('ucla_siteindicator_request', array('requestid' => $requestid), '*', MUST_EXIST);

        $this->entry->type = $request->type;
        $this->request->support = $request->support;
        $this->request->categoryid = $request->categoryid;
        $this->request->requestid = $requestid;
        $this->id = $request->id;
    }
    
    function create_indicator_entry() {
        global $DB;
        
        $DB->insert_record('ucla_siteindicator', $this->entry);
        $this->delete();
    }
    
    /**
     * @todo write this function! 
     */
    private function generate_jira_ticket() {
        echo "TODO: GENERATE JIRA TICKET";
    }
    
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator_request', array('requestid' => $this->id));
    }
        
    static function create($newindicator) {
        global $DB;
        $DB->insert_record('ucla_siteindicator_request', $newindicator);
    }
    
}


class ucla_site_indicator {
    
    /**
     * Returns list of available collab site indicators 
     * @todo: get list from database!
     * 
     * @return type 
     */
    static function get_indicators_list() {
        return array('Instruction (with Intructor roles)', 
            'Non-Instruction (with Project roles)', 
            'Research (with Project roles)', 
            'Test (experimental)');
    }
    
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
     *
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
    
    
    static function create($courseid, $requestid) {
        global $DB;

        if($DB->record_exists('ucla_siteindicator_request', array('requestid' => $requestid))) {
            $newindicator = new site_indicator_request($requestid);
            $newindicator->course->courseid = $courseid;

            // Create record for course
            $newindicator->create_indicator_entry();

            return $newindicator->property->categoryid;
        }
    }

    static function reject($requestid) {
        $request = new site_indicator_request($requestid);
        $request->delete();
    }
    
    static function remove($courseid) {
        $indicator = new site_indicator_entry($courseid);
        $indicator->delete();
    }
    
    static function get_site_indicator($courseid) {
        
    }
    
    static function is_collab($courseid) {
        global $DB;
        return $DB->record_exists('ucla_site_indicator', array('courseid' => $courseid));
    }

}
