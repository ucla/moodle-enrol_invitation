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

abstract class uclaform extends moodleform {
    
}
// To get categories
require_once($CFG->dirroot . '/course/lib.php');

// Use the UCLA help block to get support contacts
require_once($CFG->dirroot . '/local/ucla/jira.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');



class ucla_site_indicator {
       
    private $courseid;
    public $categoryid;
    private $support;
    private $type;
    private $requestid;
            
    function __construct($courseid, $courserequestid) {
        $request = $DB->get_record('ucla_site_indicator_request', array('requestid' => $courserequestid), '*', MUST_EXIST);
        
        $this->courseid = $courseid;
        $this->support = $request->support;
        $this->type = $request->type;
        $this->categoryid = $request->categoryid;
        $this->requestid = $courserequestid;
    }
    
    /**
     * Returns list of available collab site indicators 
     * @TODO: get list from database!
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
     * @TODO: hide categorie we don't want to make visible
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
    
    static function get_supports_contact_list() {
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
            'shortname' => $data->shortname));
        
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
            
            $contacts = self::get_supports_contact_list();

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
        
        $DB->insert_record('ucla_site_indicator_request', $newindicator);
    }
    
    
    static function create($courseid, $courserequestid) {
        $newindicator = new ucla_site_indicator($courseid, $courserequestid);
        // Remove record in the request table
        $newindicator->delete();
        // Create record for course
        $newindicator->create_indicator_entry();
        // Send out JIRA ticket
        $newindicator->send_jira_ticket();
        
        return $newindicator->categoryid;
    }

    static function reject($courseid) {
        global $DB;
        $DB->delete_records('ucla_site_indicator_request', array('requestid' => $courseid));
    }
    
    function create_indicator_entry() {
        $newindicator = new stdClass();
        $newindicator->courseid = $this->courseid;
        $newindicator->type = $this->type;
        
        $DB->insert_record('ucla_site_indicator_request', $newindicator);
    }
    
    public function delete() {
        self::reject($this->requestid);
    }
    
    public function send_jira_ticket() {
        echo "JIRA ticket created";
    }
}

